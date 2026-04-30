<?php

namespace App\Livewire\Portal;

use App\Enums\PaymentProvider;
use App\Enums\ShopOrderStatus;
use App\Models\Member;
use App\Models\ShopOrder;
use App\Models\ShopOrderLine;
use App\Models\ShopProduct;
use App\Models\ShopRun;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.portal.layout')]
#[Title('Shop order')]
class ShopCheckout extends Component
{
    use WithFileUploads;

    public ShopRun $run;

    public int $orderId;

    /** @var array<int, int> product_id => quantity */
    public array $qty = [];

    public string $ship_to_name = '';

    public string $ship_phone = '';

    public string $ship_line1 = '';

    public string $ship_line2 = '';

    public string $ship_city = '';

    public string $ship_province = '';

    public string $ship_postal_code = '';

    public string $ship_country = 'ZA';

    public $proofUpload = null;

    public function mount(ShopRun $run): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless($run->isAcceptingOrders(), 404);

        $this->run = $run;

        $member = auth()->user()?->member;
        abort_unless($member instanceof Member, 403);

        $products = $this->products();
        foreach ($products as $product) {
            $this->qty[$product->id] = 0;
        }

        $order = ShopOrder::query()->firstOrCreate(
            [
                'shop_run_id' => $this->run->id,
                'user_id' => auth()->id(),
            ],
            [
                'status' => ShopOrderStatus::Draft,
                'currency' => 'ZAR',
                'subtotal_cents' => 0,
                'shipping_cents' => 0,
                'total_cents' => 0,
            ],
        );
        $this->orderId = $order->id;

        $order->load('lines');
        foreach ($order->lines as $line) {
            $this->qty[$line->shop_product_id] = $line->quantity;
        }

