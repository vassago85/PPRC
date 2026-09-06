<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;

/**
 * The two old money pages — /admin/find-payment and /admin/reconcile-statement
 * — were consolidated into /admin/reconciliation with two tabs. Every URL that
 * used to work must still resolve, and must land the treasurer on the right
 * side of the desk without a second click.
 */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Cache::forget('site_settings:payments.bank.reference_prefix');

    $treasurer = User::factory()->create(['email_verified_at' => now()]);
    $treasurer->assignRole('treasurer');
    $this->actingAs($treasurer);
});

it('redirects the old find-payment URL to the Find one line tab', function () {
    $this->get('/admin/find-payment')
        ->assertRedirect('/admin/reconciliation?tab=find');
});

it('redirects the old reconcile-statement URL to the Reconcile statement tab', function () {
    $this->get('/admin/reconcile-statement')
        ->assertRedirect('/admin/reconciliation?tab=statement');
});
