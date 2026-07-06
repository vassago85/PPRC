<?php

use App\Enums\MatchCreditStatus;
use App\Filament\Admin\Resources\MatchCredits\Pages\CreateMatchCredit;
use App\Filament\Admin\Resources\MatchCredits\Pages\ListMatchCredits;
use App\Models\MatchCredit;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('fills payee name and email from the linked member', function () {
    $user = User::factory()->create(['email' => 'linked@example.com']);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'first_name' => 'Linda',
        'last_name' => 'Knox',
    ]);

    $credit = MatchCredit::create([
        'member_id' => $member->id,
        'amount_cents' => 25000,
    ]);

    expect($credit->payee_name)->toBe($member->fullName());
    expect($credit->payee_email)->toBe('linked@example.com');
    expect($credit->payeeName())->toBe($member->fullName());
});

it('keeps a guest payee name and falls back gracefully', function () {
    $guest = MatchCredit::create([
        'payee_name' => 'Walk-in Willie',
        'payee_email' => 'willie@example.com',
        'amount_cents' => 20000,
    ]);

    expect($guest->payeeName())->toBe('Walk-in Willie');

    $nameless = MatchCredit::create([
        'payee_email' => 'onlyemail@example.com',
        'amount_cents' => 20000,
    ]);

    expect($nameless->payeeName())->toBe('onlyemail@example.com');
});

it('scopes available credits', function () {
    MatchCredit::create(['payee_name' => 'A', 'amount_cents' => 100, 'status' => MatchCreditStatus::Available->value]);
    MatchCredit::create(['payee_name' => 'B', 'amount_cents' => 100, 'status' => MatchCreditStatus::Used->value]);

    expect(MatchCredit::query()->available()->count())->toBe(1);
});

it('renders the match credits list', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('match_director');
    $this->actingAs($admin);

    MatchCredit::create(['payee_name' => 'Owed Person', 'amount_cents' => 30000]);

    Livewire::test(ListMatchCredits::class)->assertOk();
});

it('creates a credit converting rands to cents', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('match_director');
    $this->actingAs($admin);

    Livewire::test(CreateMatchCredit::class)
        ->fillForm([
            'payee_name' => 'Guest Grace',
            'amount_cents' => 250,
            'status' => MatchCreditStatus::Available->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $credit = MatchCredit::query()->where('payee_name', 'Guest Grace')->first();

    expect($credit)->not->toBeNull();
    expect($credit->amount_cents)->toBe(25000);
    expect($credit->created_by_user_id)->toBe($admin->id);
});
