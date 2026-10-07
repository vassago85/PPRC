@php
    /** @var \App\Invoices\InvoiceDocument $invoice */
    $legalName = filled(\App\Models\SiteSetting::get('invoice.legal_name'))
        ? (string) \App\Models\SiteSetting::get('invoice.legal_name')
        : 'Pretoria Precision Rifle Club';
    $vatNumber = (string) \App\Models\SiteSetting::get('invoice.vat_number', '');
    $companyReg = (string) \App\Models\SiteSetting::get('invoice.company_reg', '');
    $address = (string) \App\Models\SiteSetting::get('contact.physical_address', '');
    $contactEmail = (string) \App\Models\SiteSetting::get('contact.email', '');
    $contactPhone = (string) \App\Models\SiteSetting::get('contact.phone', '');
    $bankName = (string) \App\Models\SiteSetting::get('payments.bank.bank', '');
    $accountName = (string) \App\Models\SiteSetting::get('payments.bank.account_name', '');
    $accountNumber = (string) \App\Models\SiteSetting::get('payments.bank.account_number', '');
    $branchCode = (string) \App\Models\SiteSetting::get('payments.bank.branch_code', '');
    $accountType = (string) \App\Models\SiteSetting::get('payments.bank.account_type', 'cheque');

    // $logoPath is pre-resolved by InvoicePdfRenderer::logoPath() — a cached,
    // downscaled copy of the brand logo that DomPDF can decode without blowing
    // memory. Null when the logo file is missing on this install.
    $logoAvailable = isset($logoPath) && is_string($logoPath) && file_exists($logoPath);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        @page { margin: 18mm 16mm 20mm 16mm; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            color: #1f2937;
            line-height: 1.45;
        }
        .muted { color: #6b7280; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .right { text-align: right; }
        .small { font-size: 9pt; }
        .xs { font-size: 8pt; }
        .uppercase-tag {
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 7.5pt;
            color: #9ca3af;
            font-weight: bold;
        }

        /* Masthead: logo right-aligned, legal name + "INVOICE" on the left. */
        table.masthead { width: 100%; border-collapse: collapse; margin-bottom: 10mm; }
        table.masthead td { vertical-align: top; padding: 0; }
        table.masthead td.logo { width: 60px; text-align: right; }
        table.masthead td.logo img { width: 60px; height: 60px; }
        .brand-line { color: #0ea5e9; font-weight: bold; font-size: 8pt; letter-spacing: 1.5px; text-transform: uppercase; }
        .doc-title { font-size: 24pt; font-weight: bold; margin: 2mm 0 0 0; color: #111827; }
        .doc-sub { color: #6b7280; margin: 1mm 0 0 0; font-size: 11pt; }

        /* Bill-to / invoice-meta row. */
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 8mm; }
        table.meta td { vertical-align: top; padding: 0; width: 50%; }
        table.meta .label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 1.5px; color: #9ca3af; font-weight: bold; }
        table.meta .value { font-size: 12pt; font-weight: bold; color: #111827; margin-top: 1mm; }
        table.meta .value-sub { font-size: 10pt; color: #4b5563; margin-top: 0.5mm; }

        /* Line-item table. */
        table.lines { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        table.lines thead th {
            background: #f3f4f6;
            text-align: left;
            padding: 3mm 3mm;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        table.lines thead th.right { text-align: right; }
        table.lines tbody td {
            padding: 3mm 3mm;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }
        table.lines tbody td.right { text-align: right; }

        /* Totals block, right-aligned. */
        table.totals { width: 60mm; border-collapse: collapse; margin-left: auto; margin-top: 5mm; }
        table.totals td { padding: 1.5mm 0; font-size: 10.5pt; }
        table.totals td.label { color: #6b7280; }
        table.totals td.value { text-align: right; }
        table.totals tr.grand td { border-top: 1.5px solid #111827; font-weight: bold; font-size: 12pt; padding-top: 2.5mm; color: #111827; }

        /* Paid stamp — rendered as an inline block so DomPDF renders it reliably. */
        .pill {
            display: inline-block;
            padding: 1.5mm 4mm;
            border-radius: 20mm;
            font-size: 9pt;
            font-weight: bold;
            margin-top: 2mm;
        }
        .pill-paid { background: #dcfce7; color: #166534; border: 0.5pt solid #86efac; }
        .pill-due  { background: #fef3c7; color: #92400e; border: 0.5pt solid #fcd34d; }

        /* EFT payment block (shown only when unpaid). */
        .pay-box {
            margin-top: 10mm;
            background: #f9fafb;
            border: 0.5pt solid #e5e7eb;
            border-radius: 2mm;
            padding: 5mm;
        }
        .pay-box h3 { margin: 0 0 1mm 0; font-size: 8pt; text-transform: uppercase; letter-spacing: 1.5px; color: #6b7280; }
        .pay-box .ref { margin: 1mm 0 3mm 0; font-size: 10pt; color: #374151; }
        .pay-box .ref .mono { color: #111827; font-weight: bold; }
        table.pay-details { width: 100%; border-collapse: collapse; }
        table.pay-details td { padding: 1mm 0; font-size: 10pt; vertical-align: top; }
        table.pay-details td.key { color: #6b7280; width: 35mm; }
        table.pay-details td.val { color: #111827; }

        .footer-note {
            margin-top: 10mm;
            padding-top: 4mm;
            border-top: 0.5pt solid #e5e7eb;
            font-size: 8.5pt;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="masthead">
        <tr>
            <td>
                <div class="brand-line">{{ $legalName }}</div>
                <h1 class="doc-title">Invoice</h1>
                <p class="doc-sub">{{ $invoice->type->label() }}</p>
                @if ($address || $contactEmail || $contactPhone || $vatNumber || $companyReg)
                    <div class="small muted" style="margin-top: 3mm;">
                        @if ($address)<div>{{ $address }}</div>@endif
                        @if ($contactEmail)<div>{{ $contactEmail }}</div>@endif
                        @if ($contactPhone)<div>{{ $contactPhone }}</div>@endif
                        @if ($companyReg)<div>Reg {{ $companyReg }}</div>@endif
                        @if ($vatNumber)<div>VAT {{ $vatNumber }}</div>@endif
                    </div>
                @endif
            </td>
            @if ($logoAvailable)
                <td class="logo">
                    <img src="{{ $logoPath }}" alt="PPRC" />
                </td>
            @endif
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="label">Bill to</div>
                <div class="value">{{ $invoice->billToDisplayName() }}</div>
                @if ($invoice->billToName && $invoice->billToName !== $invoice->payerName)
                    <div class="value-sub">For {{ $invoice->payerName }}</div>
                @endif
                @if ($invoice->membershipNumber)
                    <div class="value-sub mono">{{ $invoice->membershipNumber }}</div>
                @endif
                @if ($invoice->billToEmail)
                    <div class="value-sub">{{ $invoice->billToEmail }}</div>
                @endif
            </td>
            <td class="right">
                <div class="label">Invoice number</div>
                <div class="value mono">{{ $invoice->number }}</div>
                <div class="label" style="margin-top: 3mm;">Date</div>
                <div class="value-sub">{{ $invoice->issuedAt->format('j F Y') }}</div>
                <div>
                    <span class="pill {{ $invoice->paid ? 'pill-paid' : 'pill-due' }}">{{ $invoice->statusLabel }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right" style="width: 20mm;">Qty</th>
                <th class="right" style="width: 30mm;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="right muted">{{ $line->quantity }}</td>
                    <td class="right mono">{{ $invoice->formatted($line->lineCents) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value mono">{{ $invoice->formatted($invoice->subtotalCents) }}</td>
        </tr>
        <tr class="grand">
            <td>{{ $invoice->paid ? 'Total paid' : 'Total due' }}</td>
            <td class="right mono">{{ $invoice->formatted($invoice->totalCents) }}</td>
        </tr>
        @if ($invoice->paid && $invoice->paidAt)
            <tr>
                <td colspan="2" class="right xs muted" style="padding-top: 1.5mm;">
                    Paid {{ $invoice->paidAt->format('j F Y') }}@if ($invoice->paymentMethod) &nbsp;·&nbsp; {{ $invoice->paymentMethod }}@endif
                </td>
            </tr>
        @elseif ($invoice->paymentMethod)
            <tr>
                <td colspan="2" class="right xs muted" style="padding-top: 1.5mm;">{{ $invoice->paymentMethod }}</td>
            </tr>
        @endif
    </table>

    @if ($invoice->showBankDetails() && ($bankName || $accountNumber))
        <div class="pay-box">
            <h3>Pay by EFT</h3>
            <p class="ref">Use reference <span class="mono">{{ $invoice->number }}</span> exactly as shown so your payment matches this invoice.</p>
            <table class="pay-details">
                @if ($accountName)
                    <tr><td class="key">Account</td><td class="val">{{ $accountName }}</td></tr>
                @endif
                @if ($bankName)
                    <tr><td class="key">Bank</td><td class="val">{{ $bankName }}</td></tr>
                @endif
                @if ($accountNumber)
                    <tr><td class="key">Account number</td><td class="val mono">{{ $accountNumber }}</td></tr>
                @endif
                @if ($branchCode)
                    <tr><td class="key">Branch code</td><td class="val mono">{{ $branchCode }}</td></tr>
                @endif
                @if ($accountType)
                    <tr><td class="key">Type</td><td class="val" style="text-transform: capitalize;">{{ $accountType }}</td></tr>
                @endif
            </table>
        </div>
    @endif

    <div class="footer-note">
        This is a club invoice issued by {{ $legalName }} for the charge above.
        It is not a VAT tax invoice unless a VAT number is shown.
    </div>
</body>
</html>
