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
 * Imports members from the SSMM PPRC WordPress plugin export.
 *
 * CSV headers (exactly as the plugin emits them):
 *   email, first_name, last_name, known_as, membership_number, membership_type,
 *   status, phone_country_code, phone_number, city, province, date_of_birth,
 *   shooting_disciplines, join_date, expiry_date, payment_reference
 *
 * Behaviour:
 *   - `unverified` rows are skipped (never confirmed an email).
 *   - `expired`, `inactive`, and `pending` rows are imported without sending any
 *     welcome email. The committee later runs `members:send-welcome` for the
 *     cohort(s) they actually want to invite.
 *   - `active` rows are imported. Welcome emails are also NOT sent here — that
 *     is the dedicated sender command's job.
 *   - Imported Users have a random unguessable password. Members claim their
 *     account via the password-setup link in the welcome invite.
 *   - `membership_type` maps by display name:
 *         "Standard Membership" -> full-member
 *         "Junior"              -> junior
 *         "Life Membership"     -> life-member
 *   - `shooting_disciplines` expands from the plugin's single string into the
 *     PPRC two-value vocabulary (the only formats the club runs):
 *         "both"       -> ["PRS","PR22"]
 *         "centrefire" -> ["PRS"]
 *         "rimfire"    -> ["PR22"]
 *
 * Idempotent: re-running updates existing rows matched by email.
 */
class ImportSsmmMembers extends Command
{
    protected $signature = 'members:import-ssmm
        {path : Absolute or storage-relative path to the SSMM CSV export}
        {--dry-run : Parse and validate only; do not write to the database}
        {--send-welcome : Send a welcome/account-claim email to newly created members}';

    protected $description = 'Import members from the legacy SSMM PPRC plugin CSV export';

    /** @var array<string,string> plugin display name -> MembershipType slug */
    private const TYPE_MAP = [
        'standard membership' => 'full-member',
        'junior' => 'junior',
        'life membership' => 'life-member',
        'pensioner' => 'pensioner',
        'spouse' => 'spouse',
    ];

    /** @var array<string,string> plugin status -> MemberStatus enum value */
    private const STATUS_MAP = [
        'active' => 'active',
        'pending' => 'pending',
        'expired' => 'expired',
        'inactive' => 'inactive',
    ];

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
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

        // SSMM exports are UTF-8 with a BOM. Without this strip, the first
        // column key becomes "\xEF\xBB\xBFemail" and every row silently
        // "skips missing email". Lost two hours to this once — never again.
        if (isset($headerRow[0])) {
            $headerRow[0] = preg_replace('/^\x{FEFF}/u', '', (string) $headerRow[0]);
        }

        $headers = array_map(fn ($h) => Str::of((string) $h)->trim()->lower()->toString(), $headerRow);

