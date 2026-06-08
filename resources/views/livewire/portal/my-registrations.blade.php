@php
    $badgeClasses = [
        'success' => 'bg-emerald-500/20 text-emerald-400 ring-emerald-500/30',
        'warning' => 'bg-amber-500/20 text-amber-400 ring-amber-500/30',
        'info'    => 'bg-sky-500/20 text-sky-400 ring-sky-500/30',
        'danger'  => 'bg-red-500/20 text-red-400 ring-red-500/30',
        'gray'    => 'bg-slate-500/20 text-slate-400 ring-slate-500/30',
    ];
@endphp

<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">My Registrations</h1>
        <p class="mt-1 text-sm text-slate-400">Your event registrations — upcoming and past.</p>
    </div>

    @if (session('flash'))
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('flash') }}
        </div>
    @endif
    @if (session('flash_error'))
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            {{ session('flash_error') }}
        </div>
    @endif

    @if ($this->payable->isNotEmpty())
        @php
            $bankName = \App\Models\SiteSetting::get('payments.bank.bank', '');
            $accountName = \App\Models\SiteSetting::get('payments.bank.account_name', '');
            $accountNumber = \App\Models\SiteSetting::get('payments.bank.account_number', '');
            $branchCode = \App\Models\SiteSetting::get('payments.bank.branch_code', '');
            $accountType = \App\Models\SiteSetting::get('payments.bank.account_type', 'cheque');
        @endphp
        <section class="space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-amber-400">Payment due</h2>

            @foreach ($this->payable as $reg)
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/[0.04] p-5 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-white">{{ $reg->event->title }}</p>
                            <p class="text-xs text-slate-400">{{ $reg->event->start_date->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Entry fee</p>
                            <p class="text-xl font-bold text-white">R {{ number_format((int) ($reg->effectiveFeeCents() ?? 0) / 100, 2) }}</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Payment reference</p>
                            <p class="mt-1 font-mono text-lg font-bold tracking-wider text-white">{{ $reg->paymentReference() }}</p>
                            <p class="mt-1 text-xs text-slate-400">Use this exact reference when paying</p>
                        </div>
                        @if ($bankName || $accountNumber)
                            <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                                <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                                    @if ($accountName)
                                        <dt class="text-slate-500">Account</dt>
                                        <dd class="text-white">{{ $accountName }}</dd>
                                    @endif
                                    @if ($bankName)
                                        <dt class="text-slate-500">Bank</dt>
                                        <dd class="text-white">{{ $bankName }}</dd>
                                    @endif
                                    @if ($accountNumber)
                                        <dt class="text-slate-500">Acc. number</dt>
                                        <dd class="font-mono text-white">{{ $accountNumber }}</dd>
                                    @endif
                                    @if ($branchCode)
                                        <dt class="text-slate-500">Branch</dt>
                                        <dd class="font-mono text-white">{{ $branchCode }}</dd>
                                    @endif
                                    @if ($accountType)
                                        <dt class="text-slate-500">Type</dt>
                                        <dd class="capitalize text-white">{{ $accountType }}</dd>
                                    @endif
                                </dl>
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-white/10 pt-4">
                        @if ($reg->payment_proof_path)
                            <div class="flex flex-wrap items-center gap-2 text-sm text-emerald-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <span>Proof uploaded{{ $reg->proof_submitted_at ? ' on '.$reg->proof_submitted_at->format('d M Y') : '' }} — awaiting confirmation.</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Paid a different way or need to replace it? Upload again below.</p>
                        @else
                            <p class="text-sm font-medium text-slate-300">Once paid, upload your proof of payment:</p>
                        @endif

                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <input type="file" wire:model="proofUploads.{{ $reg->id }}"
                                class="text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-white/15" />
                            <button type="button" wire:click="uploadProof({{ $reg->id }})" wire:loading.attr="disabled" wire:target="uploadProof({{ $reg->id }}),proofUploads.{{ $reg->id }}"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-400 disabled:opacity-50">
                                <span wire:loading.remove wire:target="uploadProof({{ $reg->id }}),proofUploads.{{ $reg->id }}">Upload proof</span>
                                <span wire:loading wire:target="uploadProof({{ $reg->id }}),proofUploads.{{ $reg->id }}" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                            </button>
                        </div>
                        @error('proofUploads.'.$reg->id) <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @foreach (['Upcoming' => $this->upcoming, 'Past' => $this->past] as $label => $items)
        <section class="space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</h2>

            @if ($items->isEmpty())
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center">
                    <p class="text-sm text-slate-500">No {{ strtolower($label) }} registrations.</p>
                </div>
            @else
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-white/10 text-left text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Event</th>
                                    <th class="px-6 py-3 font-medium">Date</th>
                                    <th class="px-6 py-3 font-medium">Location</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    @if ($label === 'Upcoming')
                                        <th class="px-6 py-3 font-medium"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($items as $reg)
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-6 py-4">
                                            <a href="{{ url('/matches/' . $reg->event->slug) }}" class="font-medium text-white hover:text-slate-300">{{ $reg->event->title }}</a>
                                        </td>
                                        <td class="px-6 py-4 text-slate-400">{{ $reg->event->start_date->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-slate-400">{{ $reg->event->location_name ?? '—' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses[$reg->status->color()] ?? $badgeClasses['gray'] }}">
                                                {{ $reg->status->label() }}
                                            </span>
                                        </td>
                                        @if ($label === 'Upcoming')
                                            <td class="px-6 py-4 text-right">
                                                @if ($reg->status !== App\Enums\EventRegistrationStatus::Cancelled)
                                                    <button
                                                        type="button"
                                                        wire:click="withdraw({{ $reg->id }})"
                                                        wire:confirm="Withdraw from {{ $reg->event->title }}?"
                                                        wire:loading.attr="disabled"
                                                        class="text-xs font-medium text-red-400 transition hover:text-red-300 disabled:opacity-50"
                                                    >Withdraw</button>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="sm:hidden divide-y divide-white/5">
                        @foreach ($items as $reg)
                            <div class="px-5 py-4">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ url('/matches/' . $reg->event->slug) }}" class="font-medium text-white">{{ $reg->event->title }}</a>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses[$reg->status->color()] ?? $badgeClasses['gray'] }}">
                                        {{ $reg->status->label() }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center justify-between">
                                    <p class="text-xs text-slate-500">{{ $reg->event->start_date->format('d M Y') }}@if ($reg->event->location_name) · {{ $reg->event->location_name }}@endif</p>
                                    @if ($label === 'Upcoming' && $reg->status !== App\Enums\EventRegistrationStatus::Cancelled)
                                        <button
                                            type="button"
                                            wire:click="withdraw({{ $reg->id }})"
                                            wire:confirm="Withdraw from {{ $reg->event->title }}?"
                                            class="text-xs font-medium text-red-400 hover:text-red-300"
                                        >Withdraw</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endforeach
</div>
