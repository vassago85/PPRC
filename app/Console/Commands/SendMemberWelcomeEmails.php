<?php

namespace App\Console\Commands;

use App\Mail\MemberWelcomeInvite;
use App\Models\EmailLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * Sends the "Welcome to PPRC — claim your account" invite to imported members
 * so they can set their own password. Designed to be run by a committee admin
 * at launch (not during nightly cron).
 *
 *   php artisan members:send-welcome --status=active --dry-run
 *   php artisan members:send-welcome --status=active
 *   php artisan members:send-welcome --status=active --status=pending
 *   php artisan members:send-welcome --email=paul@example.com
 *   php artisan members:send-welcome --resend            # override idempotency
 *   php artisan members:send-welcome --limit=10          # batch cautiously
 *
 * Safety rails:
 *   - Only targets Members (skips staff Users without a member profile).
 *   - Defaults to status=active. expired/inactive/pending/resigned must be
 *     explicitly opted in with --status, so we can never "accidentally mass
 *     invite former members" by running it without flags.
 *   - Idempotent via email_logs: skips anyone who already has a sent
 *     MemberWelcomeInvite row unless --resend is passed.
 */
class SendMemberWelcomeEmails extends Command
{
    protected $signature = 'members:send-welcome
        {--status=active* : Member status(es) to include. Repeatable. Defaults to active only.}
        {--email=* : Only send to these specific email addresses (bypasses --status filter)}
        {--resend : Send even if a welcome has already been logged for this address}
        {--limit=0 : Maximum emails to send this run (0 = no limit)}
        {--dry-run : Resolve recipients and print what would be sent, without sending}';

    protected $description = 'Send the member welcome / account-claim invite to imported members';

    public function handle(): int
    {
        /** @var array<int, string> $statuses */
        $statuses = array_values(array_filter((array) $this->option('status')));
        /** @var array<int, string> $emails */
        $emails = array_values(array_filter(array_map('strtolower', (array) $this->option('email'))));
        $resend = (bool) $this->option('resend');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $query = User::query()
            ->whereHas('member', function ($q) use ($statuses, $emails) {
                if (empty($emails) && ! empty($statuses)) {
                    $q->whereIn('status', $statuses);
                }
            })
            ->with('member');

        if (! empty($emails)) {
            $query->whereIn(DB::raw('lower(email)'), $emails);
        }

        $recipients = $query->orderBy('id')->get();

        if ($recipients->isEmpty()) {
            $this->warn('No members matched the given filters — nothing to send.');

            return self::SUCCESS;
        }

        $this->info("Matched {$recipients->count()} member(s).");
        if ($limit > 0) {
            $this->info("Limit: {$limit}");
        }
        if ($dryRun) {
            $this->warn('Dry run — no emails will be sent.');
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($recipients as $user) {
            if ($limit > 0 && $sent >= $limit) {
                break;
            }

            if (! $resend && $this->hasAlreadyBeenWelcomed($user->email)) {
                $this->line("  skip  {$user->email} — welcome already sent");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("  would send  {$user->email}");
                $sent++;

                continue;
            }

            try {
                $setupUrl = $this->buildPasswordSetupUrl($user);

                Mail::to($user->email, $user->name)->send(new MemberWelcomeInvite(
                    user: $user,
                    setupUrl: $setupUrl,
                    firstName: $user->member?->first_name ?: null,
                ));

                $this->info("  sent  {$user->email}");
                $sent++;
            } catch (\Throwable $e) {
                $failed++;

                // Log the failure directly — the MessageSent listener never
                // fires when sending throws, so we write a failure row here
                // to keep the audit trail complete.
                EmailLog::create([
                    'user_id' => $user->id,
                    'to_email' => $user->email,
                    'to_name' => $user->name,
                    'subject' => 'Welcome to Pretoria Precision Rifle Club — claim your account',
                    'mailable_class' => MemberWelcomeInvite::class,
                    'status' => EmailLog::STATUS_FAILED,
                    'error' => $e->getMessage(),
                    'context' => ['source' => 'members:send-welcome'],
                ]);

                $this->error("  FAIL  {$user->email} — ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->table(['metric', 'count'], [
            ['sent', $sent],
            ['skipped (already sent)', $skipped],
            ['failed', $failed],
            ['matched total', $recipients->count()],
        ]);

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * True when a MemberWelcomeInvite has already been recorded for this
     * address with status=sent, so we don't re-invite people who've already
     * been contacted (unless --resend is given).
     */
    private function hasAlreadyBeenWelcomed(string $email): bool
    {
        return EmailLog::query()
            ->where('to_email', $email)
            ->where('mailable_class', MemberWelcomeInvite::class)
            ->where('status', EmailLog::STATUS_SENT)
            ->exists();
    }

    /**
     * Generates a first-party Laravel password-reset URL. This reuses Fortify's
     * existing reset flow (route name `password.reset`), so the member lands on
     * the proper "choose a new password" screen — no custom claim flow needed.
     */
    private function buildPasswordSetupUrl(User $user): string
    {
        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);

        return url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], absolute: false));
    }
}
