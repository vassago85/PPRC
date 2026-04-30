@php
    /** @var \App\Models\EndorsementRequest $endorsement */
    /** @var \App\Models\Member $member */
    $isPreview = $isPreview ?? false;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isPreview ? 'PREVIEW · ' : '' }}Endorsement letter · {{ $member->fullName() }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; -webkit-print-color-adjust: exact; }
        }
        @page { margin: 2cm; }
        .draft-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 8rem;
            font-weight: 900;
            color: rgba(220, 38, 38, 0.12);
            letter-spacing: 0.2em;
            pointer-events: none;
            z-index: 1;
            user-select: none;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    @if ($isPreview)
        <div class="draft-watermark">DRAFT PREVIEW</div>
        <div class="no-print mx-auto max-w-3xl px-4 pt-6">
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                <strong>Preview mode</strong> — this is a draft. The letter has not been issued.
                Status: <strong>{{ $endorsement->status->label() }}</strong>.
            </div>
        </div>
    @endif

    <div class="no-print mx-auto max-w-3xl px-4 py-6 text-center">
        <button type="button" onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            {{ $isPreview ? 'Print preview' : 'Print / Save as PDF' }}
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
