<?php

use App\Enums\MatchExpenseCategory;
use App\Filament\Admin\Resources\Events\Pages\MatchReport;
use App\Models\MatchExpense;
use App\Models\User;
use App\Services\Events\MatchDirectorReport;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->treasurer = User::factory()->create([
        'name' => 'Casey Treasurer',
        'email_verified_at' => now(),
    ]);
    $this->treasurer->assignRole('treasurer');
    $this->actingAs($this->treasurer);
});

it('persists a trophies expense linked to a club member payee', function () {
    $event = transferMatch('Trophies Match', 45000);
    $dirk = User::factory()->create(['name' => 'Dirk Director']);

    Livewire::test(MatchReport::class, ['record' => $event->slug])
        ->set('newExpenseDescription', 'Trophies for class winners')
        ->set('newExpenseCategory', MatchExpenseCategory::Trophies->value)
        ->set('newExpensePayeeUserId', $dirk->id)
        ->set('newExpenseAmount', 1250.50)
        ->call('addExpense');

    $expense = MatchExpense::query()->where('event_id', $event->id)->firstOrFail();

    expect($expense->category)->toBe(MatchExpenseCategory::Trophies)
        ->and($expense->payee_user_id)->toBe($dirk->id)
        ->and($expense->payee_name)->toBe('Dirk Director')
        ->and($expense->amount_cents)->toBe(125050)
        ->and($expense->payeeDisplay())->toBe('Dirk Director');
});

it('persists a free-text payee when no user is picked', function () {
    $event = transferMatch('Range Fees Match', 45000);

    Livewire::test(MatchReport::class, ['record' => $event->slug])
        ->set('newExpenseDescription', 'Range fees')
        ->set('newExpenseCategory', MatchExpenseCategory::RangeFees->value)
        ->set('newExpensePayee', 'Legends Adventure Farm')
        ->set('newExpenseAmount', 500)
        ->call('addExpense');

    $expense = MatchExpense::query()->where('event_id', $event->id)->firstOrFail();

    expect($expense->category)->toBe(MatchExpenseCategory::RangeFees)
        ->and($expense->payee_user_id)->toBeNull()
        ->and($expense->payee_name)->toBe('Legends Adventure Farm')
        ->and($expense->payeeDisplay())->toBe('Legends Adventure Farm');
});

it('defaults the category to Other when the admin leaves it alone', function () {
    $event = transferMatch('Default Category Match', 45000);

    Livewire::test(MatchReport::class, ['record' => $event->slug])
        ->set('newExpenseDescription', 'Catering')
        ->set('newExpensePayee', 'Mrs Smith')
        ->set('newExpenseAmount', 200)
        ->call('addExpense');

    expect(MatchExpense::query()->firstOrFail()->category)->toBe(MatchExpenseCategory::Other);
});

it('resets the form after adding a cost', function () {
    $event = transferMatch('Form Reset Match', 45000);
    $dirk = User::factory()->create(['name' => 'Dirk Director']);

    Livewire::test(MatchReport::class, ['record' => $event->slug])
        ->set('newExpenseDescription', 'Medals')
        ->set('newExpenseCategory', MatchExpenseCategory::Medals->value)
        ->set('newExpensePayeeUserId', $dirk->id)
        ->set('newExpenseAmount', 150)
        ->call('addExpense')
        ->assertSet('newExpenseDescription', '')
        ->assertSet('newExpensePayee', '')
        ->assertSet('newExpenseAmount', null)
        ->assertSet('newExpenseCategory', MatchExpenseCategory::Other->value)
        ->assertSet('newExpensePayeeUserId', null);
});

it('keeps the director payout maths unchanged with structured categories', function () {
    $event = transferMatch('Payout Math Match', 45000);
    $dirk = User::factory()->create(['name' => 'Dirk Director']);

    // Two paid shooters so there's an EFT pot to deduct from.
    foreach (range(1, 2) as $i) {
        paidEntry($event, transferShooter(), [
            'fee_cents' => 45000,
            'payment_method' => 'eft',
        ]);
    }

    // Dirk fronted R1 250 for trophies, range fees are R600.
    MatchExpense::create([
        'event_id' => $event->id,
        'description' => 'Trophies',
        'category' => MatchExpenseCategory::Trophies->value,
        'payee_user_id' => $dirk->id,
        'payee_name' => 'Dirk Director',
        'amount_cents' => 125000,
        'created_by_user_id' => $this->treasurer->id,
    ]);
    MatchExpense::create([
        'event_id' => $event->id,
        'description' => 'Range fees',
        'category' => MatchExpenseCategory::RangeFees->value,
        'payee_name' => 'Legends',
        'amount_cents' => 60000,
        'created_by_user_id' => $this->treasurer->id,
    ]);

    $summary = (new MatchDirectorReport($event->fresh()))->summary();

    // 2 * R450 = R900; subtract R1250 trophies + R600 range = -R950, floors to 0.
    expect($summary['eft_base_cents'])->toBe(90000)
        ->and($summary['expenses_total_cents'])->toBe(185000)
        ->and($summary['director_payout_cents'])->toBe(0);
});

it('serves the slip without blowing up when a structured expense exists', function () {
    $event = transferMatch('Slip Render Match', 45000);
    $dirk = User::factory()->create(['name' => 'Dirk Director']);

    MatchExpense::create([
        'event_id' => $event->id,
        'description' => 'Trophies for class winners',
        'category' => MatchExpenseCategory::Trophies->value,
        'payee_user_id' => $dirk->id,
        'payee_name' => 'Dirk Director',
        'amount_cents' => 125000,
        'created_by_user_id' => $this->treasurer->id,
    ]);

    Livewire::test(MatchReport::class, ['record' => $event->slug])
        ->assertStatus(200)
        ->assertSee('Trophies for class winners')
        ->assertSee('Trophies')          // category badge
        ->assertSee('Dirk Director')     // linked payee display name
        ->assertSee('reimbursed off the top');
});
