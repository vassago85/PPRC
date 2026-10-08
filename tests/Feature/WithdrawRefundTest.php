<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\MatchPaymentMethod;
use App\Enums\RegistrationState;
use App\Filament\Admin\Resources\Events\Pages\EditEvent;
use App\Filament\Admin\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Mail\MatchWithdrawalConfirmedMail;
use App\Models\EventRegistration;
use App\Models\User;
use App\Services\Events\MatchDirectorReport;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Mail::fake();

    $this->treasurer = User::factory()->create(['email_verified_at' => now()]);
    $this->treasurer->assignRole('treasurer');
    $this->actingAs($this->treasurer);
});

/** Build a paid, EFT-settled match entry at R450 for a real member. */
function refundableEntry(): EventRegistration
{
    $event = transferMatch('Refund Match', 45000);
    $member = transferShooter();

    return EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
        'payment_method' => MatchPaymentMethod::Eft->value,
    ]);
}

it('hides the Withdraw & refund action on an unpaid entry', function () {
    $entry = refundableEntry();
    $entry->update(['paid_at' => null]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])->assertTableActionHidden('withdraw_refund', $entry);
});

it('withdraws an unpaid entry via the Withdraw action without touching refund fields', function () {
    $entry = refundableEntry();
    $entry->update(['paid_at' => null]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('withdraw', $entry)
        ->assertHasNoTableActionErrors();

    $entry->refresh();

    expect($entry->status)->toBe(EventRegistrationStatus::Cancelled)
        ->and($entry->refunded_at)->toBeNull()
        ->and($entry->refund_paid_at)->toBeNull()
        ->and($entry->wasRefunded())->toBeFalse();
});

it('hides the plain Withdraw action on paid entries so admins use Withdraw & refund instead', function () {
    $entry = refundableEntry();

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertTableActionHidden('withdraw', $entry)
        ->assertTableActionVisible('withdraw_refund', $entry);
});

it('hides both withdraw actions on already-cancelled entries', function () {
    $entry = refundableEntry();
    $entry->update([
        'status' => EventRegistrationStatus::Cancelled,
        'refunded_at' => now(),
        'refunded_amount_cents' => 45000,
        'refunded_method' => MatchPaymentMethod::Eft->value,
    ]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertTableActionHidden('withdraw', $entry)
        ->assertTableActionHidden('withdraw_refund', $entry);
});

it('frees up a capped match spot the moment an entry is withdrawn', function () {
    $event = transferMatch('Tight Match', 45000, [
        'registrations_open' => true,
        'max_entries' => 1,
    ]);

    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDays(5),
    ]);

    // One active entry fills the single-seat cap.
    expect($event->fresh()->registrationState())->toBe(RegistrationState::Full);

    $entry->update(['status' => EventRegistrationStatus::Cancelled]);

    // Cancelled rows are excluded from the active count, so the spot opens up.
    expect($event->fresh()->registrationState())->toBe(RegistrationState::Open);
});

it('hides the Withdraw & refund action once already refunded', function () {
    $entry = refundableEntry();
    $entry->update([
        'status' => EventRegistrationStatus::Cancelled,
        'refunded_at' => now(),
        'refunded_amount_cents' => 45000,
        'refunded_method' => MatchPaymentMethod::Eft->value,
    ]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])->assertTableActionHidden('withdraw_refund', $entry);
});

it('leaves EFT refunds owing until an admin marks them paid', function () {
    $entry = refundableEntry();

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('withdraw_refund', $entry, [
            'amount_rands' => '450.00',
            'method' => MatchPaymentMethod::Eft->value,
            'note' => 'Family emergency, won\'t make it',
            'notify' => true,
        ])
        ->assertHasNoTableActionErrors();

    $entry->refresh();

    expect($entry->status)->toBe(EventRegistrationStatus::Cancelled)
        ->and($entry->refunded_at)->not->toBeNull()
        ->and($entry->refunded_amount_cents)->toBe(45000)
        ->and($entry->refunded_method)->toBe(MatchPaymentMethod::Eft)
        ->and($entry->refunded_note)->toBe('Family emergency, won\'t make it')
        ->and($entry->refunded_by_user_id)->toBe($this->treasurer->id)
        ->and($entry->refund_paid_at)->toBeNull()
        ->and($entry->wasRefunded())->toBeTrue()
        ->and($entry->isRefundPaid())->toBeFalse()
        ->and($entry->isRefundOwing())->toBeTrue();

    Mail::assertSent(MatchWithdrawalConfirmedMail::class, 1);
});

