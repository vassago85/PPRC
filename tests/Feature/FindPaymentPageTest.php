<?php

use App\Enums\MatchPaymentMethod;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Pages\FindPayment;
use App\Models\EventRegistration;
use App\Models\MembershipPayment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Mail::fake();
    Cache::forget('site_settings:payments.bank.reference_prefix');

    $this->treasurer = User::factory()->create(['email_verified_at' => now()]);
    $this->treasurer->assignRole('treasurer');
    $this->actingAs($this->treasurer);
});

it('renders for a committee member who handles money', function () {
    Livewire::test(FindPayment::class)
        ->assertOk()
        ->assertSee('Bank statement line');
});

it('settles a match entry straight off a mangled bank line', function () {
    $entry = refEntry(103, refEvent(14, 'Match Fourteen'), refMember('Jaco', 'Smit'));

    Livewire::test(FindPayment::class)
        ->set('line', 'INVESTECPBPPRC-M14-103')
        ->call('find')
        ->assertSee('Jaco Smit')
        ->assertSee('Match Fourteen')
        ->call('markEntryPaid', $entry->id);

    $entry->refresh();

    expect($entry->paid_at)->not->toBeNull()
        ->and($entry->payment_method)->toBe(MatchPaymentMethod::Eft)
        ->and($entry->marked_paid_by_user_id)->toBe($this->treasurer->id);
});

it('offers the unpaid entry when a member pays with their joining reference', function () {
    $member = refMember('Marius', 'Brummer');
    refMembershipPayment($member, 'PPRC-20260105-0006', PaymentStatus::Confirmed);

    $entry = refEntry(103, refEvent(14, 'Match Fourteen'), $member);

    Livewire::test(FindPayment::class)
        ->set('line', 'ABSA BANK PPRC-20260105-0006')
        ->call('find')
        ->assertSee('Match Fourteen')
        ->assertSee('still owes')
        ->call('markEntryPaid', $entry->id);

    expect($entry->refresh()->paid_at)->not->toBeNull();
});

it('confirms a membership payment and activates the membership', function () {
    $member = refMember('Coenie', 'van Tonder');
    $payment = refMembershipPayment($member, 'PPRC-20260725-0001', PaymentStatus::Submitted);

    Livewire::test(FindPayment::class)
        ->set('line', 'AF BANK PPRC202607250001')
        ->call('find')
        ->assertSee('Coenie van Tonder')
        ->call('confirmMembershipPayment', $payment->id);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Confirmed)
        ->and($payment->membership->refresh()->status)->toBe(MembershipStatus::Active);
});

it('flags the candidates whose amount agrees with the statement', function () {
    refEntry(103, refEvent(14, 'Match Fourteen', 45000), refMember('Jaco', 'Smit'));

    Livewire::test(FindPayment::class)
        ->set('line', 'PPRC-M14-103')
        ->set('amount', 'R 450.00')
        ->call('find')
        ->assertSee('Amount matches');
});

it('does not claim the amount agrees when it does not', function () {
    refEntry(103, refEvent(14, 'Match Fourteen', 45000), refMember('Jaco', 'Smit'));

    Livewire::test(FindPayment::class)
        ->set('line', 'PPRC-M14-103')
        ->set('amount', '500.00')
        ->call('find')
        ->assertDontSee('Amount matches');
});

it('says so plainly when the line matches nothing', function () {
    Livewire::test(FindPayment::class)
        ->set('line', 'ABSA BANK CHARGES')
        ->call('find')
        ->assertSee('Nothing matched that line');
});

it('refuses to settle an entry that is already paid', function () {
    $entry = refEntry(103, refEvent(14, 'Match Fourteen'), refMember('Jaco', 'Smit'), [
        'paid_at' => now()->subDay(),
    ]);

    $before = $entry->paid_at;

    Livewire::test(FindPayment::class)
        ->set('line', 'PPRC-M14-103')
        ->call('find')
        ->call('markEntryPaid', $entry->id);

    expect($entry->refresh()->paid_at->timestamp)->toBe($before->timestamp);
});

it('keeps the page away from members with no money permissions', function () {
    $shooter = User::factory()->create(['email_verified_at' => now()]);
    $shooter->assignRole('member');
    $this->actingAs($shooter);

    expect(FindPayment::canAccess())->toBeFalse();
});

it('blocks someone without registration rights from settling an entry', function () {
    $entry = refEntry(103, refEvent(14, 'Match Fourteen'), refMember('Jaco', 'Smit'));

    // Can reach the page on payments.view alone, but must not be able to touch
    // match entries.
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $viewer->givePermissionTo('payments.view');
    $this->actingAs($viewer);

    expect(fn () => (new FindPayment)->markEntryPaid($entry->id))
        ->toThrow(HttpException::class);

    expect($entry->refresh()->paid_at)->toBeNull();
});

it('hides the settle button from someone without registration rights', function () {
    refEntry(103, refEvent(14, 'Match Fourteen'), refMember('Jaco', 'Smit'));

    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $viewer->givePermissionTo('payments.view');
    $this->actingAs($viewer);

    Livewire::test(FindPayment::class)
        ->set('line', 'PPRC-M14-103')
        ->call('find')
        ->assertSee('Jaco Smit')
        ->assertDontSee('Mark paid');
});

it('leaves a settled entry alone when the reference is right but the money already came in', function () {
    $member = refMember('Jaco', 'Smit');
    refEntry(103, refEvent(14, 'Match Fourteen'), $member, ['paid_at' => now()->subDay()]);

    Livewire::test(FindPayment::class)
        ->set('line', 'PPRC-M14-103')
        ->call('find')
        ->assertSee('Already marked paid')
        ->assertDontSee('Mark paid');

    expect(EventRegistration::whereKey(103)->value('paid_at'))->not->toBeNull();
});

it('does not offer to confirm a membership payment that is already confirmed', function () {
    $member = refMember('Coenie', 'van Tonder');
    refMembershipPayment($member, 'PPRC-20260725-0001', PaymentStatus::Confirmed);

    Livewire::test(FindPayment::class)
        ->set('line', 'PPRC-20260725-0001')
        ->call('find')
        ->assertSee('already confirmed')
        ->assertDontSee('Confirm + activate');

    expect(MembershipPayment::where('reference', 'PPRC-20260725-0001')->value('status'))
        ->toBe(PaymentStatus::Confirmed);
});

it('still records the money when the membership was already activated by hand', function () {
    $member = refMember('Coenie', 'van Tonder');
    $payment = refMembershipPayment($member, 'PPRC-20260725-0001', PaymentStatus::Submitted);

    // An admin got there first and activated the membership manually, which
    // makes activation a no-op — the deposit still has to be recorded.
    $payment->membership->update(['status' => MembershipStatus::Active]);

    Livewire::test(FindPayment::class)
        ->set('line', 'PPRC-20260725-0001')
        ->call('find')
        ->call('confirmMembershipPayment', $payment->id);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Confirmed)
        ->and($payment->confirmed_by_user_id)->toBe($this->treasurer->id);
});
