<?php

namespace App\Console\Commands;

use App\Mail\MemberWelcomeInvite;
use App\Models\EmailLog;
use App\Models\Member;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Import members from a CSV export of the legacy SSMM PPRC WordPress plugin.
 *
 * Expected CSV headers (case-insensitive; extra columns ignored):
 *   email (required, becomes User login)
 *   first_name, last_name, known_as
 *   membership_number
 *   membership_type_slug   (must match a seeded MembershipType.slug; skipped otherwise)
 *   phone_country_code, phone_number
 *   address_line1, address_line2, city, province, postal_code, country
 *   date_of_birth          (YYYY-MM-DD)
 *   status                 (active|pending|suspended|expired|resigned)
 *   join_date, expiry_date (YYYY-MM-DD)
 *   saprf_membership_number
 *   notes
 *   linked_adult_email     (optional; 2nd pass resolves this to linked_adult_member_id)
 *
 * The command is idempotent: re-running with the same file updates the existing
 * User/Member rows matched by email. Use --dry-run to preview counts without
 * writing.
 */
class ImportMembers extends Command
{
    protected $signature = 'members:import
        {path : Absolute or storage-relative path to the CSV file}
        {--dry-run : Parse and validate only; do not write to the database}
        {--default-password=ChangeMe! : Password assigned to brand-new users}
        {--send-welcome : Send a welcome/account-claim email to newly created members}';

    protected $description = 'Import members from a CSV export (SSMM PPRC plugin schema)';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $defaultPassword = (string) $this->option('default-password');
        $sendWelcome = (bool) $this->option('send-welcome');

        $fh = fopen($path, 'r');
        if ($fh === false) {
            $this->error("Cannot open {$path}");

            return self::FAILURE;
        }

        $headerRow = fgetcsv($fh);
        if (! $headerRow) {
            $this->error('CSV appears to be empty.');

            return self::FAILURE;
        }
        $headers = array_map(fn ($h) => Str::of((string) $h)->trim()->lower()->toString(), $headerRow);

        $types = MembershipType::pluck('id', 'slug')->all();

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'warnings' => 0, 'welcomed' => 0];
        $newUsers = [];
        $rowsToLink = [];
        $line = 1;

        DB::beginTransaction();
        try {
            while (($raw = fgetcsv($fh)) !== false) {
                $line++;
                $row = @array_combine($headers, $raw) ?: [];

                $email = trim((string) ($row['email'] ?? ''));
                if ($email === '') {
                    $this->warn("  line {$line}: missing email — skipped");
                    $stats['skipped']++;

                    continue;
                }

                $typeSlug = trim((string) ($row['membership_type_slug'] ?? ''));
                if ($typeSlug !== '' && ! isset($types[$typeSlug])) {
                    $this->warn("  line {$line} ({$email}): unknown membership_type_slug '{$typeSlug}' — recording member without type");
                    $stats['warnings']++;
                }

                $name = trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')) ?: $email;

                if ($dryRun) {
                    $stats['created']++;

                    continue;
                }

                $user = User::firstOrCreate(
                    ['email' => $email],
                    ['name' => $name, 'password' => Hash::make($defaultPassword), 'created_via_import' => true],
                );
                $wasNewUser = $user->wasRecentlyCreated;

                $member = Member::updateOrCreate(
                    ['user_id' => $user->id],
                    array_filter([
                        'first_name' => $row['first_name'] ?? null,
                        'last_name' => $row['last_name'] ?? null,
                        'known_as' => $row['known_as'] ?? null,
                        'membership_number' => $row['membership_number'] ?? null,
                        'phone_country_code' => $row['phone_country_code'] ?? null,
                        'phone_number' => $row['phone_number'] ?? null,
                        'address_line1' => $row['address_line1'] ?? null,
                        'address_line2' => $row['address_line2'] ?? null,
                        'city' => $row['city'] ?? null,
                        'province' => $row['province'] ?? null,
                        'postal_code' => $row['postal_code'] ?? null,
                        'country' => ($row['country'] ?? null) ?: 'South Africa',
                        'date_of_birth' => ($row['date_of_birth'] ?? null) ?: null,
                        'status' => ($row['status'] ?? null) ?: 'active',
                        'join_date' => ($row['join_date'] ?? null) ?: null,
                        'expiry_date' => ($row['expiry_date'] ?? null) ?: null,
                        'saprf_membership_number' => $row['saprf_membership_number'] ?? null,
                        'notes' => $row['notes'] ?? null,
                    ], fn ($v) => $v !== null && $v !== ''),
                );

                $wasNewUser ? $stats['created']++ : $stats['updated']++;

                if ($wasNewUser && $sendWelcome) {
                    $newUsers[] = ['user' => $user, 'member' => $member];
                }

                if (! empty($row['linked_adult_email'])) {
                    $rowsToLink[] = ['member_id' => $member->id, 'linked_adult_email' => trim($row['linked_adult_email'])];
                }
            }
            fclose($fh);

            foreach ($rowsToLink as $link) {
                $adult = Member::query()
                    ->whereHas('user', fn ($q) => $q->where('email', $link['linked_adult_email']))
                    ->first();
                if ($adult) {
                    Member::where('id', $link['member_id'])->update(['linked_adult_member_id' => $adult->id]);
                } else {
                    $this->warn("  linked_adult_email not found: {$link['linked_adult_email']}");
                    $stats['warnings']++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('Dry run complete — nothing written.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Import failed on line {$line}: ".$e->getMessage());

            return self::FAILURE;
        }

        if ($sendWelcome && ! $dryRun && ! empty($newUsers)) {
            $this->info("Sending welcome emails to {$stats['created']} new member(s)...");
            foreach ($newUsers as $item) {
                try {
                    $token = Password::broker()->createToken($item['user']);
                    $setupUrl = url(route('password.reset', [
                        'token' => $token,
                        'email' => $item['user']->email,
                    ], absolute: false));

                    Mail::to($item['user']->email, $item['user']->name)
                        ->send(new MemberWelcomeInvite(
                            user: $item['user'],
                            setupUrl: $setupUrl,
                            firstName: $item['member']->first_name ?: null,
                        ));
                    $stats['welcomed']++;
                } catch (\Throwable $e) {
                    EmailLog::create([
                        'user_id' => $item['user']->id,
                        'to_email' => $item['user']->email,
                        'to_name' => $item['user']->name,
                        'subject' => 'Welcome to Pretoria Precision Rifle Club — claim your account',
                        'mailable_class' => MemberWelcomeInvite::class,
                        'status' => EmailLog::STATUS_FAILED,
                        'error' => $e->getMessage(),
                        'context' => ['source' => 'members:import --send-welcome'],
                    ]);
                    $this->error("  FAIL  {$item['user']->email} — {$e->getMessage()}");
                    $stats['warnings']++;
                }
            }
        }

        $this->newLine();
        $rows = [
            ['created', $stats['created']],
            ['updated', $stats['updated']],
            ['skipped', $stats['skipped']],
            ['warnings', $stats['warnings']],
        ];
        if ($sendWelcome) {
            $rows[] = ['welcome emails sent', $stats['welcomed']];
        }
        $this->table(['metric', 'count'], $rows);

        if (! $dryRun) {
            $this->info('Recomputing membership number sequence...');
            $this->call('members:seed-sequence');
        }

        return self::SUCCESS;
    }
}
