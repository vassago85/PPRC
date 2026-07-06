<x-filament-panels::page>
    @php
        /** @var \App\Models\Event $event */
        $event = $this->getRecord();
        $rows = $this->getRows();
        $s = $this->getSummary();
        $canPay = $this->canManagePaymentsPublic();
        $canAttend = $this->canManageAttendancePublic();

        $money = fn (int $cents) => 'R ' . number_format($cents / 100, 2);

        $badge = [
            \App\Services\Events\MatchDirectorReport::PAYOUT => ['label' => 'Counts to payout', 'class' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400'],
            \App\Services\Events\MatchDirectorReport::CREDIT => ['label' => 'Credit — no-show', 'class' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-400'],
            \App\Services\Events\MatchDirectorReport::AWAITING => ['label' => 'Unpaid', 'class' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-400'],
            \App\Services\Events\MatchDirectorReport::FREE => ['label' => 'Free / waived', 'class' => 'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-white/5 dark:text-gray-400'],
        ];
    @endphp

    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, .no-print { display: none !important; }
            .fi-main, .fi-page { padding: 0 !important; margin: 0 !important; max-width: none !important; }
            .print-card { box-shadow: none !important; border-color: #e5e7eb !important; }
        }
    </style>

    {{-- Match header --}}
    <div class="print-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $event->title }}</h2>
                <div class="mt-1 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($event->start_date)
                        <span>{{ $event->start_date->format('l, d F Y') }}</span>
                    @endif
                    @if ($event->matchDirectorDisplay())
                        <span>Match director: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $event->matchDirectorDisplay() }}</span></span>
                    @endif
                    @if ($event->location_name)
                        <span>{{ $event->location_name }}</span>
                    @endif
                </div>
            </div>
            <button type="button" onclick="window.print()"
                class="no-print inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0V4.875c0-.621-.504-1.125-1.125-1.125h-9.75c-.621 0-1.125.504-1.125 1.125v2.16m12 0a48.667 48.667 0 00-12 0"/></svg>
                Print
            </button>
        </div>
    </div>

    {{-- Payout summary --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="print-card rounded-2xl border border-primary-200 bg-primary-50 p-5 shadow-sm dark:border-primary-500/30 dark:bg-primary-500/10 md:col-span-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Club owes match director</p>
            <p class="mt-1 text-3xl font-bold text-primary-900 dark:text-primary-100">{{ $money($s['director_payout_cents']) }}</p>
            <p class="mt-2 text-xs text-primary-700/80 dark:text-primary-300/80">
                {{ $money($s['eft_base_cents']) }} EFT (in the club account), less club levy {{ $money($s['levy_total_cents']) }}.
                @if ($s['cash_base_cents'] > 0)
                    <br>Plus {{ $money($s['cash_base_cents']) }} cash you already collected on the day.
                @endif
            </p>
        </div>

        <div class="print-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 md:col-span-2">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">EFT (to club)</p>
                    <p class="mt-0.5 text-lg font-semibold text-gray-900 dark:text-white">{{ $money($s['eft_base_cents']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cash (with director)</p>
                    <p class="mt-0.5 text-lg font-semibold text-gray-900 dark:text-white">{{ $money($s['cash_base_cents']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Club levy (total)</p>
                    <p class="mt-0.5 text-lg font-semibold text-gray-900 dark:text-white">{{ $money($s['levy_total_cents']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Held as credit</p>
                    <p class="mt-0.5 text-lg font-semibold text-warning-600 dark:text-warning-400">{{ $money($s['credit_cents']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Outstanding</p>
                    <p class="mt-0.5 text-lg font-semibold text-danger-600 dark:text-danger-400">{{ $money($s['outstanding_cents']) }}</p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                <span>{{ $s['entries_total'] }} entries</span>
                <span>{{ $s['attended_count'] }} attended</span>
                <span>{{ $s['payout_count'] }} paid &amp; shot</span>
                <span>{{ $s['cash_count'] }} cash</span>
                <span>{{ $s['credit_count'] }} no-show credit</span>
                <span>{{ $s['awaiting_count'] }} unpaid</span>
                <span>{{ $s['free_count'] }} free/waived</span>
            </div>
        </div>
    </div>

    {{-- Levy control --}}
    <div class="no-print mt-4 print-card rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="levy" class="block text-xs font-medium text-gray-600 dark:text-gray-300">Club levy per paid shooter (R)</label>
                <input id="levy" type="number" min="0" step="0.01" wire:model.live.debounce.400ms="levyRands"
                    class="mt-1 w-40 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white" />
            </div>
            @if ($canPay)
                <button type="button" wire:click="saveLevyDefault"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                    Save as club default
                </button>
            @endif
            <p class="text-xs text-gray-500 dark:text-gray-400">
                The club keeps this amount for each paying shooter who shot; the director gets the rest. Adjusting it recalculates the payout instantly.
            </p>
        </div>
    </div>

    {{-- Entries table --}}
    <div class="mt-4 print-card overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                    <th class="px-4 py-3">Shooter</th>
                    <th class="px-4 py-3">Div / Cat</th>
                    <th class="px-4 py-3 text-right">Fee</th>
                    <th class="px-4 py-3 text-center">Paid</th>
                    <th class="px-4 py-3 text-center">Shot</th>
                    <th class="px-4 py-3 text-center no-print"></th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Reference</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</div>
                            <div class="text-xs text-gray-400">{{ $row['is_member'] ? 'Member' : 'Guest' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ $row['division'] ?: '—' }}@if($row['category']) <span class="text-gray-400">/ {{ $row['category'] }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">
                            {{ $row['fee_cents'] > 0 ? $money($row['fee_cents']) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($row['paid'])
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $row['is_cash'] ? 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400' }}">
                                    {{ $row['is_cash'] ? 'Cash' : 'EFT' }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button"
                                @if($canAttend) wire:click="toggleAttended({{ $row['id'] }})" @else disabled @endif
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md ring-1 ring-inset {{ $row['attended'] ? 'bg-primary-500 text-white ring-primary-500' : 'bg-white text-transparent ring-gray-300 dark:bg-white/5 dark:ring-white/10' }} {{ $canAttend ? 'cursor-pointer' : 'cursor-default' }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center no-print">
                            @if ($canPay)
                                <div class="inline-flex overflow-hidden rounded-lg ring-1 ring-inset ring-gray-300 dark:ring-white/10">
                                    <button type="button" wire:click="payVia({{ $row['id'] }}, 'eft')"
                                        class="px-2 py-1 text-xs font-medium {{ $row['paid'] && ! $row['is_cash'] ? 'bg-success-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10' }}">
                                        EFT
                                    </button>
                                    <button type="button" wire:click="payVia({{ $row['id'] }}, 'cash')"
                                        class="border-l border-gray-300 px-2 py-1 text-xs font-medium dark:border-white/10 {{ $row['paid'] && $row['is_cash'] ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10' }}">
                                        Cash
                                    </button>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge[$row['classification']]['class'] }}">
                                {{ $badge[$row['classification']]['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row['reference'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="no-print mt-3 text-xs text-gray-500 dark:text-gray-400">
        Mark each entry <strong>EFT</strong> or <strong>Cash</strong> once paid (click the same one again to undo), and tick <strong>Shot</strong> for shooters who attended.
        <strong>EFT</strong> money sits in the club account and is what the club owes you; <strong>cash</strong> was handed to you on the day, so it's shown separately and not added to the payout.
        A paid shooter who didn't shoot shows as a <span class="text-warning-600 dark:text-warning-400">no-show credit</span> — their fee is held for a future match.
    </p>
</x-filament-panels::page>
