@props(['payment'])

@php
    $bankName = \App\Models\SiteSetting::get('payments.bank.bank', '');
    $accountName = \App\Models\SiteSetting::get('payments.bank.account_name', '');
    $accountNumber = \App\Models\SiteSetting::get('payments.bank.account_number', '');
    $branchCode = \App\Models\SiteSetting::get('payments.bank.branch_code', '');
    $accountType = \App\Models\SiteSetting::get('payments.bank.account_type', 'cheque');
    $bankNotes = \App\Models\SiteSetting::get('payments.bank.notes', '');
@endphp

<div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-5 space-y-4">
    <div class="flex items-center gap-2">
        <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
        </svg>
        <p class="text-sm font-semibold text-amber-300">EFT Payment Details</p>
    </div>

    {{-- Reference highlight --}}
    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3 text-center">
        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Your payment reference</p>
        <p class="mt-1 font-mono text-2xl font-bold tracking-wider text-white">{{ $payment->reference }}</p>
        <p class="mt-1 text-xs text-slate-400">Use this EXACT reference when paying</p>
    </div>

    {{-- Amount --}}
    <div class="text-center">
        <p class="text-sm text-slate-400">Amount to pay</p>
        <p class="text-2xl font-bold text-white">R {{ number_format($payment->amount_cents / 100, 2) }}</p>
    </div>

    {{-- Bank details grid --}}
    @if ($bankName || $accountNumber)
        <div class="rounded-lg border border-white/10 bg-white/5 p-4">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                @if ($accountName)
                    <div>
                        <dt class="text-xs text-slate-500">Account name</dt>
                        <dd class="font-medium text-white">{{ $accountName }}</dd>
                    </div>
                @endif
                @if ($bankName)
                    <div>
                        <dt class="text-xs text-slate-500">Bank</dt>
                        <dd class="font-medium text-white">{{ $bankName }}</dd>
                    </div>
                @endif
                @if ($accountNumber)
                    <div>
                        <dt class="text-xs text-slate-500">Account number</dt>
                        <dd class="font-mono font-medium text-white">{{ $accountNumber }}</dd>
                    </div>
                @endif
                @if ($branchCode)
                    <div>
                        <dt class="text-xs text-slate-500">Branch code</dt>
                        <dd class="font-mono font-medium text-white">{{ $branchCode }}</dd>
                    </div>
                @endif
                @if ($accountType)
                    <div>
                        <dt class="text-xs text-slate-500">Account type</dt>
                        <dd class="font-medium text-white capitalize">{{ $accountType }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif

    @if ($bankNotes)
        <p class="text-xs text-slate-400">{{ $bankNotes }}</p>
    @endif

    {{-- Upload proof --}}
    <div class="border-t border-white/10 pt-4">
        <p class="text-sm font-medium text-slate-300">Once paid, upload your proof of payment:</p>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="file" wire:model="proofUpload"
                class="text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-white/15" />
            <button type="button" wire:click="uploadProof({{ $payment->id }})" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-400 disabled:opacity-50">
                <span wire:loading.remove wire:target="uploadProof({{ $payment->id }})">Upload proof</span>
                <span wire:loading wire:target="uploadProof({{ $payment->id }})" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
            </button>
        </div>
        @error('proofUpload') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>
</div>
