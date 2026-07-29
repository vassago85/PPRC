<?php

use App\Models\Member;
use App\Services\Admin\AdminDashboardService;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function onboardCard(): array
{
    $card = collect(app(AdminDashboardService::class)->needsAttention())
        ->firstWhere('label', 'Members to onboard');

    expect($card)->not->toBeNull();

    return $card;
}

it('counts only members the club can act on, not the whole member base', function () {
    Member::factory()->count(3)->create();                       // pending, email confirmed
    Member::factory()->count(5)->active()->create();
    Member::factory()->count(2)->awaitingEmail()->create();      // pending, email NOT confirmed
    Member::factory()->expired()->create();
    Member::factory()->resigned()->create();

    // 12 members total. 5 are Pending, but the 2 who never confirmed their email
    // are not work the club can do — only the member can click the link — so the
    // onboard queue is the 3 who confirmed and are now waiting on us.
    expect(Member::count())->toBe(12);
    expect(onboardCard()['value'])->toBe(3);
});

it('keeps members who never confirmed their email out of the onboard count', function () {
    Member::factory()->count(2)->create();                       // confirmed → actionable
    Member::factory()->count(377)->awaitingEmail()->create();    // the real-world 377

    // This is the number that would have exploded the queue to 490 on the
    // production data: unconfirmed signups are Pending but not onboarding work.
    expect(Member::query()->pending()->count())->toBe(379);
    expect(onboardCard()['value'])->toBe(2);
});

it('leaves abandoned signups out of the onboard count', function () {
    Member::factory()->count(3)->create();
    Member::factory()->count(4)->abandoned()->create();

    // Abandoned members are still Pending, but nobody is chasing them, so they
    // must not inflate the queue — this is the count that read 111.
    expect(Member::query()->where('lifecycle', 'pending')->count())->toBe(7);
    expect(onboardCard()['value'])->toBe(3);
});

it('leaves suspended members out of the onboard count', function () {
    Member::factory()->count(2)->create();
    Member::factory()->suspended()->create();

    expect(onboardCard()['value'])->toBe(2);
});

it('keeps the onboard count consistent with the members list "pending onboard" tab', function () {
    Member::factory()->count(4)->create();
    Member::factory()->count(6)->active()->create();
    Member::factory()->count(2)->abandoned()->create();
    Member::factory()->count(3)->awaitingEmail()->create();

    // The ListMembers "pending_onboard" tab uses exactly this scope, so the two
    // cannot drift apart. Note this is needsOnboarding(), not pending(): the
    // abandoned and awaiting-email members are Pending but not in the queue.
    expect(onboardCard()['value'])->toBe(Member::query()->needsOnboarding()->count())
        ->and(onboardCard()['value'])->toBe(4);
});

it('links the onboard card to the pending-onboard tab (not an unfiltered list)', function () {
    Member::factory()->create();

    $url = onboardCard()['url'];

    // Members are filtered by tabs, so the deep-link must target the tab,
    // otherwise it lands on the unfiltered "All" tab and looks like it
    // counts every member.
    expect($url)->toContain('activeTab=pending_onboard');
    expect($url)->not->toContain('tableFilters');
});
