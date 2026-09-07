<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Enums\ShopOrderStatus;
use App\Enums\ShopRunStatus;
use App\Invoices\InvoiceUrl;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\ShopOrder;
use App\Models\ShopOrderLine;
use App\Models\ShopProduct;
use App\Models\ShopRun;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function invoiceShopOrder(User $user, array $overrides = []): ShopOrder
{
    $run = ShopRun::create([
        'title' => 'Club shirts',
        'slug' => 'club-shirts-'.uniqid(),
        'status' => ShopRunStatus::Open,
    ]);

    $product = ShopProduct::create([
        'shop_run_id' => $run->id,
        'name' => 'Club shirt',
        'slug' => 'club-shirt',
        'price_cents' => 35000,
    ]);

    $order = ShopOrder::create(array_merge([
        'shop_run_id' => $run->id,
        'user_id' => $user->id,
        'status' => ShopOrderStatus::PendingPayment,
        'ship_to_name' => $user->name,
        'subtotal_cents' => 35000,
        'shipping_cents' => 5000,
        'total_cents' => 40000,
        'currency' => 'ZAR',
        'eft_reference' => 'PPRC-SHP-'.$run->id.'-ABCDEF12',
    ], $overrides));

    ShopOrderLine::create([
        'shop_order_id' => $order->id,
        'shop_product_id' => $product->id,
        'quantity' => 1,
        'unit_price_cents' => 35000,
        'line_total_cents' => 35000,
    ]);

    return $order->fresh(['lines.product', 'user', 'run']);
}

it('renders a membership invoice for the paying member', function () {
    $member = refMember('Jaco', 'Smit');
    $payment = refMembershipPayment($member, 'PPRC-20260105-0006', PaymentStatus::Confirmed, 60000);

    $this->actingAs($member->user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Membership, 'id' => $payment->id]))
        ->assertOk()
        ->assertSee('Invoice')
        ->assertSee('PPRC-20260105-0006')
        ->assertSee('Jaco Smit')
        ->assertSee('R 600.00')
        ->assertSee('Paid');
});

it('renders a match invoice with the event fee and reference', function () {
    $member = refMember('Lize', 'Botha');
    $entry = paidEntry(transferMatch('Winter PRS', 45000), $member, ['fee_cents' => 45000]);

    $this->actingAs($member->user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Match, 'id' => $entry->id]))
        ->assertOk()
        ->assertSee('Winter PRS')
        ->assertSee($entry->paymentReference())
        ->assertSee('Lize Botha')
        ->assertSee('R 450.00');
});

it('renders a shop invoice with line items and shipping', function () {
    $user = User::factory()->create();
    $order = invoiceShopOrder($user);

    $this->actingAs($user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Shop, 'id' => $order->id]))
        ->assertOk()
        ->assertSee('Club shirt')
        ->assertSee('Shipping')
        ->assertSee($order->eft_reference)
        ->assertSee('R 400.00')
        ->assertSee('Amount due');
});

it('lets a household adult open a junior membership invoice', function () {
    $adult = refMember('Pat', 'Parent');
    $junior = Member::factory()->create([
        'first_name' => 'Kid',
        'last_name' => 'Parent',
        'linked_adult_member_id' => $adult->id,
        'date_of_birth' => now()->subYears(14)->toDateString(),
    ]);
    $payment = refMembershipPayment($junior, 'PPRC-20260301-0002', PaymentStatus::Pending, 25000);

    $this->actingAs($adult->user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Membership, 'id' => $payment->id]))
        ->assertOk()
        ->assertSee('Kid Parent')
        ->assertSee('Pat Parent')
        ->assertSee('PPRC-20260301-0002');
});

it('forbids a stranger from opening another shooter\'s invoice', function () {
    $owner = refMember('Jaco', 'Smit');
    $stranger = refMember('Other', 'Shooter');
    $payment = refMembershipPayment($owner, 'PPRC-20260105-0006', PaymentStatus::Confirmed);

    $this->actingAs($stranger->user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Membership, 'id' => $payment->id]))
        ->assertForbidden();
});

it('lets a treasurer open a membership invoice', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $member = refMember('Jaco', 'Smit');
    $payment = refMembershipPayment($member, 'PPRC-20260105-0006', PaymentStatus::Confirmed);
    $treasurer = User::factory()->create();
    $treasurer->assignRole('treasurer');

    $this->actingAs($treasurer)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Membership, 'id' => $payment->id]))
        ->assertOk()
        ->assertSee('PPRC-20260105-0006');
});

it('opens a guest match invoice from a signed URL', function () {
    $event = transferMatch('Open Day', 20000);
    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Jane Guest',
        'guest_email' => 'guest@example.com',
        'fee_cents' => 20000,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $this->get(InvoiceUrl::signed(InvoiceType::Match, $entry->id))
        ->assertOk()
        ->assertSee('Jane Guest')
        ->assertSee($entry->paymentReference())
        ->assertSee('R 200.00');
});

it('rejects an unsigned public invoice URL', function () {
    $member = refMember('Jaco', 'Smit');
    $payment = refMembershipPayment($member, 'PPRC-20260105-0006', PaymentStatus::Confirmed);

    $this->get(route('invoices.show', ['type' => InvoiceType::Membership, 'id' => $payment->id]))
        ->assertForbidden();
});

it('does not invoice a waived or SAPRF match entry', function () {
    $member = refMember('Jaco', 'Smit');

    $waived = EventRegistration::create([
        'event_id' => transferMatch('Club match', 45000)->id,
        'member_id' => $member->id,
        'fee_cents' => 0,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now(),
    ]);

    $saprf = EventRegistration::create([
        'event_id' => transferMatch('SAPRF qualifier', 45000)->id,
        'member_id' => $member->id,
        'is_saprf_entry' => true,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now(),
    ]);

    $this->actingAs($member->user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Match, 'id' => $waived->id]))
        ->assertNotFound();

    $this->actingAs($member->user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Match, 'id' => $saprf->id]))
        ->assertNotFound();
});

it('does not invoice a cancelled shop order or a failed membership payment', function () {
    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id]);
    $failed = refMembershipPayment($member, 'PPRC-20260105-0099', PaymentStatus::Failed);
    $order = invoiceShopOrder($user, ['status' => ShopOrderStatus::Cancelled]);

    $this->actingAs($user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Membership, 'id' => $failed->id]))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('portal.invoices.show', ['type' => InvoiceType::Shop, 'id' => $order->id]))
        ->assertNotFound();
});
