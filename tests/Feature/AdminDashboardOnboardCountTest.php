<?php

use App\Enums\MemberStatus;
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

it('counts only pending members as "to onboard", not the whole member base', function () {
    Member::factory()->count(3)->create(['status' => MemberStatus::Pending->value]);
    Member::factory()->count(5)->create(['status' => MemberStatus::Active->value]);
    Member::factory()->count(2)->create(['status' => MemberStatus::Unverified->value]);
    Member::factory()->create(['status' => MemberStatus::Expired->value]);
    Member::factory()->create(['status' => MemberStatus::Resigned->value]);

    // 12 members total, but only 3 are genuinely awaiting onboarding.
    expect(Member::count())->toBe(12);
    expect(onboardCard()['value'])->toBe(3);
});

it('keeps the onboard count consistent with the members list "pending onboard" tab', function () {
    Member::factory()->count(4)->create(['status' => MemberStatus::Pending->value]);
    Member::factory()->count(6)->create(['status' => MemberStatus::Active->value]);

    // The ListMembers "pending_onboard" tab filters on exactly this query.
    $tabCount = Member::where('status', MemberStatus::Pending->value)->count();

    expect(onboardCard()['value'])->toBe($tabCount);
});

it('links the onboard card to the pending-onboard tab (not an unfiltered list)', function () {
    Member::factory()->create(['status' => MemberStatus::Pending->value]);

    $url = onboardCard()['url'];

    // Members are filtered by tabs, so the deep-link must target the tab,
    // otherwise it lands on the unfiltered "All" tab and looks like it
    // counts every member.
    expect($url)->toContain('activeTab=pending_onboard');
    expect($url)->not->toContain('tableFilters');
});
