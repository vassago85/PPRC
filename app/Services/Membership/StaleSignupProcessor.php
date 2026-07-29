<?php

namespace App\Services\Membership;

use App\Mail\FinishSignupReminderMail;
use App\Models\EmailLog;
use App\Models\Member;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Handles incomplete signups that have gone stale.
 *
 * Three cohorts are targeted:
 *   - "verify"  : registered but never confirmed their email
 *   - "choose"  : verified but never started a membership application (no
 *                 membership row at all)
 *   - "pay"     : chose a membership but never paid for it
 *
 * The first two are only stale after months of silence. The "pay" cohort is
 * chased on a shorter, gentler clock (30 days by default): they engaged, the
 * club just never saw the money, so a month-old unpaid application is already
 * worth a nudge.
 *
 * Policy is nudge-then-archive: the first time we see a stale account we email
 * a single "finish your signup" reminder and stamp signup_reminder_sent_at.
 * If, after the grace window, they still haven't progressed, we stamp
 * abandoned_at, which drops them out of the onboarding inbox while leaving
 * their lifecycle position alone. It is fully reversible — verifying their
 * email, starting an application or paying clears the stamp (see MemberService
 * / MembershipIssuer / MemberService::activate).
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
        ?int $unpaidDays = null,
        int $limit = 0,
        int $sleepSeconds = 0,
        ?Closure $log = null,
    ): array {
        $months = $months ?? (int) config('membership.stale_signup_months', 6);
        $graceDays = $graceDays ?? (int) config('membership.stale_signup_grace_days', 14);
        $unpaidDays = $unpaidDays ?? (int) config('membership.stale_unpaid_signup_days', 30);
        $log ??= fn (string $line) => null;

        $staleCutoff = Carbon::now()->subMonths(max(1, $months));
        $unpaidCutoff = Carbon::now()->subDays(max(1, $unpaidDays));
        $graceCutoff = Carbon::now()->subDays(max(0, $graceDays));

        $candidates = $this->candidates($staleCutoff, $unpaidCutoff);

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
                        $member->update(['abandoned_at' => now()]);
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
                    $member->update(['abandoned_at' => now()]);
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
     * The three scopes are mutually exclusive by definition — a member is
     * either unverified, or verified with no membership, or has an unpaid
     * membership — so nobody appears twice.
     *
     * @return array<int, array{member:Member,variant:string}>
     */
    protected function candidates(Carbon $staleCutoff, Carbon $unpaidCutoff): array
    {
        $pairs = [];

        foreach (Member::query()->with('user')->staleUnverifiedSignups($staleCutoff)->get() as $m) {
            $pairs[] = ['member' => $m, 'variant' => 'verify'];
        }

        foreach (Member::query()->with('user')->staleUnstartedSignups($staleCutoff)->get() as $m) {
            $pairs[] = ['member' => $m, 'variant' => 'choose'];
        }

        foreach (Member::query()->with('user')->staleUnpaidSignups($unpaidCutoff)->get() as $m) {
            $pairs[] = ['member' => $m, 'variant' => 'pay'];
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
                'subject' => match ($variant) {
                    'verify' => 'Finish setting up your PPRC account',
                    'pay' => 'Complete your PPRC membership payment',
                    default => 'One step left to join PPRC',
                },
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
