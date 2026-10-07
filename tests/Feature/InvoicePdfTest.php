<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\InvoiceType;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Invoices\InvoiceUrl;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;

function invoiceMatch(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Invoice Test Match',
        'start_date' => now()->subWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 45000,
        'non_member_price_cents' => 50000,
    ]);
}

it('serves a match invoice as a PDF for the member who owns it', function () {
    $member = Member::factory()->active()->create();
    $event = invoiceMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDays(2),
        'paid_at' => now()->subDay(),
    ]);

    $this->actingAs($member->user);

    $response = $this->get(InvoiceUrl::portal(InvoiceType::Match, $registration->id));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('inline');
    expect($response->headers->get('Content-Disposition'))->toContain('.pdf');
    // Dompdf always produces a valid PDF stream — magic bytes "%PDF".
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('forces a download when ?download=1 is supplied', function () {
    $member = Member::factory()->active()->create();
    $event = invoiceMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDays(2),
        'paid_at' => now()->subDay(),
    ]);

    $this->actingAs($member->user);

    $response = $this->get(InvoiceUrl::portal(InvoiceType::Match, $registration->id).'?download=1');

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('refuses to serve another household\'s match invoice', function () {
    $owner = Member::factory()->active()->create();
    $outsider = Member::factory()->active()->create();
    $event = invoiceMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $owner->id,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDays(2),
        'paid_at' => now()->subDay(),
    ]);

    $this->actingAs($outsider->user)
        ->get(InvoiceUrl::portal(InvoiceType::Match, $registration->id))
        ->assertForbidden();
});

it('serves a membership-payment invoice as a PDF', function () {
    $member = Member::factory()->create();

    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::Active,
    ]);

    $payment = MembershipPayment::create([
        'membership_id' => $membership->id,
        'provider' => 'manual_eft',
        'status' => PaymentStatus::Confirmed,
        'amount_cents' => 120000,
        'currency' => 'ZAR',
        'reference' => 'PPRC-MEM-TEST',
        'confirmed_at' => now()->subDay(),
    ]);

    $this->actingAs($member->user);

    $response = $this->get(InvoiceUrl::portal(InvoiceType::Membership, $payment->id));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('returns 404 when the match entry has no fee to invoice (SAPRF-paid)', function () {
    $member = Member::factory()->active()->create();
    $event = invoiceMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now(),
        'is_saprf_entry' => true,
    ]);

    $this->actingAs($member->user)
        ->get(InvoiceUrl::portal(InvoiceType::Match, $registration->id))
        ->assertNotFound();
});
