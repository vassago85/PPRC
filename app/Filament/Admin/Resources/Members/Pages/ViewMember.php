<?php

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Enums\EventRegistrationStatus;
use App\Enums\MemberStanding;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Actions\ResendMembershipPaymentRequestAction;
use App\Filament\Admin\Resources\Members\MemberResource;
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
        $this->record->load(['user', 'linkedAdult', 'memberships.membershipType', 'memberships.payments']);
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->fullName();
    }

    public function getBreadcrumbs(): array
    {
        return [
            MemberResource::getUrl('index') => 'Members',
            $this->record->fullName(),
        ];
    }

    /**
     * State-aware header actions. Reads what the member is currently waiting
     * on and offers the two or three things that make sense for that state,
     * with everything else demoted to the ⋯ group in the view.
     */
    protected function getHeaderActions(): array
    {
        $actions = [];

        $standing = $this->record->standing();

        if (in_array($standing, [MemberStanding::AwaitingChoice, MemberStanding::AwaitingPayment], true)) {
            $actions[] = ResendMembershipPaymentRequestAction::forMember();
        }

        if ($standing === MemberStanding::AwaitingEmail && $this->record->user) {
            $actions[] = Action::make('resend_welcome')
                ->label('Resend welcome')
                ->icon('heroicon-o-envelope')
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
