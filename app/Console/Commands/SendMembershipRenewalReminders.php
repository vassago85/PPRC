<?php

namespace App\Console\Commands;

use App\Enums\MemberStatus;
use App\Mail\MembershipRenewalReminderMail;
use App\Models\EmailLog;
use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Sends renewal reminders to members whose membership is about to expire
 * (Active + expiry_date inside --before-days) and to members who recently
 * lapsed (Expired + expiry_date inside --lapsed-days). Skips Suspended,
 * Resigned, Inactive (>6mo lapsed), Unverified, Pending and any member
 * without a valid email address.
 *
 * The mail body includes a signed self-cancellation link so members can
 * opt out without contacting the secretary.
 *
 * Designed to be invoked from the daily scheduler. Throttled per-member
 * by last_renewal_reminder_at so nobody gets repeated nags.
 *
 *   php artisan members:send-renewal-reminders --dry-run
 *   php artisan members:send-renewal-reminders --before-days=30 --throttle-days=14
 *   php artisan members:send-renewal-reminders --email=paul@example.com --resend
 *   php artisan members:send-renewal-reminders --limit=100 --sleep=2
 */
class SendMembershipRenewalReminders extends Command
{
    protected $signature = 'members:send-renewal-reminders
        {--before-days=30 : Send to active members whose expiry is within this many days from today.}
        {--lapsed-days=180 : Send to expired members whose expiry was within this many days in the past.}
        {--throttle-days=14 : Skip a member if a reminder was sent in the last N days. Use 0 to disable.}
        {--email=* : Only send to these specific email addresses (bypasses status / window filters).}
        {--limit=0 : Maximum emails to send this run (0 = no limit).}
        {--sleep=0 : Seconds to sleep between sends (use to dodge mail-provider rate limits).}
        {--resend : Send even if the throttle window has not elapsed.}
        {--dry-run : Resolve recipients and print what would be sent, without sending.}';

    protected $description = 'Email renewal reminders to members whose membership is expiring soon or has lapsed';

    public function handle(): int
    {
        $beforeDays = max(1, (int) $this->option('before-days'));
        $lapsedDays = max(1, (int) $this->option('lapsed-days'));
        $throttleDays = max(0, (int) $this->option('throttle-days'));
        $emails = array_values(array_filter(array_map('strtolower', (array) $this->option('email'))));
        $limit = max(0, (int) $this->option('limit'));
        $sleepSeconds = max(0, (int) $this->option('sleep'));
        $resend = (bool) $this->option('resend');
        $dryRun = (bool) $this->option('dry-run');

        $today = Carbon::today();
        $expiringCutoff = $today->copy()->addDays($beforeDays);
        $lapsedCutoff = $today->copy()->subDays($lapsedDays);
        $throttleCutoff = $throttleDays > 0 ? Carbon::now()->subDays($throttleDays) : null;

        $query = Member::query()
            ->with('user')
            ->whereNotNull('expiry_date')
            ->whereNull('resigned_at');

        if ($emails !== []) {
            // Targeted send — bypass the status/window filter, still requires a valid linked user email.
            $query->whereHas('user', fn ($q) => $q->whereIn('email', $emails));
        } else {
            $query->where(function ($q) use ($today, $expiringCutoff, $lapsedCutoff) {
                // Almost-expired bucket: active + expiry within the look-ahead window.
                $q->where(function ($qq) use ($today, $expiringCutoff) {
                    $qq->where('status', MemberStatus::Active->value)
                       ->whereBetween('expiry_date', [$today, $expiringCutoff]);
                })
                // Recently lapsed bucket: expired status + expiry within the look-back window.
                ->orWhere(function ($qq) use ($today, $lapsedCutoff) {
                    $qq->where('status', MemberStatus::Expired->value)
                       ->whereBetween('expiry_date', [$lapsedCutoff, $today->copy()->subDay()]);
                });
            });

            if (! $resend && $throttleCutoff !== null) {
                $query->where(function ($q) use ($throttleCutoff) {
                    $q->whereNull('last_renewal_reminder_at')
                      ->orWhere('last_renewal_reminder_at', '<', $throttleCutoff);
                });
            }
        }

        $members = $query->get();
        $total = $members->count();

        if ($total === 0) {
            $this->info('No members match the reminder criteria. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info("Resolved {$total} member(s) for renewal reminders.");
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($members as $member) {
            if ($limit > 0 && $sent >= $limit) {
                $this->warn("Hit --limit={$limit}; stopping for this run.");

                break;
            }

            $email = strtolower(trim((string) $member->user?->email));
            if ($email === '') {
                $this->line("  - {$member->membership_number} {$member->fullName()} → skipped (no email).");
                $skipped++;

                continue;
            }

            $expiry = Carbon::parse($member->expiry_date)->startOfDay();
            $isLapsed = $expiry->lt($today);
            $variant = $isLapsed ? 'lapsed' : 'expiring';
            $days = $isLapsed
                ? max(1, $today->diffInDays($expiry))
                : max(1, $today->diffInDays($expiry, false));

            $tag = $isLapsed ? 'LAPSED ' : 'EXPIRES';
            $line = sprintf(
                '  %s  %s  %-25s  %s  (%s)',
                $tag,
                str_pad((string) $days, 3, ' ', STR_PAD_LEFT).'d',
                substr($member->fullName(), 0, 25),
                $email,
                $member->membership_number ?? '—',
            );

            if ($dryRun) {
                $this->line('[DRY RUN]'.$line);
                $sent++;

                continue;
            }

            try {
                Mail::to($email, $member->fullName())
                    ->send(new MembershipRenewalReminderMail($member, $variant, $days));

                $member->forceFill(['last_renewal_reminder_at' => now()])->saveQuietly();

                EmailLog::create([
                    'user_id' => $member->user_id,
                    'to_email' => $email,
                    'to_name' => $member->fullName(),
                    'subject' => $isLapsed
                        ? 'Your PPRC membership has lapsed — renew when you\'re ready'
                        : 'Your PPRC membership is about to expire',
                    'mailable_class' => MembershipRenewalReminderMail::class,
                    'status' => EmailLog::STATUS_SENT,
                    'sent_at' => now(),
                    'context' => [
                        'variant' => $variant,
                        'days' => $days,
                        'expiry_date' => $member->expiry_date?->toDateString(),
                        'member_id' => $member->id,
                    ],
                ]);

                $this->line('[SENT]   '.$line);
                $sent++;
            } catch (\Throwable $e) {
                $this->error('[FAIL]   '.$line.'  → '.$e->getMessage());
                $failed++;

                EmailLog::create([
                    'user_id' => $member->user_id,
                    'to_email' => $email,
                    'to_name' => $member->fullName(),
                    'subject' => 'Renewal reminder',
                    'mailable_class' => MembershipRenewalReminderMail::class,
                    'status' => EmailLog::STATUS_FAILED,
                    'error' => $e->getMessage(),
                    'context' => ['variant' => $variant, 'member_id' => $member->id],
                ]);
            }

            if ($sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        $this->newLine();
        $this->table(['action', 'count'], [
            [$dryRun ? 'Would-send' : 'Sent', $sent],
            ['Skipped', $skipped],
            ['Failed', $failed],
        ]);

        return self::SUCCESS;
    }
}