        $typeIdBySlug = MembershipType::pluck('id', 'slug')->all();

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped_unverified' => 0,
            'skipped_missing_email' => 0,
            'skipped_unknown_status' => 0,
            'warnings' => 0,
            'welcomed' => 0,
        ];
        $newUsers = [];
        $line = 1;

        DB::beginTransaction();
        try {
            while (($raw = fgetcsv($fh)) !== false) {
                $line++;

                // Pad/truncate to header length so array_combine never errors.
                $raw = array_pad($raw, count($headers), '');
                $row = array_combine($headers, array_slice($raw, 0, count($headers)));

                $email = trim((string) ($row['email'] ?? ''));
                if ($email === '') {
                    $this->warn("  line {$line}: missing email — skipped");
                    $stats['skipped_missing_email']++;

                    continue;
                }

                $rawStatus = strtolower(trim((string) ($row['status'] ?? '')));
                if ($rawStatus === 'unverified') {
                    $stats['skipped_unverified']++;

                    continue;
                }

                if (! isset(self::STATUS_MAP[$rawStatus])) {
                    $this->warn("  line {$line} ({$email}): unrecognised status '{$rawStatus}' — skipped");
                    $stats['skipped_unknown_status']++;

                    continue;
                }
                $status = self::STATUS_MAP[$rawStatus];

                $typeName = strtolower(trim((string) ($row['membership_type'] ?? '')));
                $typeSlug = $typeName !== '' ? (self::TYPE_MAP[$typeName] ?? null) : null;
                if ($typeName !== '' && $typeSlug === null) {
                    $this->warn("  line {$line} ({$email}): unknown membership_type '{$row['membership_type']}' — profile recorded without type");
                    $stats['warnings']++;
                }
                if ($typeSlug !== null && ! isset($typeIdBySlug[$typeSlug])) {
                    $this->warn("  line {$line} ({$email}): mapped type '{$typeSlug}' not seeded — profile recorded without type");
                    $stats['warnings']++;
                    $typeSlug = null;
                }

                $firstName = trim((string) ($row['first_name'] ?? ''));
                $lastName = trim((string) ($row['last_name'] ?? ''));
                $displayName = trim($firstName.' '.$lastName);
                if ($displayName === '') {
                    $displayName = $email;
                }

                [$phoneCountryCode, $phoneNumber] = $this->normalisePhone(
                    (string) ($row['phone_country_code'] ?? ''),
                    (string) ($row['phone_number'] ?? ''),
                );

                $disciplines = $this->expandDisciplines((string) ($row['shooting_disciplines'] ?? ''));

                $joinDate = $this->parseDate((string) ($row['join_date'] ?? ''));
                $expiryDate = $this->parseDate((string) ($row['expiry_date'] ?? ''));
                $dob = $this->parseDate((string) ($row['date_of_birth'] ?? ''));

                if ($dryRun) {
                    $stats['created']++;

                    continue;
                }

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $displayName,
                        // Random unguessable password. Members set their own when they
                        // click the welcome-invite setup link.
                        'password' => Hash::make(Str::random(48)),
                        'created_via_import' => true,
                    ],
                );
                $wasNewUser = $user->wasRecentlyCreated;

                $memberAttrs = array_filter([
                    'first_name' => $firstName ?: null,
                    'last_name' => $lastName ?: null,
                    'known_as' => trim((string) ($row['known_as'] ?? '')) ?: null,
                    'membership_number' => trim((string) ($row['membership_number'] ?? '')) ?: null,
                    'phone_country_code' => $phoneCountryCode,
                    'phone_number' => $phoneNumber,
                    'city' => trim((string) ($row['city'] ?? '')) ?: null,
                    'province' => trim((string) ($row['province'] ?? '')) ?: null,
                    'date_of_birth' => $dob,
                    'shooting_disciplines' => $disciplines,
                    'status' => $status,
                    'join_date' => $joinDate,
                    'expiry_date' => $expiryDate,
                ], fn ($v) => $v !== null && $v !== '' && $v !== []);

                // Carry a light audit trail of the source payment reference so
                // the treasurer can reconcile imports against the old system.
                $paymentRef = trim((string) ($row['payment_reference'] ?? ''));
                if ($paymentRef !== '') {
                    $memberAttrs['notes'] = "SSMM import. payment_reference={$paymentRef}";
                }

                $member = Member::updateOrCreate(
                    ['user_id' => $user->id],
                    $memberAttrs,
                );

                // Create a Membership record if the member doesn't have one yet
                if ($typeSlug && isset($typeIdBySlug[$typeSlug])) {
                    $existingMembership = DB::table('memberships')
                        ->where('member_id', $member->id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $existingMembership) {
                        $type = MembershipType::find($typeIdBySlug[$typeSlug]);
                        $membershipStatus = match ($status) {
                            'active' => 'active',
                            'pending' => 'pending_approval',
                            'expired' => 'expired',
                            'inactive' => 'cancelled',
                            default => 'pending_approval',
                        };

                        $periodStart = $joinDate ?? now()->toDateString();
                        $periodEnd = $expiryDate;
                        $isLifetime = $typeSlug === 'life-member';
                        if (! $periodEnd && $membershipStatus === 'active' && $type && ! $isLifetime) {
                            $periodEnd = \Illuminate\Support\Carbon::parse($periodStart)
                                ->addMonths($type->duration_months ?: 12)
                                ->subDay()
                                ->toDateString();
                        }

                        // Snapshot the type slug/name (so even if the type is
                        // renamed later we keep their historic label), but
                        // leave price_cents_snapshot NULL: the SSMM export
                        // does not carry a payment amount, so we don't know
                        // what they actually paid. Stamping the type's
                        // current price here would be a lie. New memberships
                        // created through the portal/admin record the real
                        // paid amount.
                        DB::table('memberships')->insert([
                            'member_id' => $member->id,
                            'membership_type_id' => $type->id,
                            'period_start' => $periodStart,
                            'period_end' => $periodEnd,
                            'status' => $membershipStatus,
                            'price_cents_snapshot' => null,
                            'membership_type_slug_snapshot' => $type->slug,
                            'membership_type_name_snapshot' => $type->name,
                            'approved_at' => $membershipStatus === 'active' ? now() : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $wasNewUser ? $stats['created']++ : $stats['updated']++;

                if ($wasNewUser && $sendWelcome) {
                    $newUsers[] = ['user' => $user, 'member' => $member];
                }
            }
            fclose($fh);

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
                        'context' => ['source' => 'members:import-ssmm --send-welcome'],
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
            ['skipped (unverified)', $stats['skipped_unverified']],
            ['skipped (missing email)', $stats['skipped_missing_email']],
            ['skipped (unknown status)', $stats['skipped_unknown_status']],
            ['warnings', $stats['warnings']],
        ];
        if ($sendWelcome) {
            $rows[] = ['welcome emails sent', $stats['welcomed']];
        }
        $this->table(['metric', 'count'], $rows);

        return self::SUCCESS;
    }

    /**
     * SSMM exports country code as anything from "+27" to "South Africa" to "".
     * Normalise to a proper dial code; keep the raw phone_number as-is minus
     * surrounding whitespace.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function normalisePhone(string $rawCode, string $rawNumber): array
    {
        $number = trim($rawNumber);
        if ($number === '') {
            return [null, null];
        }

        $code = trim($rawCode);

        if ($code !== '' && str_starts_with($code, '+') && strlen($code) > 5) {
            $code = '+27';
        }

        if ($code === '' || ! str_starts_with($code, '+')) {
            $code = '+27';
        }

        return [$code, $number];
    }

    /**
     * Expands the plugin's single-string discipline value into the JSON array
     * the `members.shooting_disciplines` column expects.
     *
     * @return array<int, string>|null
     */
    private function expandDisciplines(string $raw): ?array
    {
        $v = strtolower(trim($raw));

        return match ($v) {
            'both' => ['PRS', 'PR22'],
            'centrefire', 'centerfire' => ['PRS'],
            'rimfire' => ['PR22'],
            default => null,
        };
    }

    /**
     * The plugin emits dates as "YYYY-MM-DD" or "YYYY-MM-DD HH:MM:SS". A handful
     * of rows contain obviously-wrong DoB values (1907, etc.) but we still
     * store what's there — cleanup is a committee task, not an importer's.
     */
    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        try {
            $dt = new \DateTimeImmutable($raw);
            $year = (int) $dt->format('Y');
            if ($year < 1900 || $year > 2100) {
                return null;
            }

            return $dt->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
