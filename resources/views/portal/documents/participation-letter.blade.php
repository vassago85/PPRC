@php
    /** @var \App\Models\Member $member */
    /** @var \Illuminate\Support\Collection $registrations */
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Participation letter · {{ $member->fullName() }}</title>
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
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Letter of Participation</h1>
            </div>

            <div class="mt-8 text-sm text-slate-600">
                <p>{{ now()->format('j F Y') }}</p>
            </div>

            <div class="mt-6 space-y-4 text-sm leading-relaxed text-slate-700">
                <p>To whom it may concern,</p>

                <p>
                    This letter confirms that <strong class="text-slate-900">{{ $member->fullName() }}</strong>
                    @if ($member->membership_number)
                        (membership number <strong class="text-slate-900">{{ $member->membership_number }}</strong>)
                    @endif
                    is a registered member of the <strong class="text-slate-900">Pretoria Precision Rifle Club (PPRC)</strong>
                    @if ($member->join_date)
                        since {{ $member->join_date->format('j F Y') }}
                    @endif
                    and has participated in the following events hosted by the club:
                </p>
            </div>

            @if ($registrations->count())
                <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">Date</th>
                                <th class="px-4 py-2.5 font-medium">Event</th>
                                <th class="px-4 py-2.5 font-medium">Division</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($registrations as $reg)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 tabular-nums text-slate-600">{{ $reg->event?->start_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $reg->event?->title ?? '—' }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $reg->division ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-slate-400">{{ $registrations->count() }} event(s) on record.</p>
            @else
                <p class="mt-6 text-sm text-slate-500">No event participation on record.</p>
            @endif

            <div class="mt-10 space-y-4 text-sm leading-relaxed text-slate-700">
                <p>
                    This letter is issued upon request of the above-named member for whatever purpose it may serve.
                </p>

                <p>Should you require any further information, please contact the club at <strong class="text-slate-900">{{ $clubEmail }}</strong>.</p>
            </div>

            <div class="mt-12 text-sm text-slate-700">
                <p>Yours faithfully,</p>
                <p class="mt-6 font-semibold text-slate-900">The Committee</p>
                <p class="text-slate-600">Pretoria Precision Rifle Club</p>
                @if ($clubAddress)
                    <p class="text-slate-500">{{ $clubAddress }}</p>
                @endif
            </div>

            <p class="mt-10 text-center text-[10px] text-slate-400">
                Generated {{ now()->format('j F Y \a\t H:i') }} · This document is electronically produced and valid without a signature.
            </p>
        </div>
    </main>
</body>
</html>
