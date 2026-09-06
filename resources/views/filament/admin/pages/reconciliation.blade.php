<x-filament-panels::page>
    @php
        $money = fn (?int $cents) => 'R ' . number_format(((int) $cents) / 100, 2);
        $statementCents = $this->amountCents();

        $confidence = [
            \App\Services\Payments\PaymentMatch::EXACT => ['label' => 'Confident', 'variant' => 'ok'],
            \App\Services\Payments\PaymentMatch::LIKELY => ['label' => 'Likely', 'variant' => 'warn'],
            \App\Services\Payments\PaymentMatch::POSSIBLE => ['label' => 'Possible', 'variant' => 'muted'],
        ];

        $kindLabel = [
            \App\Services\Payments\PaymentMatch::MATCH_ENTRY => 'Match entry',
            \App\Services\Payments\PaymentMatch::MEMBERSHIP_PAYMENT => 'Membership',
            \App\Services\Payments\PaymentMatch::SHOP_ORDER => 'Shop order',
        ];
    @endphp

    <x-ui.page>
        <x-ui.segments
            name="tab"
            :active="$tab"
            :segments="[
                ['key' => 'find', 'label' => 'Find one line'],
                ['key' => 'statement', 'label' => 'Reconcile statement'],
            ]"
        />

        @if ($tab === 'find')
            {{-- ─────── Find one line ─────── --}}
            <x-ui.panel title="Find one line">
                <form wire:submit.prevent="find" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                        <div class="lg:col-span-8">
                            <label class="block text-sm font-medium text-[color:var(--ui-ink)]">Bank statement line</label>
                            <input type="text"
                                wire:model="line"
                                placeholder="e.g. INVESTECPBPPRC-M14-85"
                                autocomplete="off"
                                class="ui-cell--mono mt-1.5 block w-full rounded-md border border-[color:var(--ui-line-2)] bg-[color:var(--ui-surface)] px-3 py-2 text-sm text-[color:var(--ui-ink)] focus:border-[color:var(--ui-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--ui-accent)]"
                            />
                            <p class="mt-1.5 text-xs text-[color:var(--ui-ink-3)]">
                                Paste it exactly as the bank shows it — bank name on the front, missing dashes or a name instead of a reference are all fine.
                            </p>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-[color:var(--ui-ink)]">Amount <span class="font-normal text-[color:var(--ui-ink-3)]">(optional)</span></label>
                            <input type="text"
                                wire:model="amount"
                                placeholder="450.00"
                                autocomplete="off"
                                class="mt-1.5 block w-full rounded-md border border-[color:var(--ui-line-2)] bg-[color:var(--ui-surface)] px-3 py-2 text-sm text-[color:var(--ui-ink)] focus:border-[color:var(--ui-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--ui-accent)]"
                            />
                            <p class="mt-1.5 text-xs text-[color:var(--ui-ink-3)]">Flags the ones that add up.</p>
                        </div>

                        <div class="flex items-start gap-2 lg:col-span-2 lg:pt-7">
                            <button type="submit" class="ui-btn ui-btn--primary flex-1">
                                <span wire:loading.remove wire:target="find">Find</span>
                                <span wire:loading wire:target="find">Finding…</span>
                            </button>
                            @if ($searched)
                                <button type="button" wire:click="clearFind" class="ui-btn">Clear</button>
                            @endif
                        </div>
                    </div>
                </form>
            </x-ui.panel>

            @if ($searched)
                @if ($results === [])
                    <x-ui.panel>
                        <x-ui.empty
                            icon="heroicon-o-magnifying-glass"
                            title="Nothing matched that line"
                            description="Either the reference belongs to something already settled, or the bank left nothing in the line to go on. Try just the payer's surname, or check the amount against unpaid entries on the match."
                        />
                    </x-ui.panel>
                @else
                    <x-ui.panel title="{{ count($results) }} {{ \Illuminate\Support\Str::plural('possibility', count($results)) }}">
                        <div class="space-y-3">
                            @foreach ($results as $result)
                                @php
                                    $badge = $confidence[$result['confidence']] ?? $confidence[\App\Services\Payments\PaymentMatch::POSSIBLE];
                                    $amountAgrees = $statementCents !== null
                                        && ! $result['settled']
                                        && $statementCents === $result['amount_cents'];
                                @endphp

                                <div class="rounded-md border border-[color:var(--ui-line)] bg-[color:var(--ui-surface)] p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-ui.pill :variant="$badge['variant']">{{ $badge['label'] }}</x-ui.pill>
                                                <span class="ui-panel__title">{{ $kindLabel[$result['kind']] ?? $result['kind'] }}</span>
                                                @if ($amountAgrees)
                                                    <x-ui.pill variant="ok">Amount matches</x-ui.pill>
                                                @endif
                                            </div>
                                            <p class="mt-2 text-base font-semibold text-[color:var(--ui-ink)]">{{ $result['who'] }}</p>
                                            <p class="mt-0.5 text-sm text-[color:var(--ui-ink-2)]">{{ $result['what'] }}</p>
                                            <p class="mt-1 text-xs text-[color:var(--ui-ink-3)]">{{ $result['reason'] }}</p>
                                            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-[color:var(--ui-ink-3)]">
                                                @if ($result['reference'])
                                                    <span class="ui-cell--mono">{{ $result['reference'] }}</span>
                                                @endif
                                                @if ($result['email'])
                                                    <span>{{ $result['email'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 flex-col items-end gap-2">
                                            <p class="ui-money text-lg font-semibold {{ $result['settled'] ? 'ui-money--muted' : '' }}">
                                                {{ $money($result['amount_cents']) }}
                                            </p>
                                            @if ($result['settled'])
                                                <x-ui.pill variant="muted" plain>{{ $result['settled_note'] ?? 'Nothing outstanding' }}</x-ui.pill>
                                            @elseif ($result['kind'] === \App\Services\Payments\PaymentMatch::MATCH_ENTRY && $this->canMarkEntries())
                                                <button type="button"
                                                    wire:click="markEntryPaid({{ $result['id'] }})"
                                                    wire:confirm="Mark {{ $result['who'] }}'s {{ $money($result['amount_cents']) }} entry as paid by EFT?"
                                                    class="ui-btn ui-btn--primary ui-btn--sm">
                                                    Mark paid
                                                </button>
                                            @elseif ($result['kind'] === \App\Services\Payments\PaymentMatch::MEMBERSHIP_PAYMENT && $this->canConfirmMemberships())
                                                <button type="button"
                                                    wire:click="confirmMembershipPayment({{ $result['id'] }})"
                                                    wire:confirm="Confirm {{ $result['who'] }}'s membership payment and activate the membership?"
                                                    class="ui-btn ui-btn--primary ui-btn--sm">
                                                    Confirm + activate
                                                </button>
                                            @endif
                                            @if ($result['url'])
                                                <a href="{{ $result['url'] }}" class="text-xs font-medium text-[color:var(--ui-accent)] hover:underline">Open →</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.panel>
                @endif
            @else
                <x-ui.panel title="What it can read">
                    <p class="text-sm text-[color:var(--ui-ink-2)]">Every example below came off the club's own statement and all resolve:</p>
                    <dl class="mt-3 grid grid-cols-1 gap-x-8 gap-y-2 sm:grid-cols-2">
                        @foreach ([
                            'ABSA BANK PPRC-M14-103' => 'bank name on the front',
                            'INVESTECPBPPRC-M14-85' => 'bank name glued on',
                            'FNB APP PAYMENT FROM PPRC-M13-101' => 'wrapped in narration',
                            'PPRCM1489' => 'separators stripped',
                            'PPRC-M13-95 J NEL' => 'reference plus a name',
                            'M BRUMMER' => 'no reference, just a name',
                        ] as $example => $why)
                            <div class="flex items-baseline justify-between gap-3 border-b border-[color:var(--ui-line)] pb-1.5">
                                <dt class="ui-cell--mono text-xs">{{ $example }}</dt>
                                <dd class="shrink-0 text-xs text-[color:var(--ui-ink-3)]">{{ $why }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.panel>
            @endif

        @else
            {{-- ─────── Reconcile statement ─────── --}}
            <x-ui.panel title="Upload statement">
                <p class="text-sm text-[color:var(--ui-ink-2)]">
                    Upload a bank statement CSV. Every money-in line goes past the same resolver the single-line lookup uses;
                    lines that resolve to one certain item for the exact amount can be settled in a batch — everything else
                    is listed for you to review. Nothing about the uploaded file is stored.
                </p>
                <div class="mt-3">
                    <input type="file" wire:model="file" accept=".csv,.txt"
                        class="block w-full text-sm text-[color:var(--ui-ink)]" />
                    <div class="mt-1 text-xs text-[color:var(--ui-ink-3)]" wire:loading wire:target="file,review">Reading…</div>
                    @error('file') <p class="mt-1 text-xs text-[color:var(--ui-crit)]">{{ $message }}</p> @enderror
                </div>
            </x-ui.panel>

            @if ($parsed)
                <x-ui.panel>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm">
                            <strong>{{ $summary['lines'] ?? 0 }}</strong> money-in lines ·
                            <strong>{{ $summary['ready'] ?? 0 }}</strong> ready ·
                            <strong>{{ $summary['review'] ?? 0 }}</strong> to review
                            @if ($this->ignoredCount() > 0)
                                · <span class="text-[color:var(--ui-ink-3)]">{{ $this->ignoredCount() }} ignored</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ([
                                'all' => 'All',
                                \App\Services\Payments\StatementReconciliation::READY => 'Ready',
                                \App\Services\Payments\StatementReconciliation::REVIEW => 'To review',
                                'ignored' => 'Ignored',
                            ] as $key => $label)
                                <button type="button"
                                    wire:click="setFilter('{{ $key }}')"
                                    @class(['ui-btn ui-btn--sm', 'ui-btn--primary' => $filter === $key])>
                                    {{ $label }}
                                </button>
                            @endforeach
                            @if (($summary['ready'] ?? 0) > 0)
                                <button type="button" wire:click="applyAllReady" class="ui-btn ui-btn--sm ui-btn--primary">
                                    Settle all ready
                                </button>
                            @endif
                            <button type="button" wire:click="clearStatement" class="ui-btn ui-btn--sm ui-btn--ghost">Clear</button>
                        </div>
                    </div>
                </x-ui.panel>

                @php $visible = $this->visibleReviews(); @endphp

                @if (empty($visible))
                    <x-ui.panel>
                        <x-ui.empty icon="heroicon-o-check-circle" title="Nothing to show at this filter" />
                    </x-ui.panel>
                @else
                    <x-ui.panel flush>
                        <x-ui.table :columns="[
                            ['label' => 'Row', 'align' => 'right'],
                            ['label' => 'Description'],
                            ['label' => 'Amount', 'align' => 'right'],
                            ['label' => 'Status'],
                            ['label' => 'Action', 'align' => 'right'],
                        ]">
                            @foreach ($visible as $review)
                                <tr>
                                    <td class="ui-cell--right ui-cell--mono">{{ $review['row'] }}</td>
                                    <td>
                                        <div class="ui-cell--mono text-xs">{{ $review['description'] }}</div>
                                        @if (! empty($review['best_candidate_who']))
                                            <div class="text-xs text-[color:var(--ui-ink-3)]">{{ $review['best_candidate_who'] }}</div>
                                        @endif
                                    </td>
                                    <td><x-ui.money :cents="$review['amount_cents']" /></td>
                                    <td>
                                        <x-ui.pill variant="{{ $review['status'] === \App\Services\Payments\StatementReconciliation::READY ? 'ok' : 'warn' }}">
                                            {{ $review['status'] === \App\Services\Payments\StatementReconciliation::READY ? 'Ready' : 'Review' }}
                                        </x-ui.pill>
                                    </td>
                                    <td class="ui-cell--right">
                                        @if ($this->isIgnored((int) $review['row']))
                                            <button type="button" wire:click="restore({{ $review['row'] }})" class="ui-btn ui-btn--sm">Restore</button>
                                        @elseif ($review['status'] === \App\Services\Payments\StatementReconciliation::READY && $review['apply'])
                                            <button type="button" wire:click="apply({{ $review['row'] }}, '{{ $review['apply'] }}')" class="ui-btn ui-btn--sm ui-btn--primary">Settle</button>
                                            <button type="button" wire:click="ignore({{ $review['row'] }})" class="ui-btn ui-btn--sm ui-btn--ghost">Ignore</button>
                                        @else
                                            <button type="button" wire:click="ignore({{ $review['row'] }})" class="ui-btn ui-btn--sm ui-btn--ghost">Ignore</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </x-ui.table>
                    </x-ui.panel>
                @endif
            @endif
        @endif
    </x-ui.page>
</x-filament-panels::page>
