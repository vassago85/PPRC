@php
    /** @var \App\Models\EndorsementRequest $endorsement */
    /** @var \App\Models\Member $member */
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Endorsement letter · {{ $member->fullName() }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; -webkit-print-color-adjust: exact; }
        }
        @page { margin: 2cm; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="no-print mx-auto max-w-3xl px-4 py-6 text-center">
        <button type="button" onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Print / Save as PDF
        </button>
    </div>

    <main class="mx-auto max-w-3xl px-4 pb-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-10 shadow-sm print:border-0 print:shadow-none">
            {{-- Letterhead --}}
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Pretoria Precision Rifle Club</p>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Firearm Licence Endorsement</h1>
            </div>

            <div class="mt-8 text-sm text-slate-600">
                <p>{{ $endorsement->reviewed_at?->format('j F Y') ?? now()->format('j F Y') }}</p>
            </div>

            <div class="mt-6 space-y-4 text-sm leading-relaxed text-slate-700">
                <p>To whom it may concern,</p>

                <p>
                    The <strong class="text-slate-900">Pretoria Precision Rifle Club (PPRC)</strong> hereby endorses the application of
                    <strong class="text-slate-900">{{ $member->fullName() }}</strong>
                    @if ($member->membership_number)
                        (membership number <strong class="text-slate-900">{{ $member->membership_number }}</strong>)
                    @endif
                    @if ($member->id_number)
                        (ID number <strong class="text-slate-900">{{ $member->id_number }}</strong>)
                    @endif
                    for the purpose of obtaining a firearm licence.
                </p>

                <p>
                    The above-named individual is a member in good standing of the Pretoria Precision Rifle Club
                    @if ($member->join_date)
                        since <strong class="text-slate-900">{{ $member->join_date->format('j F Y') }}</strong>
                    @endif
                    and actively participates in club shooting activities.
                </p>

                @if ($endorsement->reason)
                    <p>
                        <strong class="text-slate-900">Purpose:</strong> {{ $endorsement->reason }}
                    </p>
                @endif

                @if ($endorsement->firearm_type)
                    <p>
                        <strong class="text-slate-900">Firearm type:</strong> {{ $endorsement->firearm_type }}
                        @if ($endorsement->firearm_details)
                            — {{ $endorsement->firearm_details }}
                        @endif
                    </p>
                @endif

                <p>
                    The club confirms that the member requires the above-mentioned firearm for dedicated sport shooting as
                    practised at our club. The member participates in precision rifle shooting disciplines and requires this
                    firearm to compete in club events and related competitions.
                </p>

                <p>
                    This endorsement is issued in support of the member's application in terms of the Firearms Control Act 60 of 2000.
                </p>
            </div>

            <div class="mt-12 text-sm text-slate-700">
                <p>Yours faithfully,</p>
                <p class="mt-6 font-semibold text-slate-900">The Committee</p>
                <p class="text-slate-600">Pretoria Precision Rifle Club</p>
                @if ($clubAddress)
                    <p class="text-slate-500">{{ $clubAddress }}</p>
                @endif
                <p class="text-slate-500">{{ $clubEmail }}</p>
            </div>

            <div class="mt-10 border-t border-slate-200 pt-4 text-[10px] text-slate-400">
                <p>Approved {{ $endorsement->reviewed_at?->format('j F Y') }} · Reference: END-{{ str_pad($endorsement->id, 5, '0', STR_PAD_LEFT) }}</p>
                <p class="mt-1">This document is electronically produced and valid without a signature.</p>
            </div>
        </div>
    </main>
</body>
</html>
