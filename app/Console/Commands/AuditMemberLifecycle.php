<?php

namespace App\Console\Commands;

use App\Enums\MemberLifecycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only check on the lifecycle backfill. Writes nothing, ever.
 *
 * Run it before migrating to see what the backfill will do to real data, and
 * again afterwards to confirm it did exactly that. The projection below applies
 * the same rules as the migration, so a disagreement after the fact means the
 * backfill did not land the way it was designed to.
 */
class AuditMemberLifecycle extends Command
{
    protected $signature = 'members:lifecycle-audit';

    protected $description = 'Show what the lifecycle backfill will do (or did) to the members table. Read-only.';

    public function handle(): int
    {
        $migrated = Schema::hasColumn('members', 'lifecycle');

        $this->newLine();
        $this->line($migrated
            ? 'The lifecycle column exists, so this is a post-migration check.'
            : 'The lifecycle column does not exist yet, so this is a projection.');

        $this->projection();

        if (! $migrated) {
            $this->newLine();
            $this->comment('Nothing was written. Migrate, then run this again to confirm the result.');

            return self::SUCCESS;
        }

        $this->flags();

        return $this->disagreements();
    }

    /**
     * What each old status becomes, and how many members that affects.
     */
    protected function projection(): void
    {
        $rows = DB::table('members')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('There are no members to audit.');

            return;
        }

        $this->newLine();
        $this->table(
            ['old status', 'members', 'becomes', 'gains a flag'],
            $rows->map(function ($row) {
                $expected = $this->expectedLifecycle($row->status);

                return [
                    $row->status ?? '(none)',
                    $row->total,
                    $this->describeExpected($expected),
                    match (strtolower(trim((string) $row->status))) {
                        'suspended' => 'suspended_at',
                        'abandoned' => 'abandoned_at',
                        default => '—',
                    },
                ];
            })->all(),
        );

        $this->line('Total members: '.$rows->sum('total'));
    }

    /**
     * A suspension is recovered from the expiry date rather than stored, so it
     * is the one status whose outcome is not a single fixed value.
     */
    protected function expectedLifecycle(?string $status): MemberLifecycle|string
    {
        if (strtolower(trim((string) $status)) === 'suspended') {
            return 'split';
        }

        return MemberLifecycle::from(MemberLifecycle::mapLegacy($status)['lifecycle']);
    }

    protected function describeExpected(MemberLifecycle|string $expected): string
    {
        if ($expected === 'split') {
            $today = now()->toDateString();

            $active = DB::table('members')->where('status', 'suspended')
                ->where(fn ($query) => $query->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today))
                ->count();

            $expired = DB::table('members')->where('status', 'suspended')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', $today)
                ->count();

            return "Active ({$active}, still in date) / Expired ({$expired}, lapsed)";
        }

        return $expected->label();
    }

    /**
     * The flags carry information the old column destroyed, so it is worth
     * seeing that they actually landed on somebody.
     */
    protected function flags(): void
    {
        $this->newLine();
        $this->table(['flag', 'members'], [
            ['suspended_at set', DB::table('members')->whereNotNull('suspended_at')->count()],
            ['abandoned_at set', DB::table('members')->whereNotNull('abandoned_at')->count()],
            ['abandoned_at still null after an abandoned status', DB::table('members')
                ->where('status', 'abandoned')->whereNull('abandoned_at')->count()],
        ]);
    }

    /**
     * Every member whose stored lifecycle is not what the rules say it should
     * be. Anything here needs a human before the old column is dropped.
     */
    protected function disagreements(): int
    {
        $today = now()->toDateString();

        $offenders = DB::table('members')
            ->select('id', 'membership_number', 'first_name', 'last_name', 'status', 'lifecycle', 'expiry_date')
            ->get()
            ->reject(function ($member) use ($today) {
                $legacy = strtolower(trim((string) $member->status));

                $expected = $legacy === 'suspended'
                    ? ($member->expiry_date !== null && $member->expiry_date < $today
                        ? MemberLifecycle::Expired->value
                        : MemberLifecycle::Active->value)
                    : MemberLifecycle::mapLegacy($member->status)['lifecycle'];

                return $member->lifecycle === $expected;
            });

        $this->newLine();

        if ($offenders->isEmpty()) {
            $this->info('Every member agrees with the mapping rules.');

            return self::SUCCESS;
        }

        $this->error("{$offenders->count()} member(s) do not match the mapping rules:");
        $this->table(
            ['id', 'number', 'name', 'old status', 'lifecycle', 'expiry'],
            $offenders->take(50)->map(fn ($member) => [
                $member->id,
                $member->membership_number,
                trim("{$member->first_name} {$member->last_name}"),
                $member->status,
                $member->lifecycle,
                $member->expiry_date ?? '—',
            ])->all(),
        );

        if ($offenders->count() > 50) {
            $this->line('… and '.($offenders->count() - 50).' more.');
        }

        return self::FAILURE;
    }
}
