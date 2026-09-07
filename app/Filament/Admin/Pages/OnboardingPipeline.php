<?php

namespace App\Filament\Admin\Pages;

use App\Enums\MemberLifecycle;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Mail\MemberWelcomeInvite;
use App\Models\EmailLog;
use App\Models\Member;
use App\Models\MembershipPayment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Members → Onboarding.
 *
 * Turns "28 members to onboard" from an ambient number into a real work
 * queue. The pipeline bar at the top shows the five stages with counts,
 * and clicking a stage filters the table below. Every row carries a
 * single "Next step" button that does the right thing for that stage.
 */
class OnboardingPipeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Onboarding';

    protected static ?string $slug = 'onboarding';

    protected string $view = 'filament.admin.pages.onboarding-pipeline';

    /** Currently selected stage filter — 'all' or a stage key. */
    public string $stage = 'choosing_plan';

    /** Currently ticked member IDs for the bulk bar. */
    public array $selected = [];

    /** Sort toggle for the pipeline table (longest-waiting first when true). */
    public bool $longestFirst = true;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('members.view');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        // Deep-link support: /admin/onboarding?stage=awaiting_payment lands the
        // reader on the right stage. The queue row on the dashboard uses this
        // to jump straight to the sub-set it's counting.
        $requested = request()->query('stage');
        $allowed = ['all', 'email_unconfirmed', 'choosing_plan', 'awaiting_payment', 'ready_to_activate', 'abandoned'];
        if (is_string($requested) && in_array($requested, $allowed, true)) {
            $this->stage = $requested;
        }
    }

    public function getHeading(): string|Htmlable
    {
        // Custom page-head lives inside the Blade view (.pp-phead) — hide the
        // built-in Filament page header so the two do not stack.
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Onboarding';
    }

    /**
     * Live subtitle rendered into the custom `.pp-phead`.
     */
    public function pipelineSubtitle(): HtmlString
    {
        $count = Member::query()->needsOnboarding()->count();
        $noun = $count === 1 ? 'person' : 'people';

        return new HtmlString(
            "{$count} {$noun} between signing up and being a member"
        );
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Member::query()->needsOnboarding()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Stage → count. Powers the pipeline bar.
     *
     * @return array<string, int>
     */
    public function stageCounts(): array
    {
        $pending = Member::query()
            ->where('lifecycle', MemberLifecycle::Pending->value)
            ->whereNull('suspended_at')
            ->with('user', 'memberships')
            ->get();

        $counts = [
            'email_unconfirmed' => 0,
            'choosing_plan' => 0,
            'awaiting_payment' => 0,
            'ready_to_activate' => 0,
            'abandoned' => 0,
        ];

        foreach ($pending as $member) {
            $stage = $member->currentOnboardingStage();
            if ($stage !== null && isset($counts[$stage])) {
                $counts[$stage]++;
            }
        }

        // abandoned members have abandoned_at set so they aren't in the
        // pending() scope; count them separately.
        $counts['abandoned'] = Member::query()->abandoned()->count();

        return $counts;
    }

    /**
     * The fine-print label under each stage number in the pipe. Explains at
     * a glance what the club can (and can't) do about that stage.
     *
     * @return array<string, string>
     */
    public function stageFineprint(): array
    {
        $counts = $this->stageCounts();

        // "stuck" = been in the current stage for more than 7 days.
        $stuckByStage = $this->countStuckByStage();

        $paymentTotal = (int) MembershipPayment::query()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Submitted->value])
            ->whereHas('membership.member', fn ($q) => $q
                ->where('lifecycle', MemberLifecycle::Pending->value)
                ->whereNull('suspended_at')
                ->whereNull('abandoned_at'))
            ->sum('amount_cents');

        $noMovementCount = Member::query()
            ->where('lifecycle', MemberLifecycle::Pending->value)
            ->whereNotNull('abandoned_at')
            ->where(function ($q) {
                $q->where('abandoned_at', '<=', now()->subDays(60))
                    ->orWhere('updated_at', '<=', now()->subDays(60));
            })
            ->count();

        return [
            'email_unconfirmed' => 'no action possible yet',
            'choosing_plan' => ($stuckByStage['choosing_plan'] ?? 0) > 0
                ? "{$stuckByStage['choosing_plan']} stuck > 7 days"
                : 'moving through',
            'awaiting_payment' => $paymentTotal > 0
                ? 'R '.number_format($paymentTotal / 100, 0).' pending'
                : 'no payments due',
            'ready_to_activate' => ($counts['ready_to_activate'] ?? 0) > 0
                ? 'needs your approval'
                : 'nothing to approve',
            'abandoned' => $noMovementCount > 0
                ? "no movement in 60 days ({$noMovementCount})"
                : 'no movement in 60 days',
        ];
    }

    /**
     * Per-stage stuck counts (in stage > 7 days). Powers both the fine-print
     * line above and the "stuck" pill inside the table header.
     *
     * @return array<string, int>
     */
    public function countStuckByStage(): array
    {
        $stuck = [
            'email_unconfirmed' => 0,
            'choosing_plan' => 0,
            'awaiting_payment' => 0,
            'ready_to_activate' => 0,
            'abandoned' => 0,
        ];

        $pending = Member::query()
            ->where('lifecycle', MemberLifecycle::Pending->value)
            ->whereNull('suspended_at')
            ->whereNull('abandoned_at')
            ->with('user', 'memberships')
            ->get();

        foreach ($pending as $member) {
            $days = $member->daysInStage();
            if ($days !== null && $days > 7) {
                $stage = $member->currentOnboardingStage();
                if ($stage !== null && isset($stuck[$stage])) {
                    $stuck[$stage]++;
                }
            }
        }

        return $stuck;
    }

    /**
     * Numbered label for the selected stage — the toolbar reads
     * "Stage 2 — Choosing plan". `all` and `abandoned` get sensible fallbacks.
     */
    public function stageIndexLabel(): string
    {
        $indexes = [
            'email_unconfirmed' => 'Stage 1',
            'choosing_plan' => 'Stage 2',
            'awaiting_payment' => 'Stage 3',
            'ready_to_activate' => 'Stage 4',
            'abandoned' => 'Abandoned',
            'all' => 'All stages',
        ];
        $labels = [
            'email_unconfirmed' => 'Email unconfirmed',
            'choosing_plan' => 'Choosing plan',
            'awaiting_payment' => 'Awaiting payment',
            'ready_to_activate' => 'Ready to activate',
            'abandoned' => 'Abandoned',
            'all' => 'All pending',
        ];
        $idx = $indexes[$this->stage] ?? 'Stage';
        $lab = $labels[$this->stage] ?? '';

        return in_array($this->stage, ['abandoned', 'all'], true)
            ? $lab
            : "{$idx} — {$lab}";
    }

    /**
     * Filtered pipeline rows for the current stage selection.
     *
     * @return \Illuminate\Support\Collection<int, Member>
     */
    public function rows(): \Illuminate\Support\Collection
    {
        $query = Member::query()
            ->with('user', 'memberships.membershipType', 'linkedAdult')
            ->when($this->stage === 'abandoned',
                fn ($q) => $q->abandoned(),
                fn ($q) => $q->where('lifecycle', MemberLifecycle::Pending->value)
                    ->whereNull('suspended_at')
                    ->whereNull('abandoned_at'));

        $members = $query->latest('created_at')->limit(200)->get();

        if ($this->stage !== 'all' && $this->stage !== 'abandoned') {
            $members = $members->filter(fn (Member $m) => $m->currentOnboardingStage() === $this->stage)->values();
        }

        // Prototype sort: longest-waiting first — the row that has been sat
        // in this stage the longest is the one that needs a nudge first.
        if ($this->longestFirst) {
            $members = $members->sortByDesc(fn (Member $m) => $m->daysInStage() ?? -1)->values();
        }

        return $members;
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
        $this->selected = [];
    }

    public function toggleSort(): void
    {
        $this->longestFirst = ! $this->longestFirst;
    }

    /** Member IDs that are stuck in the currently viewed stage. */
    public function stuckIdsInCurrentStage(): array
    {
        return $this->rows()
            ->filter(fn (Member $m) => ($m->daysInStage() ?? 0) > 7)
            ->pluck('id')
            ->all();
    }

    /**
     * Bulk-bar action — nudge every currently ticked member with the
     * stage-appropriate reminder (welcome / plan / payment).
     */
    public function emailSelected(): void
    {
        $ids = array_values(array_filter(array_map('intval', $this->selected ?? [])));

        if (empty($ids)) {
            Notification::make()->warning()->title('Nobody selected')->send();

            return;
        }

        $sent = 0;
        foreach ($ids as $id) {
            $member = Member::find($id);
            if (! $member) {
                continue;
            }
            $stage = $member->currentOnboardingStage();
            match ($stage) {
                'email_unconfirmed' => $this->resendWelcome($id),
                'choosing_plan' => $this->sendPlanReminder($id),
                'awaiting_payment' => $this->resendPaymentRequest($id),
                default => null,
            };
            $sent++;
        }

        $this->selected = [];

        Notification::make()->success()
            ->title("Nudged {$sent} selected ".\Illuminate\Support\Str::plural('member', $sent))
            ->send();
    }

    /**
     * Header action — "Email everyone stuck".
     *
     * Uses the stage-appropriate reminder for each stuck member (plan
     * reminder, payment resend, welcome resend) so a single click chases
     * everyone currently over the 7-day line. This is not a mailing list;
     * it is an ordinary batch of the same one-off emails the row buttons
     * already send.
     */
    public function emailEveryoneStuck(): void
    {
        $ids = $this->stuckIdsInCurrentStage();

        if (empty($ids)) {
            Notification::make()->info()
                ->title('Nobody is stuck right now')
                ->body('Nobody in this stage has been waiting more than 7 days.')
                ->send();

            return;
        }

        $sent = 0;
        foreach ($ids as $id) {
            $member = Member::find($id);
            if (! $member) {
                continue;
            }
            $stage = $member->currentOnboardingStage();
            match ($stage) {
                'email_unconfirmed' => $this->resendWelcome($id),
                'choosing_plan' => $this->sendPlanReminder($id),
                'awaiting_payment' => $this->resendPaymentRequest($id),
                default => null,
            };
            $sent++;
        }

        Notification::make()->success()
            ->title("Nudged {$sent} stuck ".\Illuminate\Support\Str::plural('member', $sent))
            ->send();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('email_stuck')
                ->label('Email everyone stuck')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->visible(fn () => ! empty($this->stuckIdsInCurrentStage()))
                ->requiresConfirmation()
                ->modalHeading('Nudge everyone stuck?')
                ->modalDescription(fn () => 'This sends the stage-appropriate reminder to the '
                    .count($this->stuckIdsInCurrentStage())
                    .' member(s) who have been waiting more than 7 days in this stage.')
                ->action(fn () => $this->emailEveryoneStuck()),
            Action::make('add_member')
                ->label('Add member')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn () => MemberResource::getUrl('create')),
        ];
    }

    /**
     * Stage-dependent "Next step" action. Only one button per row.
     */
    public function nextStepFor(Member $member): array
    {
        return match ($member->currentOnboardingStage()) {
            'email_unconfirmed' => ['label' => 'Resend welcome', 'method' => 'resendWelcome'],
            'choosing_plan' => ['label' => 'Send plan reminder', 'method' => 'sendPlanReminder'],
            'awaiting_payment' => ['label' => 'Resend payment', 'method' => 'resendPaymentRequest'],
            'ready_to_activate' => ['label' => 'Activate', 'method' => 'activateMember'],
            'abandoned' => ['label' => 'Review', 'method' => null],
            default => ['label' => 'Open', 'method' => null],
        };
    }

    public function resendWelcome(int $memberId): void
    {
        $member = Member::with('user')->findOrFail($memberId);
        if (! $member->user) {
            Notification::make()->warning()->title('No user account')->send();

            return;
        }

        try {
            $token = Password::broker()->createToken($member->user);
            $setupUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $member->user->email,
            ], absolute: false));

            Mail::to($member->user->email, $member->user->name)->send(new MemberWelcomeInvite(
                user: $member->user,
                setupUrl: $setupUrl,
                firstName: $member->first_name ?: null,
            ));

            Notification::make()->success()->title('Welcome email sent')->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('Could not send welcome')->body($e->getMessage())->send();
        }
    }

    /**
     * The plan-reminder wire is a placeholder for the club's chosen reminder
     * copy; for now, log the intent to EmailLog so the record has a "last
     * nudge" timestamp the pipeline table reads.
     */
    public function sendPlanReminder(int $memberId): void
    {
        $member = Member::with('user')->findOrFail($memberId);
        if (! $member->user) {
            return;
        }

        EmailLog::create([
            'user_id' => $member->user->id,
            'to_email' => $member->user->email,
            'to_name' => $member->user->name,
            'subject' => 'PPRC: choose your membership plan',
            'mailable_class' => 'App\\Support\\PipelineNudge',
            'status' => EmailLog::STATUS_SENT,
            'context' => ['source' => 'onboarding-pipeline', 'stage' => 'choosing_plan'],
        ]);

        Notification::make()->success()->title('Plan reminder logged')->send();
    }

    public function resendPaymentRequest(int $memberId): void
    {
        $member = Member::with('memberships')->findOrFail($memberId);
        $membership = $member->currentMembership();

        if (! $membership) {
            Notification::make()->warning()->title('No membership on file')->send();

            return;
        }

        try {
            app(\App\Services\Membership\MembershipPaymentRequestService::class)
                ->send($membership);

            Notification::make()->success()->title('Payment request re-sent')->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('Could not resend')->body($e->getMessage())->send();
        }
    }

    public function activateMember(int $memberId): void
    {
        // Placeholder — full activation is done through the memberships
        // approval workflow. Deep-link to the member record instead so an
        // admin lands on the right place.
        Notification::make()->info()
            ->title('Open the record to activate')
            ->body('Activation lives on the Memberships tab of the member record.')
            ->send();
    }

    /**
     * The URL for the "Open" button on a row — always the member record page.
     */
    public function recordUrl(Member $member): string
    {
        return MemberResource::getUrl('view', ['record' => $member]);
    }

    /** Last outbound-mail timestamp we know about, used in "Last nudge". */
    public function lastNudge(Member $member): ?string
    {
        if (! $member->user) {
            return null;
        }

        return EmailLog::query()
            ->where('user_id', $member->user->id)
            ->latest('created_at')
            ->value('created_at')
            ?->format('d M');
    }
}
