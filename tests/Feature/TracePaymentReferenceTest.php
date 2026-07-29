<?php

use App\Enums\PaymentStatus;
use App\Models\EmailLog;
use App\Models\MembershipPayment;
use Illuminate\Support\Facades\DB;

/**
 * The whole point of the trace is to find things the normal screens hide, so the
 * soft-deleted case is the one that actually matters.
 */
function tracedPayment(string $reference = 'PPRC-20260105-0006'): MembershipPayment
{
    return refMembershipPayment(
        refMember('Etienne', 'Hennop'),
        $reference,
        PaymentStatus::Pending,
    );
}

it('finds a live membership payment', function () {
    $payment = tracedPayment();

    $this->artisan('payments:trace', ['reference' => $payment->reference])
        ->expectsOutputToContain('Membership payments')
        ->assertExitCode(0);
});

it('finds a payment that was deleted, which is exactly what the admin screens cannot do', function () {
    $payment = tracedPayment();
    $payment->delete();

    expect(MembershipPayment::query()->where('reference', $payment->reference)->exists())->toBeFalse();

    $this->artisan('payments:trace', ['reference' => $payment->reference])
        ->expectsOutputToContain('soft-deleted')
        ->assertExitCode(0);
});

it('reports honestly when nothing holds the reference', function () {
    $this->artisan('payments:trace', ['reference' => 'PPRC-20260105-9999'])
        ->expectsOutputToContain('No record anywhere holds this reference')
        ->assertExitCode(1);
});

it('says whether we ever emailed the reference to anybody', function () {
    $payment = tracedPayment();

    EmailLog::create([
        'to_email' => 'shooter@example.com',
        'subject' => "PPRC membership payment — reference {$payment->reference}",
        'status' => EmailLog::STATUS_SENT,
        'sent_at' => now()->subMonths(6),
    ]);

    $this->artisan('payments:trace', ['reference' => $payment->reference])
        ->expectsOutputToContain('shooter@example.com')
        ->assertExitCode(0);
});

it('says plainly when the reference was never emailed', function () {
    $payment = tracedPayment();

    $this->artisan('payments:trace', ['reference' => $payment->reference])
        ->expectsOutputToContain('no record of sending this reference')
        ->assertExitCode(0);
});

it('finds a match entry by its stored reference', function () {
    $entry = refEntry(103, refEvent(14, 'Club Match'));

    $this->artisan('payments:trace', ['reference' => $entry->paymentReference()])
        ->expectsOutputToContain('Match entries')
        ->assertExitCode(0);
});

it('finds a reference that turned out to be a membership number', function () {
    $member = refMember('Lee', 'Thompson');
    DB::table('members')->where('id', $member->id)->update(['membership_number' => 'PPRC-20260105-0006']);

    $this->artisan('payments:trace', ['reference' => 'PPRC-20260105-0006'])
        ->expectsOutputToContain('Membership numbers')
        ->assertExitCode(0);
});

it('writes nothing', function () {
    $payment = tracedPayment();
    $before = DB::table('membership_payments')->where('id', $payment->id)->first();

    expect($before)->not->toBeNull();

    $this->artisan('payments:trace', ['reference' => $payment->reference])->assertExitCode(0);

    expect(DB::table('membership_payments')->where('id', $payment->id)->first())->toEqual($before);
});
