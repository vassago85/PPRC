<?php

namespace App\Services\Membership;

use App\Enums\MemberStatus;
use App\Mail\FinishSignupReminderMail;
use App\Models\EmailLog;
use App\Models\Member;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Handles incomplete signups that have gone stale.
 *
 * Two cohorts are targeted:
 *   - "verify"  : registered but never confirmed their email (Unverified)
 *   - "choose"  : verified but never started a membership application (Pending
 *                 with no membership row)
 *
 * Policy is nudge-then-archive: the first time we see a stale account we email
 * a single "finish your signup" reminder and stamp signup_reminder_sent_at.
 * If, after the grace window, they still haven't progressed, we move them to
 * the Abandoned status. It is fully reversible — verifying their email or
 * starting an application returns them to Pending (see MemberService /
 * MembershipIssuer).
 */
class StaleSignupProcessor
{
    /**
     * @return array{candidates:int,nudged:int,archived:int,skipped:int,failed:int}
     */
    public function process(
        bool $dryRun = false,
        ?int $months = null,
        ?int $graceDays = null,
        int $limit = 0,
        int $sleepSeconds = 0,
        ?Closure $log = null,
    ): array {
        $months = $months ?? (int) config('membership.stale_signup_months', 6);
        $graceDays = $graceDays ?? (int) config('membership.stale_signup_grace_days', 14);
        $log ??= fn (string $line) => null;

        $staleCutoff = Carbon::now()->subMonths(max(1, $months));
        $graceCutoff = Carbon::now()->subDays(max(0, $graceDays));

        $candidates = $this->candidates($staleCutoff);

        $stats = [
            'candidates' => count($candidates),
            'nudged' => 0,
            'archived' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($candidates as ['member' => $member, 'variant' => $variant]) {
            if ($limit > 0 && ($stats['nudged'] + $stats['archived']) >= $limit) {
                $log("Hit limit of {$limit}; stopping this run.");
                break;
            }

            $email = strtolower(trim((string) $member->user?->email));
            $label = sprintf('%s  %-28s  %s', strtoupper($variant), substr($member->fullName(), 0, 28), $email ?: '(no email)');

            // Already nudged and the grace window has elapsed → archive.
            if ($member->signup_reminder_sent_at !== null) {
                if ($member->signup_reminder_sent_at->lessThanOrEqualTo($graceCutoff)) {
                    if (! $dryRun) {
                        $member->update(['status' => MemberStatus::Abandoned]);
                    }
                    $stats['archived']++;
                    $log(($dryRun ? '[DRY] ' : '').'[ARCHIVE] '.$label);
                } else {
                    $stats['skipped']++;
                    $log('[WAIT]    '.$label.'  (nudged recently)');
                }

                continue;
            }

            // Never nudged. If we have no email we can't nudge — archive directly.
            if ($email === '') {
                if (! $dryRun) {
                    $member->update(['status' => MemberStatus::Abandoned]);
                }
                $stats['archived']++;
                $log(($dryRun ? '[DRY] ' : '').'[ARCHIVE] '.$label.'  (no email to nudge)');

                continue;
            }

            if ($dryRun) {
                $stats['nudged']++;
                $log('[DRY] [NUDGE]  '.$label);

                continue;
            }

            if ($this->sendNudge($member, $variant, $email)) {
                $stats['nudged']++;
                $log('[NUDGE]   '.$label);
            } else {
                $stats['failed']++;
                $log('[FAIL]    '.$label);
            }

            if ($sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        return $stats;
    }

    /**
     * The stale cohorts as [member, variant] pairs. Kept as plain arrays so we
     * never accidentally persist a transient "variant" onto the model.
     *
     * @return array<int, array{member:Member,variant:string}>
     */
    protected function candidates(Carbon $staleCutoff): array
    {
        $pairs = [];

        foreach (Member::query()->with('user')->staleUnverifiedSignups($staleCutoff)->get() as $m) {
            $pairs[] = ['member' => $m, 'variant' => 'verify'];
        }

        foreach (Member::query()->with('user')->staleUnstartedSignups($staleCutoff)->get() as $m) {
            $pairs[] = ['member' => $m, 'variant' => 'choose'];
        }

        return $pairs;
    }

    protected function sendNudge(Member $member, string $variant, string $email): bool
    {
        try {
            Mail::to($email, $member->fullName())
                ->send(new FinishSignupReminderMail($member, $variant));

            $member->forceFill(['signup_reminder_sent_at' => now()])->saveQuietly();

            EmailLog::create([
                'user_id' => $member->user_id,
                'to_email' => $email,
                'to_name' => $member->fullName(),
                'subject' => $variant === 'verify'
                    ? 'Finish setting up your PPRC account'
                    : 'One step left to join PPRC',
                'mailable_class' => FinishSignupReminderMail::class,
                'status' => EmailLog::STATUS_SENT,
                'sent_at' => now(),
                'context' => ['variant' => $variant, 'member_id' => $member->id],
            ]);

            return true;
        } catch (\Throwable $e) {
            EmailLog::create([
                'user_id' => $member->user_id,
                'to_email' => $email,
                'to_name' => $member->fullName(),
                'subject' => 'Finish your PPRC signup',
                'mailable_class' => FinishSignupReminderMail::class,
                'status' => EmailLog::STATUS_FAILED,
                'error' => $e->getMessage(),
                'context' => ['variant' => $variant, 'member_id' => $member->id],
            ]);

            return false;
        }
    }
}
