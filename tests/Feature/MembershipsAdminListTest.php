<?php

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Filament\Admin\Resources\Memberships\Pages\ListMemberships;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use App\Services\Membership\MemberService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('developer');
    $this->actingAs($admin);
});

function membershipFor(array $memberAttributes, array $membershipAttributes = []): Membership
{
    $member = Member::factory()->create($memberAttributes);

    return Membership::factory()->create([
        'member_id' => $member->id,
        ...$membershipAttributes,
    ]);
}

/*
|--------------------------------------------------------------------------
| Searching by member name
|--------------------------------------------------------------------------
| The member column searches a relationship, so its search columns resolve
| against the Member model. Prefixing them with "member." made Filament read
| them as a JSON path and the whole list errored out instead of filtering.
*/

it('filters the list by surname without erroring', function () {
    membershipFor(['first_name' => 'Kobus', 'last_name' => 'Van Zyl']);
    membershipFor(['first_name' => 'Sean', 'last_name' => 'Swarts']);

    Livewire::test(ListMemberships::class)
        ->set('tableSearch', 'van zyl')
        ->assertOk()
        ->assertCanSeeTableRecords(Membership::whereRelation('member', 'last_name', 'Van Zyl')->get())
        ->assertCanNotSeeTableRecords(Membership::whereRelation('member', 'last_name', 'Swarts')->get());
});

it('filters the list by first name', function () {
    membershipFor(['first_name' => 'Coenie', 'last_name' => 'Van Tonder']);
    membershipFor(['first_name' => 'Sean', 'last_name' => 'Swarts']);

    Livewire::test(ListMemberships::class)
        ->set('tableSearch', 'coenie')
        ->assertOk()
        ->assertCanSeeTableRecords(Membership::whereRelation('member', 'first_name', 'Coenie')->get())
        ->assertCanNotSeeTableRecords(Membership::whereRelation('member', 'first_name', 'Sean')->get());
});

it('finds a member by full name typed in one go', function () {
    membershipFor(['first_name' => 'Coenie', 'last_name' => 'Van Tonder']);
    membershipFor(['first_name' => 'Sean', 'last_name' => 'Swarts']);

    Livewire::test(ListMemberships::class)
        ->set('tableSearch', 'coenie van tonder')
        ->assertOk()
        ->assertCanSeeTableRecords(Membership::whereRelation('member', 'first_name', 'Coenie')->get())
        ->assertCanNotSeeTableRecords(Membership::whereRelation('member', 'first_name', 'Sean')->get());
});

it('finds a member by their account email', function () {
    $user = User::factory()->create(['email' => 'kobus@example.com']);
    membershipFor(['first_name' => 'Kobus', 'last_name' => 'Van Zyl', 'user_id' => $user->id]);
    membershipFor(['first_name' => 'Sean', 'last_name' => 'Swarts']);

    Livewire::test(ListMemberships::class)
        ->set('tableSearch', 'kobus@example.com')
        ->assertOk()
        ->assertCanSeeTableRecords(Membership::whereRelation('member', 'user_id', $user->id)->get())
        ->assertCanNotSeeTableRecords(Membership::whereRelation('member', 'first_name', 'Sean')->get());
});

it('filters the list by membership number', function () {
    membershipFor(['membership_number' => 'PPRC-0085']);
    membershipFor(['membership_number' => 'PPRC-0029']);

    Livewire::test(ListMemberships::class)
        ->set('tableSearch', 'PPRC-0085')
        ->assertOk()
        ->assertCanSeeTableRecords(Membership::whereRelation('member', 'membership_number', 'PPRC-0085')->get())
        ->assertCanNotSeeTableRecords(Membership::whereRelation('member', 'membership_number', 'PPRC-0029')->get());
});

/*
|--------------------------------------------------------------------------
| The list tells the truth about where a membership stands
|--------------------------------------------------------------------------
*/

it('reports an active membership whose period has ended as expired', function () {
    $membership = membershipFor([], [
        'status' => MembershipStatus::Active,
        'period_end' => now()->subWeek(),
    ]);

    expect($membership->isLapsed())->toBeTrue()
        ->and($membership->effectiveStatus())->toBe(MembershipStatus::Expired)
        ->and($membership->status)->toBe(MembershipStatus::Active);
});

it('does not call a membership lapsed on its final day', function () {
    $membership = membershipFor([], [
        'status' => MembershipStatus::Active,
        'period_end' => now(),
    ]);

    expect($membership->isLapsed())->toBeFalse()
        ->and($membership->effectiveStatus())->toBe(MembershipStatus::Active);
});

it('never calls a lifetime membership lapsed', function () {
    $membership = membershipFor([], [
        'status' => MembershipStatus::Active,
        'period_end' => null,
    ]);

    expect($membership->isLapsed())->toBeFalse()
        ->and($membership->effectiveStatus())->toBe(MembershipStatus::Active);
});

it('separates current from lapsed memberships on the tabs', function () {
    $current = membershipFor([], [
        'status' => MembershipStatus::Active,
        'period_end' => now()->addMonth(),
    ]);
    $lapsed = membershipFor([], [
        'status' => MembershipStatus::Active,
        'period_end' => now()->subMonth(),
    ]);
    $pending = membershipFor([], ['status' => MembershipStatus::PendingApproval]);

    Livewire::test(ListMemberships::class, ['activeTab' => 'current'])
        ->assertCanSeeTableRecords([$current])
        ->assertCanNotSeeTableRecords([$lapsed, $pending]);

    Livewire::test(ListMemberships::class, ['activeTab' => 'lapsed'])
        ->assertCanSeeTableRecords([$lapsed])
        ->assertCanNotSeeTableRecords([$current, $pending]);

    Livewire::test(ListMemberships::class, ['activeTab' => 'needs_action'])
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$current, $lapsed]);
});