it('stamps cash refunds as paid immediately because the money came out of the float', function () {
    $entry = refundableEntry();

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('withdraw_refund', $entry, [
            'amount_rands' => '450.00',
            'method' => MatchPaymentMethod::Cash->value,
            'notify' => false,
        ])
        ->assertHasNoTableActionErrors();

    $entry->refresh();

    expect($entry->refunded_method)->toBe(MatchPaymentMethod::Cash)
        ->and($entry->refund_paid_at)->not->toBeNull()
        ->and($entry->isRefundPaid())->toBeTrue()
        ->and($entry->isRefundOwing())->toBeFalse();

    Mail::assertNotSent(MatchWithdrawalConfirmedMail::class);
});

it('supports a partial refund', function () {
    $entry = refundableEntry();

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('withdraw_refund', $entry, [
            'amount_rands' => '200.00',
            'method' => MatchPaymentMethod::Eft->value,
            'notify' => false,
        ])
        ->assertHasNoTableActionErrors();

    expect($entry->fresh()->refunded_amount_cents)->toBe(20000);
});

it('offers Mark refund paid only on refunds that are still owing', function () {
    $event = transferMatch('Mark Paid Match', 45000);

    $owing = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
        'refunded_at' => now()->subDay(),
        'refunded_amount_cents' => 45000,
        'refunded_method' => MatchPaymentMethod::Eft->value,
    ]);

    $alreadyPaid = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
        'refunded_at' => now()->subDay(),
        'refunded_amount_cents' => 45000,
        'refunded_method' => MatchPaymentMethod::Cash->value,
        'refund_paid_at' => now()->subDay(),
    ]);

    $neverRefunded = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
    ]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])
        ->assertTableActionVisible('mark_refund_paid', $owing)
        ->assertTableActionHidden('mark_refund_paid', $alreadyPaid)
        ->assertTableActionHidden('mark_refund_paid', $neverRefunded);
});

it('settles an EFT refund by stamping refund_paid_at without sending a second email', function () {
    $entry = EventRegistration::create([
        'event_id' => transferMatch('Settle Match', 45000)->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
        'refunded_at' => now()->subDay(),
        'refunded_amount_cents' => 45000,
        'refunded_method' => MatchPaymentMethod::Eft->value,
    ]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('mark_refund_paid', $entry)
        ->assertHasNoTableActionErrors();

    expect($entry->fresh()->refund_paid_at)->not->toBeNull();
    Mail::assertNotSent(MatchWithdrawalConfirmedMail::class);
});

it('exposes refund totals on the match director summary split by paid vs owing', function () {
    $event = transferMatch('Director Summary Match', 45000);

    // A paid shooter who sticks around — proves refunds don't double-subtract.
    EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
        'payment_method' => MatchPaymentMethod::Eft->value,
    ]);

    // Owing EFT refund — the money is still with the club waiting for payout.
    EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
        'refunded_at' => now()->subDay(),
        'refunded_amount_cents' => 45000,
        'refunded_method' => MatchPaymentMethod::Eft->value,
    ]);

    // Cash refund that was handed back on the day — already out.
    EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
        'refunded_at' => now()->subDay(),
        'refunded_amount_cents' => 30000,
        'refunded_method' => MatchPaymentMethod::Cash->value,
        'refund_paid_at' => now()->subDay(),
    ]);

    $summary = (new MatchDirectorReport($event->fresh()))->summary();

    expect($summary['refunds_count'])->toBe(2)
        ->and($summary['refunds_total_cents'])->toBe(75000)
        ->and($summary['refunds_paid_cents'])->toBe(30000)
        ->and($summary['refunds_owing_cents'])->toBe(45000)
        ->and($summary['refunds_owing_count'])->toBe(1)
        // Only the staying shooter is in the EFT base; cancelled refund rows
        // are excluded naturally, so director payout is 45000 - 0 - 0 - 0.
        ->and($summary['eft_base_cents'])->toBe(45000)
        ->and($summary['director_payout_cents'])->toBe(45000)
        ->and($summary['payout_count'])->toBe(1);
});
