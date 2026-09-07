@php
    /** @var \App\Invoices\InvoiceDocument $invoice */
    $legalName = filled(\App\Models\SiteSetting::get('invoice.legal_name'))
        ? (string) \App\Models\SiteSetting::get('invoice.legal_name')
        : 'Pretoria Precision Rifle Club';
    $vatNumber = (string) \App\Models\SiteSetting::get('invoice.vat_number', '');
    $companyReg = (string) \App\Models\SiteSetting::get('invoice.company_reg', '');
    $address = (string) \App\Models\SiteSetting::get('contact.physical_address', '');
    $contactEmail = (string) \App\Models\SiteSetting::get('contact.email', '');
    $bankName = (string) \App\Models\SiteSetting::get('payments.bank.bank', '');
    $accountName = (string) \App\Models\SiteSetting::get('payments.bank.account_name', '');
    $accountNumber = (string) \App\Models\SiteSetting::get('payments.bank.account_number', '');
    $branchCode = (string) \App\Models\SiteSetting::get('payments.bank.branch_code', '');
    $accountType = (string) \App\Models\SiteSetting::get('payments.bank.account_type', 'cheque');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->number }} · PPRC</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .invoice-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="no-print mx-auto max-w-3xl px-4 py-6 text-center">
        <button type="button" onclick="window.print()" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800 transition-colors">
            Print / Save as PDF
        </button>
        <p class="mt-2 text-xs text-slate-500">
            {{ $invoice->paid ? 'Paid invoice — keep this for your records.' : 'Print this invoice and use the payment reference when paying.' }}
        </p>
    </div>

    <main class="mx-auto max-w-3xl px-4 pb-16">
        <div class="invoice-card rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="p-8 sm:p-10">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $legalName }}</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Invoice</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ $invoice->type->label() }}</p>
                    </div>
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                        <img src="{{ asset('pprclogo.png') }}" alt="PPRC" class="h-full w-full object-contain" />
                    </div>
                </div>

                @if ($address || $contactEmail || $vatNumber || $companyReg)
                    <div class="mt-4 space-y-0.5 text-xs text-slate-500">
                        @if ($address)<p>{{ $address }}</p>@endif
                        @if ($contactEmail)<p>{{ $contactEmail }}</p>@endif
                        @if ($companyReg)<p>Reg {{ $companyReg }}</p>@endif
                        @if ($vatNumber)<p>VAT {{ $vatNumber }}</p>@endif
                    </div>
                @endif

                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Bill to</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $invoice->billToDisplayName() }}</p>
                        @if ($invoice->billToName && $invoice->billToName !== $invoice->payerName)
                            <p class="mt-0.5 text-sm text-slate-500">For {{ $invoice->payerName }}</p>
                        @endif
                        @if ($invoice->membershipNumber)
                            <p class="mt-0.5 font-mono text-sm text-slate-600">{{ $invoice->membershipNumber }}</p>
                        @endif
                        @if ($invoice->billToEmail)
                            <p class="mt-0.5 text-sm text-slate-500">{{ $invoice->billToEmail }}</p>
                        @endif
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Invoice number</p>
                        <p class="mt-1 font-mono text-base font-semibold text-slate-900">{{ $invoice->number }}</p>
                        <p class="mt-3 text-xs font-medium uppercase tracking-wider text-slate-400">Date</p>
                        <p class="mt-1 text-sm tabular-nums text-slate-700">{{ $invoice->issuedAt->format('j F Y') }}</p>
                        <p class="mt-3">
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                'bg-emerald-50 text-emerald-700 ring-emerald-200' => $invoice->paid,
                                'bg-amber-50 text-amber-800 ring-amber-200' => ! $invoice->paid,
                            ])>{{ $invoice->statusLabel }}</span>
                        </p>
                    </div>
                </div>

                <div class="mt-8 overflow-hidden rounded-xl border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">Description</th>
                                <th class="px-4 py-3 font-medium text-right">Qty</th>
                                <th class="px-4 py-3 font-medium text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($invoice->lines as $line)
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ $line->description }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ $line->quantity }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-900">{{ $invoice->formatted($line->lineCents) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <div class="w-full max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between text-slate-500">
                            <span>Subtotal</span>
                            <span class="tabular-nums text-slate-800">{{ $invoice->formatted($invoice->subtotalCents) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold text-slate-900">
                            <span>{{ $invoice->paid ? 'Total paid' : 'Total due' }}</span>
                            <span class="tabular-nums">{{ $invoice->formatted($invoice->totalCents) }}</span>
                        </div>
                        @if ($invoice->paid && $invoice->paidAt)
                            <p class="text-right text-xs text-slate-500">
                                Paid {{ $invoice->paidAt->format('j F Y') }}
                                @if ($invoice->paymentMethod)
                                    · {{ $invoice->paymentMethod }}
                                @endif
                            </p>
                        @elseif ($invoice->paymentMethod)
                            <p class="text-right text-xs text-slate-500">{{ $invoice->paymentMethod }}</p>
                        @endif
                    </div>
                </div>

                @if ($invoice->showBankDetails() && ($bankName || $accountNumber))
                    <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pay by EFT</p>
                        <p class="mt-1 text-sm text-slate-600">Use reference <span class="font-mono font-semibold text-slate-900">{{ $invoice->number }}</span> exactly as shown.</p>
                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            @if ($accountName)
                                <dt class="text-slate-500">Account</dt>
                                <dd class="text-slate-900">{{ $accountName }}</dd>
                            @endif
                            @if ($bankName)
                                <dt class="text-slate-500">Bank</dt>
                                <dd class="text-slate-900">{{ $bankName }}</dd>
                            @endif
                            @if ($accountNumber)
                                <dt class="text-slate-500">Account number</dt>
                                <dd class="font-mono text-slate-900">{{ $accountNumber }}</dd>
                            @endif
                            @if ($branchCode)
                                <dt class="text-slate-500">Branch code</dt>
                                <dd class="font-mono text-slate-900">{{ $branchCode }}</dd>
                            @endif
                            @if ($accountType)
                                <dt class="text-slate-500">Type</dt>
                                <dd class="capitalize text-slate-900">{{ $accountType }}</dd>
                            @endif
                        </dl>
                    </div>
                @endif

                <p class="mt-8 text-xs text-slate-400">
                    This is a club invoice issued by Pretoria Precision Rifle Club for the charge above.
                    It is not a VAT tax invoice unless a VAT number is shown.
                </p>
            </div>
        </div>
    </main>
</body>
</html>
