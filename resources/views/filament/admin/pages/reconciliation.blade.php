<x-filament-panels::page>
    @php
        $money = fn (?int $cents) => 'R ' . number_format(((int) $cents) / 100, 2);
        $statementCents = $this->amountCents();

        $confidence = [
            \App\Services\Payments\PaymentMatch::EXACT => ['label' => 'Confident', 'variant' => 'ok'],
            \App\Services\Payments\PaymentMatch::LIKELY => ['label' => 'Likely', 'variant' => 'warn'],
            \App\Services\Payments\PaymentMatch::POSSIBLE => ['label' => 'Possible', 'variant' => 'muted'],
            \App\Services\Payments\PaymentMatch::INFO => ['label' => 'Identified only', 'variant' => 'muted'],
        ];

        $kindLabel = [
            \App\Services\Payments\PaymentMatch::MATCH_ENTRY => 'Match entry',
            \App\Services\Payments\PaymentMatch::MEMBERSHIP_PAYMENT => 'Membership',
            \App\Services\Payments\PaymentMatch::SHOP_ORDER => 'Shop order',
            \App\Services\Payments\PaymentMatch::IDENTIFICATION => 'Reference trace',
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
                @php
                    $statusMeta = [
                        \App\Services\Payments\StatementReconciliation::READY => ['label' => 'Ready', 'variant' => 'ok'],
                        \App\Services\Payments\StatementReconciliation::REVIEW => ['label' => 'Needs a look', 'variant' => 'warn'],
                        \App\Services\Payments\StatementReconciliation::SETTLED => ['label' => 'Already paid', 'variant' => 'muted'],
                        \App\Services\Payments\StatementReconciliation::UNMATCHED => ['label' => 'No match', 'variant' => 'crit'],
                    ];
                    $counts = $this->filterCounts();
                    $readyCount = $counts[\App\Services\Payments\StatementReconciliation::READY];
                    $visible = $this->visibleReviews();
                @endphp

                <x-ui.panel>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm">
                            <strong>{{ $summary['lines'] ?? 0 }}</strong> money-in lines totalling
                            <strong>{{ $money($summary['total_cents'] ?? 0) }}</strong>
                            @if (($skipped['debits'] ?? 0) > 0)
                                <span class="text-[color:var(--ui-ink-3)]">
                                    · {{ $skipped['debits'] }} money-out {{ \Illuminate\Support\Str::plural('line', $skipped['debits']) }} skipped
                                </span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($readyCount > 0)
                                <button type="button"
                                    wire:click="applyAllReady"
                                    wire:confirm="Settle {{ $readyCount }} {{ \Illuminate\Support\Str::plural('payment', $readyCount) }}? Only lines with one certain match for the exact amount are included."
                                    wire:loading.attr="disabled"
                                    class="ui-btn ui-btn--sm ui-btn--primary">
                                    Settle all {{ $readyCount }} ready
                                </button>
                            @endif
                            @if (! in_array($filter, ['all', 'ignored'], true) && $visible !== [])
                                <button type="button"
                                    wire:click="ignoreAllVisible"
                                    wire:confirm="Set aside all {{ count($visible) }} {{ \Illuminate\Support\Str::plural('line', count($visible)) }} shown? You can bring them back from Ignored."
                                    class="ui-btn ui-btn--sm">
                                    Ignore all {{ count($visible) }} shown
                                </button>
                            @endif
                            <button type="button" wire:click="clearStatement" class="ui-btn ui-btn--sm ui-btn--ghost">Clear</button>
                        </div>
                    </div>

                    <x-ui.segments
                        class="mt-3"
                        name="filter"
                        :active="$filter"
                        :segments="[
                            ['key' => 'all', 'label' => 'All', 'count' => $counts['all']],
                            ['key' => \App\Services\Payments\StatementReconciliation::READY, 'label' => 'Ready', 'count' => $readyCount],
                            ['key' => \App\Services\Payments\StatementReconciliation::REVIEW, 'label' => 'Needs a look', 'count' => $counts[\App\Services\Payments\StatementReconciliation::REVIEW]],
                            ['key' => \App\Services\Payments\StatementReconciliation::SETTLED, 'label' => 'Already paid', 'count' => $counts[\App\Services\Payments\StatementReconciliation::SETTLED]],
                            ['key' => \App\Services\Payments\StatementReconciliation::UNMATCHED, 'label' => 'No match', 'count' => $counts[\App\Services\Payments\StatementReconciliation::UNMATCHED]],
                            ['key' => 'ignored', 'label' => 'Ignored', 'count' => $counts['ignored']],
                        ]"
                    />
                </x-ui.panel>

                @if (empty($visible))
                    <x-ui.panel>
                        <x-ui.empty icon="heroicon-o-check-circle" title="Nothing to show at this filter" />
                    </x-ui.panel>
                @else
                    <x-ui.panel flush>
                        <div x-data="{ open: {} }">
                            <x-ui.table :columns="[
                                ['label' => 'Row', 'align' => 'right', 'width' => '4rem'],
                                ['label' => 'Date', 'width' => '7rem'],
                                ['label' => 'Description'],
                                ['label' => 'Amount', 'align' => 'right'],
                                ['label' => 'Status'],
                            ]">
                                @foreach ($visible as $review)
                                    @php
                                        $row = (int) $review['row'];
                                        $meta = $statusMeta[$review['status']] ?? $statusMeta[\App\Services\Payments\StatementReconciliation::REVIEW];
                                        $candidates = $review['candidates'];
                                        $lead = collect($candidates)->first(fn (array $candidate) => ! $candidate['settled']) ?? ($candidates[0] ?? null);
                                        $isIgnored = $this->isIgnored($row);
                                        $startOpen = $review['status'] === \App\Services\Payments\StatementReconciliation::REVIEW && ! $isIgnored ? 'true' : 'false';
                                        $readyKey = $review['status'] === \App\Services\Payments\StatementReconciliation::READY ? $review['apply'] : null;
                                    @endphp

                                    <tr wire:key="line-{{ $row }}">
                                        <td class="ui-cell--right ui-cell--mono">{{ $row }}</td>
                                        <td class="ui-cell--mono text-xs">
                                            {{ $review['date'] ? \Illuminate\Support\Carbon::parse($review['date'])->format('d M Y') : '—' }}
                                        </td>
                                        <td>
                                            <div class="ui-cell--mono text-xs">{{ $review['description'] }}</div>
                                            @if ($lead)
                                                <span class="ui-cell__sub">{{ $lead['who'] }} — {{ $lead['what'] }}</span>
                                            @elseif (($review['payer'] ?? '') !== '')
                                                <span class="ui-cell__sub">Looks like {{ \Illuminate\Support\Str::title(strtolower($review['payer'])) }}</span>
                                            @endif
                                        </td>
                                        <td class="ui-cell--right"><x-ui.money :cents="$review['amount_cents']" /></td>
                                        <td><x-ui.pill :variant="$meta['variant']">{{ $meta['label'] }}</x-ui.pill></td>
                                        <td class="ui-cell--actions">
                                            <div class="flex items-center justify-end gap-1.5">
                                                @if ($candidates !== [])
                                                    <button type="button"
                                                        class="ui-btn ui-btn--sm ui-btn--ghost"
                                                        x-on:click="open[{{ $row }}] = ! (open[{{ $row }}] ?? {{ $startOpen }})"
                                                        x-text="(open[{{ $row }}] ?? {{ $startOpen }}) ? 'Hide' : '{{ count($candidates) }} {{ \Illuminate\Support\Str::plural('match', count($candidates)) }}'">
                                                    </button>
                                                @endif
                                                @if ($isIgnored)
                                                    <button type="button" wire:click="restore({{ $row }})" class="ui-btn ui-btn--sm">Restore</button>
                                                @else
                                                    @if ($readyKey && $this->canSettleKind(explode(':', $readyKey)[0]))
                                                        <button type="button"
                                                            wire:click="apply({{ $row }}, '{{ $readyKey }}')"
                                                            wire:confirm="Record {{ $money($review['amount_cents']) }} against {{ $lead['who'] ?? 'this item' }}?"
                                                            wire:loading.attr="disabled"
                                                            class="ui-btn ui-btn--sm ui-btn--primary">Settle</button>
                                                    @endif
                                                    <button type="button" wire:click="ignore({{ $row }})" class="ui-btn ui-btn--sm ui-btn--ghost">Ignore</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    @if ($candidates !== [])
                                        <tr wire:key="line-{{ $row }}-candidates" x-show="open[{{ $row }}] ?? {{ $startOpen }}" @if ($startOpen === 'false') x-cloak @endif>
                                            <td></td>
                                            <td colspan="6">
                                                <div class="space-y-1.5">
                                                    @foreach ($candidates as $candidate)
                                                        @php
                                                            $badge = $confidence[$candidate['confidence']] ?? $confidence[\App\Services\Payments\PaymentMatch::POSSIBLE];
                                                            $amountAgrees = ! $candidate['settled'] && $candidate['amount_cents'] === $review['amount_cents'];
                                                        @endphp

                                                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-[color:var(--ui-line)] bg-[color:var(--ui-surface)] px-3 py-2">
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <x-ui.pill :variant="$badge['variant']">{{ $badge['label'] }}</x-ui.pill>
                                                                    <span class="text-sm font-semibold text-[color:var(--ui-ink)]">{{ $candidate['who'] }}</span>
                                                                    <span class="text-xs text-[color:var(--ui-ink-3)]">{{ $kindLabel[$candidate['kind']] ?? $candidate['kind'] }}</span>
                                                                    @if ($amountAgrees)
                                                                        <x-ui.pill variant="ok" plain>Amount agrees</x-ui.pill>
                                                                    @elseif (! $candidate['settled'])
                                                                        <x-ui.pill variant="warn" plain>Owes {{ $money($candidate['amount_cents']) }}</x-ui.pill>
                                                                    @endif
                                                                </div>
                                                                <p class="mt-0.5 text-xs text-[color:var(--ui-ink-2)]">
                                                                    {{ $candidate['what'] }}
                                                                    @if ($candidate['reference'])
                                                                        · <span class="ui-cell--mono">{{ $candidate['reference'] }}</span>
                                                                    @endif
                                                                </p>
                                                                <p class="mt-0.5 text-xs text-[color:var(--ui-ink-3)]">{{ $candidate['reason'] }}</p>
                                                            </div>
                                                            <div class="flex shrink-0 items-center gap-2">
                                                                @if ($candidate['settled'])
                                                                    <span class="text-xs text-[color:var(--ui-ink-3)]">{{ $candidate['settled_note'] ?? 'Nothing outstanding' }}</span>
                                                                @elseif (! $isIgnored && $this->canSettleKind($candidate['kind']))
                                                                    <button type="button"
                                                                        wire:click="apply({{ $row }}, '{{ $candidate['key'] }}')"
                                                                        wire:confirm="Record {{ $money($review['amount_cents']) }} against {{ $candidate['who'] }} — {{ $candidate['what'] }}?"
                                                                        wire:loading.attr="disabled"
                                                                        @class(['ui-btn ui-btn--sm', 'ui-btn--primary' => $readyKey === $candidate['key']])>
                                                                        This one
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </x-ui.table>
                        </div>
                    </x-ui.panel>
                @endif
            @endif
        @endif
    </x-ui.page>
</x-filament-panels::page>
