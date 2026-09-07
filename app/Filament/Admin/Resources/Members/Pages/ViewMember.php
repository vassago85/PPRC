<?php

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Enums\EventRegistrationStatus;
use App\Enums\MemberStanding;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Actions\ResendMembershipPaymentRequestAction;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Mail\MemberWelcomeInvite;
use App\Models\EmailLog;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\MembershipPayment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Member record page — /admin/members/{record}
 *
 * The record page replaces "click a name and land on an edit form" with a
 * page that shows what has happened to this person and what is currently
 * blocking them, before offering the two or three things that make sense
 * for their state.
 *
 * Legacy: /admin/members/{record}/edit still works and now appears as the
 * "Edit details" tab from this page (right-aligned, visually separated).
 */
class ViewMember extends Page
{
    protected static string $resource = MemberResource::class;

    protected string $view = 'filament.admin.resources.members.pages.view-member';

    /** Which tab the page opened on, tracked in the query string. */
    public ?string $tab = 'overview';

    /** @var Member */
    public $record;

    public function mount(int|string $record): void
    {
        $this->record = MemberResource::getEloquentQuery()->findOrFail($record);
        $this->record->load([
            'user',
            'linkedAdult',
            'memberships.membershipType',
            'memberships.payments',
            'clubBadges',
        ]);

        // Deep-link support: /admin/members/{id}?tab=payments lands the
        // reader on the payments tab. Falls through to the default when
        // the requested value is unknown.
        $requested = request()->query('tab');
        $allowed = ['overview', 'memberships', 'payments', 'matches', 'badges', 'notes'];
        if (is_string($requested) && in_array($requested, $allowed, true)) {
            $this->tab = $requested;
        }
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->fullName();
    }

    /**
     * Hide the built-in Filament page header — the custom Blade view paints
     * its own `.pp-rec-head` (title + pill + meta + state actions + tabs)
     * so a second Filament-managed header would double up the top of the
     * page.
     */
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        // "Members / Onboarding / {name}" only while the member is still in
        // the pipeline; once they are a full member the middle breadcrumb
        // adds noise.
        $trail = [
            MemberResource::getUrl('index') => 'Members',
        ];

        if ($this->record->currentOnboardingStage() !== null) {
            $trail[route('filament.admin.pages.onboarding')] = 'Onboarding';
        }

        $trail[] = $this->record->fullName();

