<?php

use App\Models\Member;
use App\Models\User;

/**
 * The five roster segments must partition the members table:
 * every member appears in exactly one, and their union sums to All.
 *
 *   Current · Onboarding · Awaiting email · Lapsed · Abandoned
 *
 * This is the guarantee the admin Members list depends on — if it drifts,
 * counts stop reconciling with "All" and the list falls back to the exact
 * bug the rebuild set out to fix.
 */
it('every member falls into exactly one of the five roster segments', function () {
    // A mix that exercises every lifecycle + flag combination we support.
    $factory = Member::factory();

    $factory->count(4)->active()->create();
    $factory->count(3)->create();                         // pending, email confirmed → Onboarding
    $factory->count(2)->awaitingEmail()->create();        // pending, email unconfirmed
    $factory->count(2)->abandoned()->create();
    $factory->count(2)->expired()->create();
    $factory->count(1)->resigned()->create();
    $factory->count(1)->suspended()->create();

    // Total across all rows.
    $all = Member::query()->count();

    // Each scope's count, using the exact query the ListMembers page uses.
    $current = Member::query()->current()->count();
    $onboarding = Member::query()->needsOnboarding()->count();
    $awaitingEmail = Member::query()->awaitingEmail()->count();
    $lapsed = Member::query()->lapsedRoster()->count();
    $abandoned = Member::query()->abandoned()->count();

    expect($current + $onboarding + $awaitingEmail + $lapsed + $abandoned)
        ->toBe($all, 'The five segments must sum to All');

    // And no member should sit in more than one segment.
    $ids = collect([
        Member::query()->current()->pluck('id'),
        Member::query()->needsOnboarding()->pluck('id'),
        Member::query()->awaitingEmail()->pluck('id'),
        Member::query()->lapsedRoster()->pluck('id'),
        Member::query()->abandoned()->pluck('id'),
    ])->flatten();

    expect($ids->count())->toBe($ids->unique()->count(),
        'The five segments must be mutually exclusive');
});

it('suspended members always land in Current, whatever their underlying lifecycle', function () {
    Member::factory()->suspended()->create();                        // active + suspended
    Member::factory()->suspended()->expired()->create();             // expired + suspended
    $suspendedPending = Member::factory()->create();                 // pending + suspended
    $suspendedPending->update(['suspended_at' => now()]);

    expect(Member::query()->current()->count())->toBe(3);
    expect(Member::query()->needsOnboarding()->count())->toBe(0);
    expect(Member::query()->lapsedRoster()->count())->toBe(0);
    expect(Member::query()->abandoned()->count())->toBe(0);
});
