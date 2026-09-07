<?php

use App\Filament\Admin\Pages\OnboardingPipeline;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole('developer');
    $this->actingAs($this->admin);
});

it('renders the onboarding pipeline page for a member-viewing admin', function () {
    Member::factory()->count(2)->create();

    Livewire::test(OnboardingPipeline::class)
        ->assertOk()
        ->assertSee('Onboarding');
});

it('defaults to the Choosing plan stage on first load', function () {
    Livewire::test(OnboardingPipeline::class)
        ->assertSet('stage', 'choosing_plan');
});

it('honours the ?stage= query string on mount', function () {
    $this->get('/admin/onboarding?stage=awaiting_payment')
        ->assertOk()
        ->assertSee('Awaiting payment');
});

it('rejects an unknown stage in the query string and falls back to the default', function () {
    Livewire::test(OnboardingPipeline::class, ['stage' => 'not-a-stage'])
        ->assertOk();
});

it('exposes stage fine-print for every stage', function () {
    $lines = (new OnboardingPipeline)->stageFineprint();

    expect($lines)
        ->toHaveKey('email_unconfirmed')
        ->toHaveKey('choosing_plan')
        ->toHaveKey('awaiting_payment')
        ->toHaveKey('ready_to_activate')
        ->toHaveKey('abandoned');
});
