<x-filament-panels::page>
    @php
        $money = fn (?int $cents) => 'R ' . number_format(((int) $cents) / 100, 2);

        $statusReady = \App\Services\Payments\StatementReconciliation::READY;
        $statusReview = \App\Services\Payments\StatementReconciliation::REVIEW;
        $statusSettled = \App\Services\Payments\StatementReconciliation::SETTLED;
        $statusUnmatched = \App\Services\Payments\StatementReconciliation::UNMATCHED;

        $statusMeta = [
            $statusReady => [
                'label' => 'Ready',
                'chip' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400',
                'card' => 'border-success-300 dark:border-success-500/40',
            ],
            $statusReview => [
                'label' => 'Needs a look',
                'chip' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-400',
                'card' => 'border-warning-300 dark:border-warning-500/40',
            ],
            $statusSettled => [
                'label' => 'Already paid',
                'chip' => 'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-white/5 dark:text-gray-400',
                'card' => 'border-gray-200 dark:border-white/10',
            ],
            $statusUnmatched => [
                'label' => 'No match',
                'chip' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-400',
                'card' => 'border-danger-200 dark:border-danger-500/30',
            ],
        ];

        $kindLabel = [
            \App\Services\Payments\PaymentMatch::MATCH_ENTRY => 'Match entry',
            \App\Services\Payments\PaymentMatch::MEMBERSHIP_PAYMENT => 'Membership',
            \App\Services\Payments\PaymentMatch::SHOP_ORDER => 'Shop order',
        ];
    @endphp

    {{-- Upload --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0 flex-1">
                <label for="statement-file" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Bank statement export (CSV)
                </label>
                <input
                    id="statement-file"
                    type="file"
                    wire:model="file"
                    accept=".csv,text/csv,text/plain"
                    class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-900 shadow-sm file:mr-3 file:border-0 file:bg-gray-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-white dark:file:bg-white/10 dark:file:text-gray-200"
                />
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    Straight from the bank, no tidying up needed. Only money-in lines are looked at &mdash;
                    fees, transfers and payouts are ignored. The file isn't stored.
                </p>
                @error('file')
                    <p class="mt-1.5 text-xs font-medium text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <span wire:loading wire:target="file,review" class="text-sm text-gray-500 dark:text-gray-400">
                    Reading&hellip;
                </span>
                @if ($parsed)
                    <button
                        type="button"
                        wire:click="clear"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                    >
                        Start over
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if ($parsed && $reviews !== [])
        {{-- Summary --}}
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 shadow-sm dark:border-primary-500/30 dark:bg-primary-500/10">
                <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Money in on this statement</p>
                <p class="mt-1 text-3xl font-bold text-primary-900 dark:text-primary-100">{{ $money($summary['total_cents']) }}</p>
                <p class="mt-2 text-xs text-primary-700/80 dark:text-primary-300/80">
                    Across {{ $summary['lines'] }} {{ \Illuminate\Support\Str::plural('deposit', $summary['lines']) }}.
                    @if (($skipped['debits'] ?? 0) > 0)
                        {{ $skipped['debits'] }} money-out {{ \Illuminate\Support\Str::plural('line', $skipped['debits']) }} ignored.
                    @endif
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 lg:col-span-2">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ([$statusReady, $statusReview, $statusSettled, $statusUnmatched] as $status)
                        <button
                            type="button"
                            wire:click="setFilter('{{ $filter === $status ? 'all' : $status }}')"
                            @class([
                                'rounded-xl border p-3 text-left transition',
                                'border-primary-400 bg-primary-50 dark:border-primary-500/50 dark:bg-primary-500/10' => $filter === $status,
                                'border-transparent hover:bg-gray-50 dark:hover:bg-white/5' => $filter !== $status,
                            ])
                        >
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $statusMeta[$status]['label'] }}</p>
                            <p class="mt-0.5 text-lg font-semibold text-gray-900 dark:text-white">{{ $summary[$status] }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $money($summary[$status . '_cents']) }}</p>
                        </button>
                    @endforeach
                </div>

                @if ($summary[$statusReady] > 0)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-white/10">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $summary[$statusReady] }} {{ \Illuminate\Support\Str::plural('line', $summary[$statusReady]) }}
                            resolved to a single unpaid item owing exactly what the bank received.
                        </p>
                        <button
                            type="button"
                            wire:click="applyAllReady"
                            wire:confirm="Settle {{ $summary[$statusReady] }} {{ \Illuminate\Support\Str::plural('payment', $summary[$statusReady]) }} totalling {{ $money($summary[$statusReady . '_cents']) }}? Only lines with one certain match and an exact amount are included."
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-success-500 disabled:opacity-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            Settle all {{ $summary[$statusReady] }} ready
                        </button>
                    </div>
                @endif

                @if ($this->ignoredCount() > 0)
                    <div class="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <span>{{ $this->ignoredCount() }} {{ \Illuminate\Support\Str::plural('line', $this->ignoredCount()) }} set aside.</span>
                        <button
                            type="button"
                            wire:click="setFilter('{{ $filter === 'ignored' ? 'all' : 'ignored' }}')"
                            class="font-medium text-primary-600 hover:underline dark:text-primary-400"
                        >
                            {{ $filter === 'ignored' ? 'Back to the list' : 'View ignored' }}
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Lines --}}
        <div class="mt-4 space-y-3">
            @if ($filter === 'ignored')
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Showing lines you've set aside.
                    <button type="button" wire:click="setFilter('all')" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Back to the list</button>
                </p>
            @elseif ($filter !== 'all')
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Showing {{ $statusMeta[$filter]['label'] }} only.
                        <button type="button" wire:click="setFilter('all')" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Show everything</button>
                    </p>
                    @if ($this->visibleReviews() !== [])
                        <button
                            type="button"
                            wire:click="ignoreAllVisible"
                            wire:confirm="Set aside all {{ count($this->visibleReviews()) }} {{ \Illuminate\Support\Str::plural('line', count($this->visibleReviews())) }} shown? They'll drop out of the list but you can bring them back."
                            class="text-xs font-medium text-gray-500 hover:text-gray-700 hover:underline dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            Ignore all {{ count($this->visibleReviews()) }} shown
                        </button>
                    @endif
                </div>
            @endif

            @foreach ($this->visibleReviews() as $review)
                @php $meta = $statusMeta[$review['status']]; @endphp

                <div class="rounded-2xl border bg-white p-4 shadow-sm dark:bg-gray-900 {{ $meta['card'] }}">
                    {{-- The bank's own words --}}
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $meta['chip'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                @if ($review['date'])
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($review['date'])->format('d M Y') }}
                                    </span>
                                @endif
                                <span class="text-xs text-gray-400 dark:text-gray-500">row {{ $review['row'] }}</span>
                            </div>
                            <p class="mt-2 break-all font-mono text-sm text-gray-900 dark:text-gray-100">
                                {{ $review['description'] }}
                            </p>
                            @if (($review['payer'] ?? '') !== '')
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Looks like <span class="font-medium text-gray-700 dark:text-gray-200">{{ \Illuminate\Support\Str::title(strtolower($review['payer'])) }}</span>
                                </p>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1.5">
                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $money($review['amount_cents']) }}
                            </p>
                            @if ($this->isIgnored((int) $review['row']))
                                <button
                                    type="button"
                                    wire:click="restore({{ $review['row'] }})"
                                    class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    Restore
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="ignore({{ $review['row'] }})"
                                    class="text-xs font-medium text-gray-400 hover:text-gray-600 hover:underline dark:text-gray-500 dark:hover:text-gray-300"
                                >
                                    Ignore
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($review['candidates'] === [])
                        <p class="mt-3 border-t border-gray-100 pt-3 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                            Nothing in this line to go on. Try the payer's surname on the Find payment page, or check
                            it against the unpaid entries on the match itself.
                        </p>
                    @else
                        <div class="mt-3 space-y-2 border-t border-gray-100 pt-3 dark:border-white/10">
                            @foreach ($review['candidates'] as $candidate)
                                @php
                                    $amountAgrees = ! $candidate['settled']
                                        && $candidate['amount_cents'] === $review['amount_cents'];
                                    $isTheReadyOne = $review['apply'] === $candidate['key'];
                                @endphp

                                <div @class([
                                    'flex flex-wrap items-center justify-between gap-3 rounded-xl px-3 py-2',
                                    'bg-success-50/60 dark:bg-success-500/5' => $isTheReadyOne,
                                    'bg-gray-50 dark:bg-white/5' => ! $isTheReadyOne,
                                ])>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $candidate['who'] }}
                                            </span>
                                            <span class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                                {{ $kindLabel[$candidate['kind']] ?? $candidate['kind'] }}
                                            </span>
                                            @if ($amountAgrees)
                                                <span class="rounded bg-success-100 px-1.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-400">
                                                    amount agrees
                                                </span>
                                            @elseif (! $candidate['settled'])
                                                <span class="rounded bg-warning-100 px-1.5 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">
                                                    owes {{ $money($candidate['amount_cents']) }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-gray-600 dark:text-gray-300">{{ $candidate['what'] }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $candidate['reason'] }}</p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        @if ($candidate['settled'])
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $candidate['settled_note'] ?? 'Nothing outstanding' }}
                                            </span>
                                        @elseif ($this->canSettleKind($candidate['kind']))
                                            <button
                                                type="button"
                                                wire:click="apply({{ $review['row'] }}, '{{ $candidate['key'] }}')"
                                                wire:confirm="Record {{ $money($review['amount_cents']) }} against {{ $candidate['who'] }} &mdash; {{ $candidate['what'] }}?"
                                                wire:loading.attr="disabled"
                                                @class([
                                                    'inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold shadow-sm disabled:opacity-50',
                                                    'bg-success-600 text-white hover:bg-success-500' => $isTheReadyOne,
                                                    'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10' => ! $isTheReadyOne,
                                                ])
                                            >
                                                This one
                                            </button>
                                        @endif

                                        @if ($candidate['url'])
                                            <a href="{{ $candidate['url'] }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                                                Open
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($this->visibleReviews() === [])
                <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing in that group.</p>
                </div>
            @endif
        </div>
    @elseif (! $parsed)
        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">How this works</p>
            <ol class="mt-3 space-y-2 text-sm text-gray-500 dark:text-gray-400">
                <li><span class="font-medium text-gray-700 dark:text-gray-200">1.</span> Export the account history from your bank as CSV and upload it above.</li>
                <li><span class="font-medium text-gray-700 dark:text-gray-200">2.</span> Every deposit is read the same way the Find payment page reads a single line, so the bank's own wording, missing dashes and names instead of references are all fine.</li>
                <li><span class="font-medium text-gray-700 dark:text-gray-200">3.</span> Lines that resolve to one certain unpaid item for exactly the amount received are marked <span class="font-medium text-success-700 dark:text-success-400">Ready</span> and can be settled in one go.</li>
                <li><span class="font-medium text-gray-700 dark:text-gray-200">4.</span> Anything ambiguous is listed with its candidates for you to pick from. Nothing is ever settled on a guess.</li>
            </ol>
        </div>
    @endif
</x-filament-panels::page>
