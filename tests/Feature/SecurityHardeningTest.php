<?php

use App\Enums\ShopRunStatus;
use App\Filament\Admin\Pages\SiteSettings;
use App\Livewire\Portal\ShopCheckout;
use App\Models\ExcoMember;
use App\Models\Member;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopRun;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/*
|--------------------------------------------------------------------------
| ExCo roster must not be a privilege-escalation path
|--------------------------------------------------------------------------
*/

it('does not auto-assign a committee role when the editor cannot assign roles', function () {
    // Marketing has the broad committee bundle but NOT settings.roles.assign.
    $editor = User::factory()->create(['email_verified_at' => now()]);
    $editor->assignRole('marketing');
    $this->actingAs($editor);

    $target = User::factory()->create(['email_verified_at' => now()]);

    ExcoMember::create([
        'full_name' => 'Would-Be Chair',
        'position' => 'Chairperson',
        'is_current' => true,
        'linked_user_id' => $target->id,
    ]);

    expect($target->fresh()->hasRole('chairperson'))->toBeFalse();
});

it('auto-assigns the matching role when the editor can assign roles', function () {
    // Chairperson holds settings.roles.assign.
    $editor = User::factory()->create(['email_verified_at' => now()]);
    $editor->assignRole('chairperson');
    $this->actingAs($editor);

    $target = User::factory()->create(['email_verified_at' => now()]);

    ExcoMember::create([
        'full_name' => 'New Treasurer',
        'position' => 'Treasurer',
        'is_current' => true,
        'linked_user_id' => $target->id,
    ]);

    expect($target->fresh()->hasRole('treasurer'))->toBeTrue();
});

it('still syncs roles from a trusted console/seeder context', function () {
    // No authenticated actor → treated as a trusted system context.
    $target = User::factory()->create(['email_verified_at' => now()]);

    ExcoMember::create([
        'full_name' => 'Seeded Secretary',
        'position' => 'Secretary',
        'is_current' => true,
        'linked_user_id' => $target->id,
    ]);

    expect($target->fresh()->hasRole('secretary'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Shop checkout must be scoped to the authenticated member (no IDOR)
|--------------------------------------------------------------------------
*/

it('will not let a member touch another member’s shop order via a tampered id', function () {
    $run = ShopRun::query()->create([
        'title' => 'Open run',
        'slug' => 'open-run',
        'status' => ShopRunStatus::Open,
        'preview_visible' => true,
    ]);

    ShopProduct::create([
        'shop_run_id' => $run->id,
        'name' => 'Club Cap',
        'slug' => 'club-cap',
        'price_cents' => 15000,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $victim = User::factory()->create(['email_verified_at' => now()]);
    Member::factory()->create(['user_id' => $victim->id]);
    $victimOrder = ShopOrder::query()->create([
        'shop_run_id' => $run->id,
        'user_id' => $victim->id,
        'status' => \App\Enums\ShopOrderStatus::Draft,
        'currency' => 'ZAR',
        'subtotal_cents' => 0,
        'shipping_cents' => 0,
        'total_cents' => 0,
        'ship_to_name' => 'Victim Original',
    ]);

    $attacker = User::factory()->create(['email_verified_at' => now()]);
    Member::factory()->create(['user_id' => $attacker->id]);

    $attempt = fn () => Livewire::actingAs($attacker)
        ->test(ShopCheckout::class, ['run' => $run])
        ->set('orderId', $victimOrder->id)
        ->set('ship_to_name', 'Hacked Name')
        ->set('ship_phone', '0000000000')
        ->set('ship_line1', '1 Somewhere')
        ->set('ship_city', 'Pretoria')
        ->set('ship_province', 'Gauteng')
        ->set('ship_postal_code', '0001')
        ->set('ship_country', 'ZA')
        ->call('placeOrder');

    expect($attempt)->toThrow(ModelNotFoundException::class);

    // The victim's order is untouched.
    expect($victimOrder->fresh()->ship_to_name)->toBe('Victim Original');
});

/*
|--------------------------------------------------------------------------
| Site Settings must not leak integration secrets to site-only editors
|--------------------------------------------------------------------------
*/

it('does not hydrate integration secrets for a site-only editor', function () {
    Cache::flush();
    SiteSetting::put('mail.mailgun.secret', 'super-secret-key', ['is_secret' => true, 'group' => 'mail']);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // Treasurer has settings.site.manage but NOT settings.integrations.manage.
    $treasurer = User::factory()->create(['email_verified_at' => now()]);
    $treasurer->assignRole('treasurer');

    $data = Livewire::actingAs($treasurer)
        ->test(SiteSettings::class)
        ->get('data');

    // The plaintext secret must never reach the Livewire component state, and
    // the secret-bearing fields must stay empty for a site-only editor.
    expect(json_encode($data))->not->toContain('super-secret-key')
        ->and(data_get($data, 'mail.mailgun_secret'))->toBeEmpty()
        ->and(data_get($data, 'storage.secret_key'))->toBeEmpty()
        ->and(data_get($data, 'paystack.secret_key'))->toBeEmpty();
});

it('hydrates integration settings for a developer', function () {
    Cache::flush();
    SiteSetting::put('mail.from.address', 'noreply@pprc.test', ['group' => 'mail']);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $developer = User::factory()->create(['email_verified_at' => now()]);
    $developer->assignRole('developer');

    $data = Livewire::actingAs($developer)
        ->test(SiteSettings::class)
        ->get('data');

    expect(data_get($data, 'mail.from_address'))->toBe('noreply@pprc.test');
});
