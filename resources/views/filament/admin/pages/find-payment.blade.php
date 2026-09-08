<x-filament-panels::page>
    @php
        $money = fn (?int $cents) => 'R ' . number_format(((int) $cents) / 100, 2);
        $statementCents = $this->amountCents();

        $confidence = [
            \App\Services\Payments\PaymentMatch::EXACT => [
                'label' => 'Confident',
                'class' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400',
            ],
            \App\Services\Payments\PaymentMatch::LIKELY => [
                'label' => 'Likely',
                'class' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-400',
            ],
            \App\Services\Payments\PaymentMatch::POSSIBLE => [
                'label' => 'Possible',
                'class' => 'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-white/5 dark:text-gray-400',
            ],
            \App\Services\Payments\PaymentMatch::INFO => [
                'label' => 'Identified only',
                'class' => 'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-white/5 dark:text-gray-400',
            ],
        ];

        $kindLabel = [
            \App\Services\Payments\PaymentMatch::MATCH_ENTRY => 'Match entry',
            \App\Services\Payments\PaymentMatch::MEMBERSHIP_PAYMENT => 'Membership',
            \App\Services\Payments\PaymentMatch::SHOP_ORDER => 'Shop order',
            \App\Services\Payments\PaymentMatch::IDENTIFICATION => 'Reference trace',
        ];
    @endphp

    {{-- Search --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <form wire:submit.prevent="find">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="find-payment-line" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Bank statement line
                    </label>
                    <input
                        id="find-payment-line"
                        type="text"
                        wire:model="line"
                        placeholder="e.g. INVESTECPBPPRC-M14-85"
                        autocomplete="off"
                        autofocus
                        class="mt-1.5 block w-full rounded-lg border-gray-300 bg-white font-mono text-sm text-gray-900 shadow-sm placeholder:font-sans placeholder:text-gray-400 focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                    />
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Paste it exactly as the bank shows it — the bank's own wording, missing dashes and a name instead of a reference are all fine.
                    </p>
                </div>

                <div class="lg:col-span-2">
                    <label for="find-payment-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Amount <span class="font-normal text-gray-400">(optional)</span>
                    </label>
                    <input
                        id="find-payment-amount"
                        type="text"
                        wire:model="amount"
                        placeholder="450.00"
                        autocomplete="off"
                        class="mt-1.5 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Flags the ones that add up.</p>
                </div>

                <div class="flex items-start gap-2 lg:col-span-2 lg:pt-7">
                    <button
                        type="submit"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        <span wire:loading.remove wire:target="find">Find</span>
                        <span wire:loading wire:target="find">Finding…</span>
                    </button>
                    @if ($searched)
                        <button
                            type="button"
                            wire:click="clear"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                        >
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Results --}}
    @if ($searched)
        @if ($results === [])
            <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Nothing matched that line</p>
                <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                    Either the reference belongs to something already settled, or the bank left nothing in the line to go on.
                    Try searching just the payer's surname, or check the amount against the unpaid entries on the match itself.
                </p>
            </div>
        @else
            <div class="mt-4 flex items-baseline justify-between">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ count($results) }} {{ \Illuminate\Support\Str::plural('possibility', count($results)) }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Best first — nothing is settled until you say so.</p>
            </div>

            <div class="mt-3 space-y-3">
                @foreach ($results as $result)
                    @php
                        $badge = $confidence[$result['confidence']] ?? $confidence[\App\Services\Payments\PaymentMatch::POSSIBLE];
                        $amountAgrees = $statementCents !== null
                            && ! $result['settled']
                            && $statementCents === $result['amount_cents'];
                    @endphp

                    <div class="rounded-2xl border bg-white p-4 shadow-sm dark:bg-gray-900 {{ $result['confidence'] === \App\Services\Payments\PaymentMatch::EXACT ? 'border-success-300 dark:border-success-500/40' : 'border-gray-200 dark:border-white/10' }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge['class'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                    <span class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                        {{ $kindLabel[$result['kind']] ?? $result['kind'] }}
                                    </span>
                                    @if ($amountAgrees)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-500/10 dark:text-success-400">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            Amount matches
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 truncate text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $result['who'] }}
                                </p>
                                <p class="mt-0.5 truncate text-sm text-gray-600 dark:text-gray-300">
                                    {{ $result['what'] }}
                                </p>
                                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $result['reason'] }}
                                </p>

                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                                    @if ($result['reference'])
                                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ $result['reference'] }}</span>
                                    @endif
                                    @if ($result['email'])
                                        <span class="text-gray-400 dark:text-gray-500">{{ $result['email'] }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <p class="text-xl font-bold {{ $result['settled'] ? 'text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-white' }}">
                                    {{ $money($result['amount_cents']) }}
                                </p>

                                @if ($result['settled'])
                                    <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                        {{ $result['settled_note'] ?? 'Nothing outstanding' }}
                                    </span>
                                @elseif ($result['kind'] === \App\Services\Payments\PaymentMatch::MATCH_ENTRY && $this->canMarkEntries())
                                    <button
                                        type="button"
                                        wire:click="markEntryPaid({{ $result['id'] }})"
                                        wire:confirm="Mark {{ $result['who'] }}'s {{ $money($result['amount_cents']) }} entry as paid by EFT?"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-success-500 disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                                        Mark paid
                                    </button>
                                @elseif ($result['kind'] === \App\Services\Payments\PaymentMatch::MEMBERSHIP_PAYMENT && $this->canConfirmMemberships())
                                    <button
                                        type="button"
                                        wire:click="confirmMembershipPayment({{ $result['id'] }})"
                                        wire:confirm="Confirm {{ $result['who'] }}'s membership payment and activate the membership?"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-success-500 disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        Confirm + activate
                                    </button>
                                @endif

                                @if ($result['url'])
                                    <a
                                        href="{{ $result['url'] }}"
                                        class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                    >
                                        Open &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- First run: say plainly what this copes with, so it gets trusted --}}
        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">What it can read</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Every one of these came off the club's own statement, and all of them resolve:
            </p>
            <dl class="mt-3 grid grid-cols-1 gap-x-8 gap-y-2 sm:grid-cols-2">
                @foreach ([
                    'ABSA BANK PPRC-M14-103' => 'bank name on the front',
                    'INVESTECPBPPRC-M14-85' => 'bank name glued on',
                    'FNB APP PAYMENT FROM PPRC-M13-101' => 'wrapped in narration',
                    'PPRCM1489' => 'separators stripped',
                    'PPRC-M13-95 J NEL' => 'reference plus a name',
                    'M BRUMMER' => 'no reference, just a name',
                    'PPRC-20260105-0006' => 'a membership reference used for a match',
                ] as $example => $why)
                    <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 pb-1.5 dark:border-white/5">
                        <dt class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $example }}</dt>
                        <dd class="shrink-0 text-xs text-gray-400 dark:text-gray-500">{{ $why }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Once it knows <em>who</em> paid, it also lists everything that person still owes — which is how a payment
                made against the wrong reference still finds its way home.
            </p>
        </div>
    @endif
</x-filament-panels::page>