        return $trail;
    }

    /**
     * State-aware header actions. Kept as Filament Actions so the modals
     * / notifications / permission checks stay consistent with the rest
     * of the admin, but rendered inside the custom `.pp-rec-acts` slot of
     * the record head (see the Blade view).
     */
    protected function getHeaderActions(): array
    {
        $actions = [];

        $standing = $this->record->standing();

        // Primary state action — one line, one verb.
        if ($standing === MemberStanding::AwaitingChoice) {
            $actions[] = Action::make('assign_membership')
                ->label('Assign membership')
                ->icon('heroicon-o-identification')
                ->color('primary')
                ->url(fn () => MembershipResource::getUrl('create', ['member_id' => $this->record->id]));
        }

        if ($standing === MemberStanding::AwaitingPayment) {
            $actions[] = ResendMembershipPaymentRequestAction::forMember();
        }

        if ($standing === MemberStanding::AwaitingEmail && $this->record->user) {
            $actions[] = Action::make('resend_welcome')
                ->label('Resend welcome')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Resend welcome email')
                ->modalDescription(fn () => "Resend the account-claim invite to {$this->record->user->email}?")
                ->action(fn () => static::sendWelcomeTo($this->record));
        }

        $actions[] = Action::make('edit_details')
            ->label('Edit details')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->url(fn () => MemberResource::getUrl('edit', ['record' => $this->record]));

        return $actions;
    }

    /**
     * The status pill rendered next to the member's name in the record
     * head. Displays the standing label plus a day-N counter while the
     * record is still in an onboarding stage — that way an admin reads
     * "Choosing plan · day 3" at a glance without opening the Overview
     * tab.
     *
     * @return array{label: string, variant: string, day: ?int}
     */
    public function recordPill(): array
    {
        $standing = $this->record->standing();
        $variantMap = [
            'success' => 'ok',
            'warning' => 'wa',
            'danger' => 'cr',
            'info' => 'in',
            'gray' => 'mu',
        ];
        $variant = $variantMap[$standing->color()] ?? 'mu';

        // day-N counter only applies while the person is mid-onboarding.
        $day = $this->record->currentOnboardingStage() !== null
            ? $this->record->daysInStage()
            : null;

        return [
            'label' => $standing->label(),
            'variant' => $variant,
            'day' => $day,
        ];
    }

    /**
     * Rows for the right-hand "At a glance" card on the record overview.
     * Kept as (label, value, mono?) tuples so the Blade template is a
     * dumb loop and every field paints identically.
     *
     * @return array<int, array{label: string, value: string, mono?: bool, dim?: bool}>
     */
    public function atAGlance(): array
    {
        $membership = $this->record->currentMembership();
        $disciplines = is_array($this->record->shooting_disciplines)
            ? implode(', ', $this->record->shooting_disciplines)
            : null;

        $matchesShot = EventRegistration::query()
            ->where('member_id', $this->record->id)
            ->whereIn('status', [
                EventRegistrationStatus::Registered->value,
                EventRegistrationStatus::Confirmed->value,
            ])
            ->count();

        // "Lifetime paid" is every membership payment we ever confirmed
        // for this person. Cheap because they typically only have a
        // handful over their lifetime.
        $lifetimeCents = (int) MembershipPayment::query()
            ->whereIn('membership_id', $this->record->memberships->pluck('id'))
            ->where('status', PaymentStatus::Confirmed->value)
            ->sum('amount_cents');

        return [
            [
                'label' => 'Membership',
                'value' => $membership?->membershipType?->name ?? 'not chosen yet',
                'dim' => $membership === null,
            ],
            [
                'label' => 'Discipline',
                'value' => $disciplines ?: 'not on file',
                'dim' => ! $disciplines,
            ],
            [
                'label' => 'SAPRF #',
                'value' => $this->record->saprf_membership_number ?? 'not on file',
                'mono' => true,
                'dim' => ! $this->record->saprf_membership_number,
            ],
            [
                'label' => 'SA ID',
                'value' => $this->record->id_number ? Str::mask($this->record->id_number, '·', 4, 7) : 'not on file',
                'mono' => true,
                'dim' => ! $this->record->id_number,
            ],
            [
                'label' => 'Matches shot',
                'value' => (string) $matchesShot,
                'mono' => true,
                'dim' => $matchesShot === 0,
            ],
            [
                'label' => 'Badges',
                'value' => (string) $this->record->clubBadges()->count(),
                'mono' => true,
                'dim' => $this->record->clubBadges()->count() === 0,
            ],
            [
                'label' => 'Lifetime paid',
                'value' => $lifetimeCents > 0
                    ? 'R '.number_format($lifetimeCents / 100, 0)
                    : 'R 0',
                'mono' => true,
                'dim' => $lifetimeCents === 0,
            ],
        ];
    }

    /**
     * Small helper used in the header meta line so the Blade template
     * doesn't have to do its own presence-checks.
     */
    public function contactMetaLine(): HtmlString
    {
        $parts = [];

        if ($num = $this->record->formattedMembershipNumber()) {
            $parts[] = '<span><i>Member no.</i> '.e($num).'</span>';
        } else {
            $parts[] = '<span><i>Member no.</i> not issued yet</span>';
        }

        if ($this->record->join_date) {
            $parts[] = '<span><i>Joined</i> '.e($this->record->join_date->format('d M Y')).'</span>';
        }

        if ($this->record->user?->email && ! $this->record->hasPlaceholderEmail()) {
            $verified = $this->record->user->email_verified_at !== null;
            $pill = $verified
                ? '<span class="pp-pill pp-pill--ok pp-pill--plain" style="margin-left:0.375rem">verified</span>'
                : '<span class="pp-pill pp-pill--wa pp-pill--plain" style="margin-left:0.375rem">unverified</span>';
            $parts[] = '<span><i>Email</i> '.e($this->record->user->email).$pill.'</span>';
        }

        if ($this->record->phone_number) {
            $parts[] = '<span><i>Phone</i> '.e($this->record->phone_country_code.' '.$this->record->phone_number).'</span>';
        }

        return new HtmlString(implode('', $parts));
    }

    /**
     * A composed timeline built from timestamps + email_logs — no new event
     * store required. Ordered newest-first; grouped visually in the view.
     *
     * @return array<int, array<string, mixed>>
     */
    public function timeline(): array
    {
        $events = collect();

        if ($this->record->created_at) {
            $events->push([
                'at' => $this->record->created_at,
                'icon' => 'heroicon-o-user-plus',
                'title' => 'Signed up',
                'detail' => $this->record->created_at->format('D d M Y H:i'),
            ]);
        }

        if ($this->record->user?->email_verified_at) {
            $events->push([
                'at' => $this->record->user->email_verified_at,
                'icon' => 'heroicon-o-check-badge',
                'title' => 'Email confirmed',
                'detail' => $this->record->user->email_verified_at->format('D d M Y H:i'),
            ]);
        }

        foreach ($this->record->memberships as $membership) {
            if ($membership->created_at) {
                $events->push([
                    'at' => $membership->created_at,
                    'icon' => 'heroicon-o-identification',
                    'title' => 'Chose '.($membership->membershipType?->name ?? 'a membership'),
                    'detail' => $membership->created_at->format('D d M Y H:i'),
                ]);
            }
            foreach ($membership->payments as $payment) {
                if ($payment->submitted_at) {
                    $events->push([
                        'at' => $payment->submitted_at,
                        'icon' => 'heroicon-o-arrow-up-tray',
                        'title' => 'Uploaded proof for '.$payment->reference,
                        'detail' => $payment->submitted_at->format('D d M Y H:i'),
                    ]);
                }
                if ($payment->confirmed_at) {
                    $events->push([
                        'at' => $payment->confirmed_at,
                        'icon' => 'heroicon-o-banknotes',
                        'title' => 'Payment confirmed · '.$payment->reference,
                        'detail' => 'R '.number_format(($payment->amount_cents ?? 0) / 100, 2)
                            .' · '.$payment->confirmed_at->format('D d M Y'),
                    ]);
                }
            }
        }

        if ($this->record->join_date) {
            $events->push([
                'at' => Carbon::parse($this->record->join_date),
                'icon' => 'heroicon-o-user-group',
                'title' => 'Became a member',
                'detail' => Carbon::parse($this->record->join_date)->format('D d M Y'),
            ]);
        }

        // Composed from EmailLog for the outbound mail we know went (welcome
        // resends, payment requests, renewal reminders). Cap it — a record
        // that has been chased for months doesn't need every reminder shown.
        if ($this->record->user) {
            $mail = EmailLog::query()
                ->where('user_id', $this->record->user->id)
                ->latest('created_at')
                ->limit(10)
                ->get();
            foreach ($mail as $log) {
                if ($log->created_at) {
                    $events->push([
                        'at' => $log->created_at,
                        'icon' => 'heroicon-o-paper-airplane',
                        'title' => $log->subject ?? 'Email sent',
                        'detail' => $log->created_at->format('D d M Y H:i'),
                    ]);
                }
            }
        }

        return $events
            ->filter(fn (array $e) => $e['at'] !== null)
            ->sortByDesc('at')
            ->values()
            ->all();
    }

    /**
     * Onboarding checklist — what is still missing for this person to be a
     * full member on the books. Rendered in the Overview tab so the block on
     * their progress is obvious.
     *
     * @return array<int, array{done: bool, label: string}>
     */
    public function onboardingChecklist(): array
    {
        return [
            [
                'done' => $this->record->user?->email_verified_at !== null,
                'label' => 'Email confirmed',
            ],
            [
                'done' => $this->record->hasStartedApplication(),
                'label' => 'Membership type chosen',
            ],
            [
                'done' => $this->record->currentMembership()?->status?->value === 'active',
                'label' => 'Membership active',
            ],
            [
                'done' => $this->record->membership_number !== null,
                'label' => 'Member number assigned',
            ],
            [
                'done' => filled($this->record->id_number),
                'label' => 'SA ID captured (needed for endorsement letters)',
            ],
            [
                'done' => filled($this->record->phone_number),
                'label' => 'Phone number on file',
            ],
        ];
    }

    /**
     * Plain-English callout when the record cannot progress.
     */
    public function blockingReason(): ?string
    {
        return match ($this->record->standing()) {
            MemberStanding::AwaitingEmail => 'No verified email address on file. Nothing else can happen until the member clicks the confirmation link — a resend is the only useful action from the club side.',
            MemberStanding::AwaitingChoice => 'No membership type chosen yet, so no member number and no expiry date. This person cannot enter a match at member rates or appear on an endorsement letter.',
            MemberStanding::AwaitingPayment => 'Chose a membership type but the payment hasn\'t arrived. Resending the payment request gives them the reference and banking details again.',
            MemberStanding::Abandoned => 'Marked as abandoned — signed up but stopped responding. They will move back into the queue if they log in.',
            default => null,
        };
    }

    /** Latest 5 payments across all of the member's memberships. */
    public function payments(): array
    {
        return MembershipPayment::query()
            ->whereIn('membership_id', $this->record->memberships->pluck('id'))
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->all();
    }

    /** Latest 10 match entries. */
    public function matchEntries(): array
    {
        return EventRegistration::query()
            ->with('event')
            ->where('member_id', $this->record->id)
            ->whereIn('status', [
                EventRegistrationStatus::Registered->value,
                EventRegistrationStatus::Confirmed->value,
            ])
            ->latest('registered_at')
            ->limit(10)
            ->get()
            ->all();
    }

    public static function sendWelcomeTo(Member $member): void
    {
        $user = $member->user;
        if (! $user) {
            return;
        }

        try {
            $token = Password::broker()->createToken($user);
            $setupUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], absolute: false));

            Mail::to($user->email, $user->name)->send(new MemberWelcomeInvite(
                user: $user,
                setupUrl: $setupUrl,
                firstName: $member->first_name ?: null,
            ));

            Notification::make()->success()->title('Welcome email sent')->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()
                ->title('Could not send welcome email')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function paymentStatusLabel(PaymentStatus $status): string
    {
        return $status->label();
    }
}
