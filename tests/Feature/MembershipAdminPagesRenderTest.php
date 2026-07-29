<?php

use App\Enums\EndorsementStatus;
use App\Enums\MembershipStatus;
use App\Filament\Admin\Resources\EndorsementRequests\Pages\ListEndorsementRequests;
use App\Filament\Admin\Resources\Members\Pages\EditMember;
use App\Filament\Admin\Resources\Members\RelationManagers\MembershipsRelationManager;
use App\Models\EndorsementRequest;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
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

it('renders the memberships relation manager on a member with a lapsed membership', function () {
    $member = Member::factory()->create();

    $lapsed = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::Active,
        'period_end' => now()->subMonth(),
    ]);

    Livewire::test(MembershipsRelationManager::class, [
        'ownerRecord' => $member,
        'pageClass' => EditMember::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$lapsed])
        ->assertSee('Still marked active');
});

it('quick edits a membership from the member page', function () {
    $member = Member::factory()->create();

    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::PendingApproval,
        'period_end' => now()->subMonth(),
    ]);

    Livewire::test(MembershipsRelationManager::class, [
        'ownerRecord' => $member,
        'pageClass' => EditMember::class,
    ])
        ->callTableAction('quick_edit', $membership, [
            'status' => MembershipStatus::Active->value,
            'period_start' => now()->toDateString(),
            'period_end' => now()->addYear()->toDateString(),
        ])
        ->assertHasNoTableActionErrors();

    expect($membership->fresh()->status)->toBe(MembershipStatus::Active);
});

it('searches endorsement requests by member surname', function () {
    $vanZyl = Member::factory()->create(['first_name' => 'Kobus', 'last_name' => 'Van Zyl']);
    $swarts = Member::factory()->create(['first_name' => 'Sean', 'last_name' => 'Swarts']);

    $wanted = EndorsementRequest::create([
        'member_id' => $vanZyl->id,
        'reason' => 'Dedicated sport shooter',
        'firearm_type' => 'Rifle',
        'status' => EndorsementStatus::Pending,
    ]);

    $other = EndorsementRequest::create([
        'member_id' => $swarts->id,
        'reason' => 'Dedicated hunter',
        'firearm_type' => 'Shotgun',
        'status' => EndorsementStatus::Pending,
    ]);

    Livewire::test(ListEndorsementRequests::class)
        ->set('tableSearch', 'van zyl')
        ->assertOk()
        ->assertCanSeeTableRecords([$wanted])
        ->assertCanNotSeeTableRecords([$other]);
});
