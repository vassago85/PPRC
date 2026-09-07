<?php

use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\Members\Pages\ViewMember;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * Smoke test for the rebuilt member record view. The record page renders
 * a lot of derived state (pill, meta line, at-a-glance grid, checklist,
 * blocking card, timeline) — this test just asserts it mounts and lands
 * on the overview tab without exploding.
 */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole('developer');
    $this->actingAs($this->admin);
});

it('renders the record head + overview tab for an ordinary member', function () {
    $member = Member::factory()->create();

    Livewire::test(ViewMember::class, ['record' => $member->id])
        ->assertOk()
        ->assertSee($member->fullName())
        ->assertSee('At a glance')
        ->assertSee('Onboarding checklist');
});

it('serves /admin/members/{id} over HTTP', function () {
    $member = Member::factory()->create();

    $this->get(MemberResource::getUrl('view', ['record' => $member]))
        ->assertOk()
        ->assertSee($member->fullName());
});

it('honours the ?tab= query string when deep-linking', function () {
    $member = Member::factory()->create();

    Livewire::test(ViewMember::class, ['record' => $member->id, 'tab' => 'payments'])
        ->assertSet('tab', 'payments');
});

it('exposes an at-a-glance row for every core field', function () {
    $member = Member::factory()->create();

    Livewire::test(ViewMember::class, ['record' => $member->id]);
    $glance = (new ViewMember)->tap(fn ($p) => $p->mount($member->id))->atAGlance();

    // Not asserting the labels one by one — just that the shape is complete.
    expect($glance)->toBeArray()->toHaveCount(7)
        ->and(collect($glance)->pluck('label')->all())
        ->toContain('Membership', 'Discipline', 'SAPRF #', 'SA ID', 'Matches shot', 'Badges', 'Lifetime paid');
});

it('exposes an actionable record pill (label + variant)', function () {
    $member = Member::factory()->awaitingEmail()->create();

    $page = new ViewMember;
    $page->mount($member->id);
    $pill = $page->recordPill();

    expect($pill)->toHaveKeys(['label', 'variant', 'day']);
    expect($pill['label'])->toBeString()->not->toBe('');
});
