@php
    /** @var \App\Models\EndorsementRequest $endorsement */
    /** @var \App\Models\Member $member */
    /** @var \App\Models\User|null $reviewer */
    $isPreview = $isPreview ?? false;
    $verifyUrl = $verifyUrl ?? null;
    $issueDate = $endorsement->reviewed_at ?? now();
    $reference = 'END-'.str_pad((string) $endorsement->id, 5, '0', STR_PAD_LEFT);
    $isComponent = $endorsement->isComponent();
    $firearmLine = $endorsement->describeItem();
    $itemLabel = $isComponent ? 'Component' : 'Firearm';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isPreview ? 'PREVIEW · ' : '' }}Endorsement letter · {{ $member->fullName() }}</title>
    @vite(['resources/css/app.css'])
    @if ($verifyUrl)
        <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    @endif
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .letter-card { box-shadow: none !important; border: 0 !important; }
        }
        @page { margin: 1.6cm; }
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
        <div class="letter-card rounded-2xl border border-slate-200 bg-white px-10 py-10 shadow-sm">

            {{-- Letterhead --}}
            <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                        <img src="{{ asset('pprclogo.png') }}" alt="PPRC" class="h-full w-full object-contain" />
                    </div>
                    <div>
                        <p class="text-base font-bold tracking-tight text-slate-900">Pretoria Precision Rifle Club</p>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">EST. 2022</p>
                        @if ($clubEmail)
                            <p class="mt-1 text-xs text-slate-500">{{ $clubEmail }}</p>
                        @endif
                    </div>
                </div>
                <div class="text-right text-xs text-slate-500">
                    <p class="font-semibold uppercase tracking-[0.2em] text-slate-400">Reference</p>
                    <p class="mt-1 font-mono text-sm text-slate-900">{{ $reference }}</p>
                    <p class="mt-2 font-semibold uppercase tracking-[0.2em] text-slate-400">Date</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $issueDate->format('j F Y') }}</p>
                </div>
            </div>

            {{-- Title --}}
            <h1 class="mt-8 text-xl font-bold text-slate-900">Endorsement for New Firearm License</h1>

            {{-- Applicant details panel --}}
            <dl class="mt-5 grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                <div class="flex gap-2">
                    <dt class="min-w-[140px] font-semibold text-slate-700">Name:</dt>
                    <dd class="text-slate-900">{{ $member->fullName() }}</dd>
                </div>
                @if (! empty($member->id_number))
                    <div class="flex gap-2">
                        <dt class="min-w-[140px] font-semibold text-slate-700">ID Number:</dt>
                        <dd class="text-slate-900">{{ $member->id_number }}</dd>
                    </div>
                @endif
                @if ($member->membership_number)
                    <div class="flex gap-2">
                        <dt class="min-w-[140px] font-semibold text-slate-700">Membership Number:</dt>
                        <dd class="text-slate-900 font-mono">{{ $member->membership_number }}</dd>
                    </div>
                @endif
                @if ($member->join_date)
                    <div class="flex gap-2">
                        <dt class="min-w-[140px] font-semibold text-slate-700">Member since:</dt>
                        <dd class="text-slate-900">{{ $member->join_date->format('j F Y') }}</dd>
                    </div>
                @endif
                @if ($endorsement->calibre)
                    <div class="flex gap-2">
                        <dt class="min-w-[140px] font-semibold text-slate-700">Calibre:</dt>
                        <dd class="text-slate-900">{{ $endorsement->calibre }}</dd>
                    </div>
                @endif
                @if ($firearmLine !== '')
                    <div class="flex gap-2 sm:col-span-2">
                        <dt class="min-w-[140px] font-semibold text-slate-700">{{ $itemLabel }}:</dt>
                        <dd class="text-slate-900">{{ $firearmLine }}</dd>
                    </div>
                @endif
                @if ($endorsement->reason)
                    <div class="flex gap-2 sm:col-span-2">
                        <dt class="min-w-[140px] font-semibold text-slate-700">Purpose:</dt>
                        <dd class="text-slate-900">{{ $endorsement->reason }}</dd>
                    </div>
                @endif
            </dl>

            <hr class="mt-6 border-slate-300" />

            {{-- Body --}}
            <div class="mt-6 space-y-4 text-sm leading-relaxed text-slate-800">
                <p>To Whom It May Concern,</p>

                <p>
                    We have reviewed
                    <strong class="text-slate-900">{{ $member->fullName() }}</strong>'s motivation for obtaining a firearm license
                    @if ($isComponent)
                        for a <strong class="text-slate-900">{{ $firearmLine }}</strong>
                        intended for use on a centerfire precision rifle.
                    @else
                        for a <strong class="text-slate-900">{{ $firearmLine }}</strong>
                        centerfire rifle.
                    @endif
                    Based on our experience, this calibre and firearm platform are highly suitable for
                    Precision Rifle Shooting, offering excellent ballistic performance, accuracy, and consistency at
                    extended distances. Precision Rifle competitions typically involve engaging targets at distances
                    between 300m and 700m, making this {{ $isComponent ? 'component' : 'firearm' }} an optimal choice for participation in the sport.
                </p>

                <p>
                    <strong class="text-slate-900">Pretoria Precision Rifle Club (PPRC)</strong> is an affiliated club of the
                    <strong class="text-slate-900">South African Precision Rifle Federation (SAPRF)</strong>, which operates
                    in accordance with SASCO to promote and develop Precision Rifle Shooting in South Africa. SAPRF is also
                    responsible for awarding Protea Colours to top-performing athletes who meet the qualification criteria
                    set forth in agreement with SASCO.
                </p>

                <p>
                    Should you require further information regarding the use of this {{ $isComponent ? 'component' : 'firearm' }} in our sport, please feel
                    free to contact the club directly.
                </p>

                <p>
                    This endorsement is issued in support of the member's application in terms of the
                    <em>Firearms Control Act 60 of 2000</em>.
                </p>
            </div>

            {{-- Signature, issuer block, and QR --}}
            <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-3">
                <div class="sm:col-span-2 text-sm text-slate-800">
                    <p>Yours faithfully,</p>
                    <div class="mt-10">
                        <p class="font-bold text-slate-900">
                            {{ $reviewer?->name ?? 'The Committee' }}
                        </p>
                        <p class="text-slate-600">Pretoria Precision Rifle Club (PPRC)</p>
                        @if ($clubEmail)
                            <p class="text-slate-600">{{ $clubEmail }}</p>
                        @endif
                        @if ($clubAddress)
                            <p class="text-slate-500">{{ $clubAddress }}</p>
                        @endif
                    </div>
                </div>

                @if ($verifyUrl)
                    <div class="flex flex-col items-center justify-end text-center">
                        <div id="qr-code" class="rounded-xl border border-slate-200 bg-slate-50 p-3"></div>
                        <p class="mt-2 text-[10px] font-medium uppercase tracking-wider text-slate-400">Scan to verify</p>
                    </div>
                @elseif ($isPreview)
                    <div class="flex flex-col items-center justify-end text-center text-[10px] uppercase tracking-wider text-slate-400">
                        <div class="flex h-[110px] w-[110px] items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50">
                            <span class="px-2 text-center text-[10px] leading-tight">QR appears<br>once issued</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-10 border-t border-slate-200 pt-4 text-[10px] text-slate-400">
                <p>
                    @if ($endorsement->reviewed_at)
                        Approved {{ $endorsement->reviewed_at->format('j F Y') }} ·
                    @endif
                    Reference: {{ $reference }}
                </p>
                <p class="mt-1">This document is electronically produced and valid without a signature. Verify authenticity by scanning the QR code.</p>
            </div>
        </div>
    </main>

    @if ($verifyUrl)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var qr = qrcode(0, 'M');
                qr.addData(@js($verifyUrl));
                qr.make();

                var container = document.getElementById('qr-code');
                if (!container) return;
                container.innerHTML = qr.createSvgTag(4, 0);
                var svg = container.querySelector('svg');
                if (svg) {
                    svg.setAttribute('width', '110');
                    svg.setAttribute('height', '110');
                    svg.style.display = 'block';
                }
            });
        </script>
    @endif
</body>
</html>
