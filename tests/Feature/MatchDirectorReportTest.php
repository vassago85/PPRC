<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Services\Events\MatchDirectorReport;

function reportMatch(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Payout Match',
        'start_date' => now()->subDay()->toDateString(),
        'status' => EventStatus::Completed,
        'member_price_cents' => 20000,
        'non_member_price_cents' => 25000,
    ]);
}

function reportEntry(Event $event, array $attrs): EventRegistration
{
    $name = $attrs['guest_name'] ?? 'Shooter';

    return EventRegistration::create(array_merge([
        'event_id' => $event->id,
        'guest_name' => 'Shooter',
        'guest_email' => \Illuminate\Support\Str::slug($name).'@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ], $attrs));
}

it('classifies entries and totals the payout with a per-head levy', function () {
    $event = reportMatch();

    // Paid (EFT) and shot -> counts toward payout the club owes (fee 250.00 as guest)
    reportEntry($event, ['guest_name' => 'Paid Shot', 'paid_at' => now(), 'attended' => true]);
    // Paid but no-show -> held as credit
    reportEntry($event, ['guest_name' => 'Paid NoShow', 'paid_at' => now(), 'attended' => false]);
    // Owes, not paid -> outstanding
    reportEntry($event, ['guest_name' => 'Owing', 'attended' => true]);
    // Free entry (waived) -> free, no money
    reportEntry($event, ['guest_name' => 'Comped', 'fee_cents' => 0, 'attended' => true]);
    // Cancelled -> excluded entirely
    reportEntry($event, ['guest_name' => 'Gone', 'status' => EventRegistrationStatus::Cancelled, 'paid_at' => now(), 'attended' => true]);

    // Club keeps R50 per paying shooter who shot.
    $report = new MatchDirectorReport($event, 5000);
    $s = $report->summary();

    expect($s['entries_total'])->toBe(4);
    expect($s['payout_count'])->toBe(1);
    expect($s['credit_count'])->toBe(1);
    expect($s['awaiting_count'])->toBe(1);
    expect($s['free_count'])->toBe(1);

    // Gross collected = the two paid entries (250 + 250 = 500.00)
    expect($s['gross_collected_cents'])->toBe(50000);
    // Payout base = the single paid-and-shot entry
    expect($s['payout_base_cents'])->toBe(25000);
    // No cash recorded -> the whole payout base is EFT
    expect($s['eft_base_cents'])->toBe(25000);
    expect($s['cash_base_cents'])->toBe(0);
    expect($s['credit_cents'])->toBe(25000);
    expect($s['outstanding_cents'])->toBe(25000);

    // Levy = R50 x 1 paying shooter; director gets 250 - 50 = 200.00
    expect($s['levy_total_cents'])->toBe(5000);
    expect($s['director_payout_cents'])->toBe(20000);
});

it('excludes cash from the payout the club owes but still counts it collected', function () {
    $event = reportMatch();

    // EFT paid + shot -> owed to director by the club
    reportEntry($event, ['guest_name' => 'Eft Shooter', 'paid_at' => now(), 'payment_method' => 'eft', 'attended' => true]);
    // Cash paid + shot -> director already has it, not owed again
    reportEntry($event, ['guest_name' => 'Cash Shooter', 'paid_at' => now(), 'payment_method' => 'cash', 'attended' => true]);

    $report = new MatchDirectorReport($event, 0);
    $s = $report->summary();

    expect($s['payout_count'])->toBe(2);
    expect($s['cash_count'])->toBe(1);
    expect($s['eft_base_cents'])->toBe(25000);
    expect($s['cash_base_cents'])->toBe(25000);
    expect($s['gross_collected_cents'])->toBe(50000);

    // Club owes only the EFT money (no levy set)
    expect($s['director_payout_cents'])->toBe(25000);
});

it('never returns a negative payout when the levy exceeds fees', function () {
    $event = reportMatch();
    reportEntry($event, ['guest_name' => 'Small Fee', 'fee_cents' => 1000, 'paid_at' => now(), 'attended' => true]);

    $report = new MatchDirectorReport($event, 5000); // levy bigger than fee
    $s = $report->summary();

    expect($s['director_payout_cents'])->toBe(0);
});

it('excludes cancelled entries from the rows', function () {
    $event = reportMatch();
    reportEntry($event, ['guest_name' => 'Active', 'paid_at' => now(), 'attended' => true]);
    reportEntry($event, ['guest_name' => 'Cancelled One', 'status' => EventRegistrationStatus::Cancelled]);

    $rows = (new MatchDirectorReport($event))->rows();

    expect($rows->pluck('name'))->toContain('Active')
        ->not->toContain('Cancelled One');
});
