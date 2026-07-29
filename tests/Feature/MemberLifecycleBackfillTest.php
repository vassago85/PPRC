<?php

use App\Enums\MemberLifecycle;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

/**
 * Exercises the real backfill SQL from the additive migration against rows
 * written in the old eight-status vocabulary. The schema part of that migration
 * is covered by every other test (RefreshDatabase runs it), but the mapping is
 * the part that touches live data, so it gets checked directly.
 */

/** Insert a member the way the old code would have, bypassing the model. */
function legacyMember(string $status, array $attributes = []): int
{
    $member = Member::factory()->create();

    DB::table('members')->where('id', $member->id)->update(array_merge([
        'status' => $status,
        'lifecycle' => MemberLifecycle::Pending->value,
        'suspended_at' => null,
        'abandoned_at' => null,
    ], $attributes));

    return $member->id;
}

function runBackfill(): void
{
    $migration = require database_path('migrations/2026_07_29_140000_add_lifecycle_to_members_table.php');

    $backfill = new ReflectionMethod($migration, 'backfill');
    $backfill->invoke($migration);
}

function backfilled(int $id): object
{
    return DB::table('members')->where('id', $id)->first();
}

it('maps each of the old eight statuses onto the canonical four', function () {
    $ids = [
        'active' => legacyMember('active'),
        'pending' => legacyMember('pending'),
        'unverified' => legacyMember('unverified'),
        'expired' => legacyMember('expired', ['expiry_date' => now()->subMonth()->toDateString()]),
        'inactive' => legacyMember('inactive', ['expiry_date' => now()->subYear()->toDateString()]),
        'resigned' => legacyMember('resigned'),
    ];

    runBackfill();

    expect(backfilled($ids['active'])->lifecycle)->toBe('active')
        ->and(backfilled($ids['pending'])->lifecycle)->toBe('pending')
        // Unverified is read off the user's email_verified_at from here on.
        ->and(backfilled($ids['unverified'])->lifecycle)->toBe('pending')
        ->and(backfilled($ids['expired'])->lifecycle)->toBe('expired')
        // Inactive only ever meant "expired a long time ago".
        ->and(backfilled($ids['inactive'])->lifecycle)->toBe('expired')
        ->and(backfilled($ids['resigned'])->lifecycle)->toBe('resigned');
});

it('moves an abandoned signup to a flag and dates it from the nudge we sent', function () {
    $nudgedAt = now()->subMonths(2)->startOfSecond();

    $withNudge = legacyMember('abandoned', ['signup_reminder_sent_at' => $nudgedAt]);
    $withoutNudge = legacyMember('abandoned', ['signup_reminder_sent_at' => null]);

    runBackfill();

    $one = backfilled($withNudge);
    $two = backfilled($withoutNudge);

    expect($one->lifecycle)->toBe('pending')
        ->and($one->abandoned_at)->not->toBeNull()
        ->and(Carbon\Carbon::parse($one->abandoned_at)->toDateString())->toBe($nudgedAt->toDateString())
        // No nudge on record, so fall back to when the row was last touched.
        ->and($two->lifecycle)->toBe('pending')
        ->and($two->abandoned_at)->not->toBeNull();
});

it('recovers the lifecycle hiding under a suspension from the expiry date', function () {
    $paidUp = legacyMember('suspended', ['expiry_date' => now()->addMonths(3)->toDateString()]);
    $lapsed = legacyMember('suspended', ['expiry_date' => now()->subMonths(3)->toDateString()]);
    $lifetime = legacyMember('suspended', ['expiry_date' => null]);

    runBackfill();

    expect(backfilled($paidUp)->lifecycle)->toBe('active')
        ->and(backfilled($lapsed)->lifecycle)->toBe('expired')
        // No expiry date means a membership that never ends.
        ->and(backfilled($lifetime)->lifecycle)->toBe('active');
});

it('stamps every suspended member so the suspension is not lost', function () {
    $ids = [
        legacyMember('suspended', ['expiry_date' => now()->addYear()->toDateString()]),
        legacyMember('suspended', ['expiry_date' => now()->subYear()->toDateString()]),
    ];

    runBackfill();

    foreach ($ids as $id) {
        expect(backfilled($id)->suspended_at)->not->toBeNull();
        expect(Member::find($id)->isSuspended())->toBeTrue();
    }
});

it('leaves nobody behind on the default lifecycle by accident', function () {
    foreach (['active', 'pending', 'unverified', 'expired', 'inactive', 'resigned', 'abandoned', 'suspended'] as $status) {
        legacyMember($status, ['expiry_date' => now()->subMonth()->toDateString()]);
    }

    runBackfill();

    // Every row must map somewhere, and only the statuses that genuinely mean
    // "not yet a member" may land on Pending.
    $byLifecycle = DB::table('members')->select('lifecycle')->get()->countBy('lifecycle');

    expect($byLifecycle->sum())->toBe(8)
        ->and($byLifecycle['pending'])->toBe(3)   // pending, unverified, abandoned
        ->and($byLifecycle['active'])->toBe(1)    // active
        ->and($byLifecycle['expired'])->toBe(3)   // expired, inactive, suspended-and-lapsed
        ->and($byLifecycle['resigned'])->toBe(1);
});

it('produces the same answer as the mapper the importers use', function () {
    foreach (['active', 'pending', 'unverified', 'expired', 'inactive', 'resigned'] as $status) {
        $id = legacyMember($status, ['expiry_date' => now()->subMonth()->toDateString()]);

        runBackfill();

        expect(backfilled($id)->lifecycle)
            ->toBe(MemberLifecycle::mapLegacy($status, now()->subMonth()->toDateString())['lifecycle']);
    }
});