        if ($order->ship_to_name) {
            $this->ship_to_name = (string) $order->ship_to_name;
            $this->ship_phone = (string) $order->ship_phone;
            $this->ship_line1 = (string) $order->ship_line1;
            $this->ship_line2 = (string) ($order->ship_line2 ?? '');
            $this->ship_city = (string) $order->ship_city;
            $this->ship_province = (string) $order->ship_province;
            $this->ship_postal_code = (string) $order->ship_postal_code;
            $this->ship_country = (string) ($order->ship_country ?? 'ZA');
        } else {
            $this->prefillShippingFromMember($member);
        }
    }

    protected function prefillShippingFromMember(Member $member): void
    {
        $this->ship_to_name = $member->fullName();
        $phone = trim((string) ($member->phone_country_code ?? '').' '.(string) ($member->phone_number ?? ''));
        $this->ship_phone = $phone !== '' ? $phone : '';
        $this->ship_line1 = (string) ($member->address_line1 ?? '');
        $this->ship_line2 = (string) ($member->address_line2 ?? '');
        $this->ship_city = (string) ($member->city ?? '');
        $this->ship_province = (string) ($member->province ?? '');
        $this->ship_postal_code = (string) ($member->postal_code ?? '');
        $this->ship_country = (string) ($member->country ?: 'ZA');
    }

    #[Computed]
    public function member(): ?Member
    {
        return auth()->user()?->member;
    }

    #[Computed]
    public function products()
    {
        return ShopProduct::query()
            ->where('shop_run_id', $this->run->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function order(): ShopOrder
    {
        return ShopOrder::query()->with('lines.product')->findOrFail($this->orderId);
    }

    public function placeOrder(): void
    {
        abort_unless($this->run->isAcceptingOrders(), 403);

        $this->validate([
            'ship_to_name' => ['required', 'string', 'max:150'],
            'ship_phone' => ['required', 'string', 'max:64'],
            'ship_line1' => ['required', 'string', 'max:255'],
            'ship_line2' => ['nullable', 'string', 'max:255'],
            'ship_city' => ['required', 'string', 'max:120'],
            'ship_province' => ['required', 'string', 'max:120'],
            'ship_postal_code' => ['required', 'string', 'max:32'],
            'ship_country' => ['required', 'string', 'max:120'],
        ]);

        $products = $this->products()->keyBy('id');
        $lines = [];
        foreach ($this->qty as $productId => $quantity) {
            $q = max(0, (int) $quantity);
            if ($q === 0) {
                continue;
            }
            $product = $products->get((int) $productId);
            if (! $product) {
                continue;
            }
            if ($product->max_per_order !== null && $q > $product->max_per_order) {
                $this->addError('qty.'.$productId, 'Maximum '.$product->max_per_order.' per order for this item.');

                return;
            }
            $lines[] = ['product' => $product, 'quantity' => $q];
        }

        if ($lines === []) {
            $this->addError('qty', 'Select at least one product with a quantity greater than zero.');

            return;
        }

        $order = $this->order();

        if (in_array($order->status, [ShopOrderStatus::Paid, ShopOrderStatus::Fulfilled], true)) {
            session()->flash('flash', 'This order is already complete.');

            return;
        }

        if ($order->status === ShopOrderStatus::PendingPayment && $order->eft_reference) {
            session()->flash('flash', 'You already have a pending payment for this run. Upload proof below if you have not yet.');

            return;
        }

        DB::transaction(function () use ($order, $lines): void {
            $order->lines()->delete();

            foreach ($lines as $row) {
                /** @var ShopProduct $product */
                $product = $row['product'];
                $q = $row['quantity'];
                $unit = $product->price_cents;
                ShopOrderLine::query()->create([
                    'shop_order_id' => $order->id,
                    'shop_product_id' => $product->id,
                    'quantity' => $q,
                    'unit_price_cents' => $unit,
                    'line_total_cents' => $unit * $q,
                ]);
            }

            $order->refresh();
            $order->recalculateTotals();

            $eftRef = 'PPRC-SHP-'.$order->id.'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            while (ShopOrder::query()->where('eft_reference', $eftRef)->exists()) {
                $eftRef = 'PPRC-SHP-'.$order->id.'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            }

            $order->update([
                'ship_to_name' => $this->ship_to_name,
                'ship_phone' => $this->ship_phone,
                'ship_line1' => $this->ship_line1,
                'ship_line2' => $this->ship_line2 ?: null,
                'ship_city' => $this->ship_city,
                'ship_province' => $this->ship_province,
                'ship_postal_code' => $this->ship_postal_code,
                'ship_country' => $this->ship_country,
                'status' => ShopOrderStatus::PendingPayment,
                'payment_provider' => PaymentProvider::ManualEft,
                'eft_reference' => $eftRef,
                'submitted_at' => null,
            ]);
        });

        unset($this->order);
        session()->flash('flash', 'Order placed. Use the EFT reference below and upload proof when paid.');
    }

    public function uploadProof(): void
    {
        $this->validate([
            'proofUpload' => ['required', 'file', 'max:8192'],
        ]);

        $order = $this->order();

        abort_unless($order->status === ShopOrderStatus::PendingPayment, 403);
        abort_unless($order->eft_reference, 403);

        $path = $this->proofUpload->store('shop/orders/proofs', \App\Support\MediaDisk::name());

        $order->update([
            'proof_path' => $path,
            'submitted_at' => now(),
        ]);

        $this->proofUpload = null;
        unset($this->order);
        session()->flash('flash', 'Proof of payment uploaded. The committee will verify your order.');
    }

    public function render(): mixed
    {
        return view('livewire.portal.shop-checkout', [
            'bank' => [
                'account_name' => (string) SiteSetting::get('payments.bank.account_name', ''),
                'bank' => (string) SiteSetting::get('payments.bank.bank', ''),
                'account_number' => (string) SiteSetting::get('payments.bank.account_number', ''),
                'branch_code' => (string) SiteSetting::get('payments.bank.branch_code', ''),
                'notes' => (string) SiteSetting::get('payments.bank.notes', ''),
            ],
            'paystackEnabled' => config('shop.paystack_enabled', false),
        ]);
    }
}
