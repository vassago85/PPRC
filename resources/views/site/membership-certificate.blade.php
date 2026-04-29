@php
    /** @var \App\Models\Membership $membership */
    $member = $membership->member;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membership certificate · {{ $member?->fullName() ?? 'PPRC' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="no-print mx-auto max-w-3xl px-4 py-6 text-center">
        <button type="button" onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Print / Save as PDF
        </button>
        <p class="mt-2 text-xs text-slate-500">This page is only shown for an active membership period.</p>
    </div>

    <main class="mx-auto max-w-3xl px-4 pb-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-10 shadow-sm print:border-0 print:shadow-none">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Pretoria Precision Rifle Club</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Membership certificate</h1>
            </div>

            <div class="mt-10 space-y-4 text-center text-lg text-slate-700">
                <p>This certifies that</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $member?->fullName() }}</p>
                <p>held <span class="font-medium">{{ $membership->membership_type_name_snapshot }}</span> membership</p>
                <p class="text-base text-slate-600">
                    @if ($membership->period_end)
                        for the period
                        <span class="font-medium tabular-nums text-slate-900">{{ $membership->period_start->format('j F Y') }}</span>
                        to
                        <span class="font-medium tabular-nums text-slate-900">{{ $membership->period_end->format('j F Y') }}</span>.
                    @else
                        effective from
                        <span class="font-medium tabular-nums text-slate-900">{{ $membership->period_start->format('j F Y') }}</span>
                        (life membership).
                    @endif
                </p>
            </div>

            @if ($membership->certificate_issued_at)
                <p class="mt-10 text-center text-xs text-slate-400">
                    Issued {{ $membership->certificate_issued_at->format('j F Y') }}
                </p>
            @endif
        </div>
    </main>
</body>
</html>
