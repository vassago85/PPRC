<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
    <header>
        <p class="text-sm text-slate-600"><a href="{{ route('shop') }}" class="hover:text-slate-900">Shop</a> / {{ $run->title }}</p>
        <h1 class="text-2xl font-semibold text-slate-900 mt-1">Place order</h1>
    </header>

    @if (session('flash'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            {{ session('flash') }}
        </div>
    @endif

    @if (! $this->member)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-6">
            <p class="text-sm text-amber-900">You need a member profile linked to your account to order club apparel.</p>
        </div>
    @else
        @php($o = $this->order)

        @if (in_array($o->status, [\App\Enums\ShopOrderStatus::Paid, \App\Enums\ShopOrderStatus::Fulfilled], true))
            <div class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-700">
                This order is marked <strong>{{ $o->status->label() }}</strong>. Thank you.
            </div>
        @else
            <section class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="text-lg font-medium text-slate-900">Products</h2>
                <div class="divide-y divide-slate-100">
                    @foreach ($this->products as $product)
                        <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                <p class="text-sm text-slate-600">R {{ number_format($product->price_cents / 100, 2) }} each</p>
                                @if ($product->max_per_order)
                                    <p class="text-xs text-slate-500">Max {{ $product->max_per_order }} per order</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-slate-600" for="qty-{{ $product->id }}">Qty</label>
                                <input id="qty-{{ $product->id }}" type="number" min="0" wire:model.live="qty.{{ $product->id }}"
                                    class="w-20 rounded-md border border-slate-300 text-sm py-1.5 px-2" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                            </div>
                        </div>
                        @error('qty.'.$product->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @endforeach
                </div>
                @error('qty') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="text-lg font-medium text-slate-900">Shipping</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="sm:col-span-2">
                        <label class="block text-slate-700">Full name</label>
                        <input type="text" wire:model="ship_to_name" class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                        @error('ship_to_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-slate-700">Phone</label>
                        <input type="text" wire:model="ship_phone" class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                        @error('ship_phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-slate-700">Country</label>
                        <input type="text" wire:model="ship_country" class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                        @error('ship_country') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-slate-700">Address line 1</label>
                        <input type="text" wire:model="ship_line1" class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                        @error('ship_line1') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-slate-700">Address line 2 (optional)</label>
                        <input type="text" wire:model="ship_line2" class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                    </div>
                    <div>
                        <label class="block text-slate-700">City</label>
                        <input type="text" wire:model="ship_city" class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                        @error('ship_city') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-slate-700">Province</label>
                        <input type="text" wire:model="ship_province" class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                        @error('ship_province') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-slate-700">Postal code</label>
                        <input type="text" wire:model="ship_postal_code" class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3" @disabled($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference) />
                        @error('ship_postal_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            @if ($o->status === \App\Enums\ShopOrderStatus::Draft || ($o->status === \App\Enums\ShopOrderStatus::PendingPayment && ! $o->eft_reference))
                <div class="flex justify-end">
                    <button
                        type="button"
                        wire:click="placeOrder"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="placeOrder">Place order &amp; show bank details</span>
                        <span wire:loading wire:target="placeOrder" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    </button>
                </div>
            @endif

            @if ($o->status === \App\Enums\ShopOrderStatus::PendingPayment && $o->eft_reference)
                <section class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-sm space-y-3">
                    <h2 class="text-lg font-medium text-slate-900">Payment (EFT)</h2>
                    <p class="text-slate-700">Reference: <span class="font-mono font-semibold">{{ $o->eft_reference }}</span></p>
                    <p class="text-slate-700">Amount due: <strong>R {{ number_format($o->total_cents / 100, 2) }}</strong></p>
                    @if ($bank['account_name'])
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-700">
                            <div><dt class="text-slate-500">Account name</dt><dd>{{ $bank['account_name'] }}</dd></div>
                            <div><dt class="text-slate-500">Bank</dt><dd>{{ $bank['bank'] }}</dd></div>
                            <div><dt class="text-slate-500">Account number</dt><dd>{{ $bank['account_number'] }}</dd></div>
                            <div><dt class="text-slate-500">Branch code</dt><dd>{{ $bank['branch_code'] }}</dd></div>
                        </dl>
                        @if ($bank['notes'])
                            <p class="text-slate-600 text-xs whitespace-pre-line">{{ $bank['notes'] }}</p>
                        @endif
                    @else
                        <p class="text-amber-800">Bank details are not configured yet. Contact the club for payment instructions.</p>
                    @endif

                    @if (! $o->proof_path)
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <p class="text-slate-700 mb-2">Upload proof of payment (PDF or image, max 8&nbsp;MB)</p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <input type="file" wire:model="proofUpload" class="text-sm" />
                                <button
                                    type="button"
                                    wire:click="uploadProof"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-1.5 text-sm text-white hover:bg-emerald-500 disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="uploadProof">Upload proof</span>
                                    <span wire:loading wire:target="uploadProof" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                </button>
                            </div>
                            @error('proofUpload') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <p class="text-emerald-800 text-sm mt-2">Proof received{{ $o->submitted_at ? ' on '.$o->submitted_at->format('d M Y H:i') : '' }}. The committee will confirm your payment.</p>
                    @endif
                </section>
            @endif

            @if ($paystackEnabled)
                <p class="text-xs text-slate-500">Card payment via Paystack will be available here once enabled by the site administrator.</p>
            @endif
        @endif
    @endif
</div>