/*
|--------------------------------------------------------------------------
| Quick edit, renew and bulk approve
|--------------------------------------------------------------------------
*/

it('changes status and dates from the quick edit modal', function () {
    $membership = membershipFor([], [
        'status' => MembershipStatus::PendingPayment,
        'period_end' => now()->subMonth(),
    ]);

    Livewire::test(ListMemberships::class)
        ->callTableAction('quick_edit', $membership, [
            'status' => MembershipStatus::Active->value,
            'period_start' => now()->toDateString(),
            'period_end' => now()->addYear()->toDateString(),
        ])
        ->assertHasNoTableActionErrors();

    $membership->refresh();

    expect($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->period_end->toDateString())->toBe(now()->addYear()->toDateString())
        ->and($membership->member->fresh()->status)->toBe(MemberStatus::Active);
});

it('renews into a fresh period without losing paid time', function () {
    $membership = membershipFor([], [
        'status' => MembershipStatus::Active,
        'period_end' => now()->addMonth(),
    ]);

    Livewire::test(ListMemberships::class)
        ->callTableAction('renew', $membership, [
            'membership_type_id' => $membership->membership_type_id,
            'activate' => false,
        ])
        ->assertHasNoTableActionErrors();

    $renewal = $membership->member->memberships()->where('id', '!=', $membership->id)->first();

    expect($renewal)->not->toBeNull()
        ->and($renewal->period_start->toDateString())
        ->toBe($membership->period_end->copy()->addDay()->toDateString());
});

it('approves several pending memberships at once', function () {
    $first = membershipFor([], ['status' => MembershipStatus::PendingApproval]);
    $second = membershipFor([], ['status' => MembershipStatus::PendingPayment]);
    $untouched = membershipFor([], ['status' => MembershipStatus::Cancelled]);

    Livewire::test(ListMemberships::class)
        ->callTableBulkAction('approve', [$first, $second, $untouched]);

    expect($first->fresh()->status)->toBe(MembershipStatus::Active)
        ->and($second->fresh()->status)->toBe(MembershipStatus::Active)
        ->and($untouched->fresh()->status)->toBe(MembershipStatus::Cancelled);
});

/*
|--------------------------------------------------------------------------
| Manual activation keeps the member in step
|--------------------------------------------------------------------------
*/

it('activates the member when a membership is set active directly', function () {
    $membership = membershipFor(
        ['status' => MemberStatus::Expired, 'expiry_date' => now()->subMonths(2)],
        ['status' => MembershipStatus::PendingPayment, 'period_end' => now()->addYear()],
    );

    $membership->update(['status' => MembershipStatus::Active]);

    $member = $membership->member->fresh();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($member->expiry_date->toDateString())->toBe($membership->period_end->toDateString());
});

it('clears a stale expiry date when the membership never expires', function () {
    $membership = membershipFor(
        ['status' => MemberStatus::Expired, 'expiry_date' => now()->subMonths(2)],
        ['status' => MembershipStatus::PendingPayment, 'period_end' => null],
    );

    $membership->update(['status' => MembershipStatus::Active]);

    $member = $membership->member->fresh();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($member->expiry_date)->toBeNull();
});

it('leaves a suspended member suspended when their membership is saved active', function () {
    $membership = membershipFor(
        ['status' => MemberStatus::Suspended],
        ['status' => MembershipStatus::Active, 'period_end' => now()->addYear()],
    );

    $membership->update(['admin_notes' => 'Reviewed by committee']);

    expect($membership->member->fresh()->status)->toBe(MemberStatus::Suspended);
});

it('does not pull a member expiry date backwards', function () {
    $membership = membershipFor(
        ['status' => MemberStatus::Active, 'expiry_date' => now()->addYear()],
        ['status' => MembershipStatus::PendingPayment, 'period_end' => now()->addMonth()],
    );

    $expiryBefore = $membership->member->expiry_date->toDateString();

    $membership->update(['status' => MembershipStatus::Active]);

    expect($membership->member->fresh()->expiry_date->toDateString())->toBe($expiryBefore);
});

it('reaches the same member state whether approved or edited straight to active', function () {
    $viaApprove = membershipFor(
        ['status' => MemberStatus::Expired, 'expiry_date' => now()->subMonths(2)],
        ['status' => MembershipStatus::PendingApproval, 'period_end' => now()->addYear()],
    );
    $viaEdit = membershipFor(
        ['status' => MemberStatus::Expired, 'expiry_date' => now()->subMonths(2)],
        ['status' => MembershipStatus::PendingApproval, 'period_end' => now()->addYear()],
    );

    app(MemberService::class)->activate($viaApprove);
    $viaEdit->update(['status' => MembershipStatus::Active]);

    expect($viaEdit->member->fresh()->status)->toBe($viaApprove->member->fresh()->status)
        ->and($viaEdit->member->fresh()->expiry_date->toDateString())
        ->toBe($viaApprove->member->fresh()->expiry_date->toDateString());
});
