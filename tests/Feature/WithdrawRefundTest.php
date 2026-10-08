<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\MatchPaymentMethod;
use App\Filament\Admin\Resources\Events\Pages\EditEvent;
use App\Filament\Admin\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Mail\MatchEntryRefundIssuedMail;
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

it('cancels the entry and stamps every refund field', function () {
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
        ->and($entry->wasRefunded())->toBeTrue();

    Mail::assertSent(MatchEntryRefundIssuedMail::class, 1);
});

it('skips the notification email when notify is off', function () {
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

    expect($entry->fresh()->refunded_method)->toBe(MatchPaymentMethod::Cash);

    Mail::assertNotSent(MatchEntryRefundIssuedMail::class);
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

it('exposes refund totals on the match director summary', function () {
    $entry = refundableEntry();
    $event = $entry->event;

    // A second paid shooter who sticks around — their EFT must stay in the
    // payout base so we can prove the refund doesn't double-subtract.
    $staying = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDays(5),
        'paid_at' => now()->subDays(3),
        'payment_method' => MatchPaymentMethod::Eft->value,
    ]);

    // Refund the first shooter.
    $entry->update([
        'status' => EventRegistrationStatus::Cancelled,
        'refunded_at' => now(),
        'refunded_amount_cents' => 45000,
        'refunded_method' => MatchPaymentMethod::Eft->value,
    ]);

    $summary = (new MatchDirectorReport($event->fresh()))->summary();

    expect($summary['refunds_total_cents'])->toBe(45000)
        ->and($summary['refunds_count'])->toBe(1)
        // Only the staying shooter is in the EFT base; refunded cancelled row
        // is excluded naturally, so director payout is 45000 - 0 - 0 - 0.
        ->and($summary['eft_base_cents'])->toBe(45000)
        ->and($summary['director_payout_cents'])->toBe(45000)
        ->and($summary['payout_count'])->toBe(1);
});
