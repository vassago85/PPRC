<?php

namespace App\Filament\Admin\Pages;

use App\Enums\MemberLifecycle;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Mail\MemberWelcomeInvite;
use App\Models\EmailLog;
use App\Models\Member;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
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
    public string $stage = 'all';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('members.view');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Onboarding';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $count = Member::query()->needsOnboarding()->count();
        $awaiting = Member::query()->awaitingEmail()->count();

        return "{$count} members mid-onboarding · {$awaiting} still to confirm email";
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

        if ($this->stage === 'all' || $this->stage === 'abandoned') {
            return $members;
        }

        return $members->filter(fn (Member $m) => $m->currentOnboardingStage() === $this->stage)->values();
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
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
