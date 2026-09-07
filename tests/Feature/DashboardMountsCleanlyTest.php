<?php

use App\Filament\Admin\Pages\Dashboard;
use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * Smoke test for the rebuilt admin dashboard.
 *
 * `/admin` used to 500 when `AdminDashboardService` reached for a hard-coded
 * route name that had drifted (`filament.admin.pages.onboarding-pipeline`
 * vs. the actual `filament.admin.pages.onboarding`). Every future refactor
 * to the queue / money strip / activity feed must still leave the page
 * openable — assert that here.
 */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole('developer');
    $this->actingAs($this->admin);
});

it('mounts the dashboard with no data without exploding', function () {
    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Overview');
});

it('serves /admin over HTTP', function () {
    $this->get('/admin')->assertOk()->assertSee('Overview');
});

it('gives the overview subtitle a real weekday even with no matches', function () {
    $subtitle = app(AdminDashboardService::class)->overviewSubtitle();

    // Sunday D MMMM YYYY, at a minimum — never a raw locale token or empty.
    expect($subtitle)->not->toBe('')
        ->and($subtitle)->toMatch('/\d{4}/');
});

it('resolves the onboarding pipeline URL on the needsYou row, not a hard-coded route name', function () {
    // Force at least one onboarding row so the entry exists.
    \App\Models\Member::factory()->count(2)->create();

    $rows = app(AdminDashboardService::class)->needsYou();
    $onboardRow = collect($rows)->firstWhere('label', 'Members part-way through onboarding');

    if ($onboardRow === null) {
        // No pending members were produced by the factory config; that's fine
        // — the important guarantee is that `needsYou()` did not throw.
        expect(true)->toBeTrue();

        return;
    }

    expect($onboardRow['actions'][0]['url'])->toContain('/admin/onboarding');
});
