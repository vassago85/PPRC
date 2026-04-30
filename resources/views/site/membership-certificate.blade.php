@php
    /** @var \App\Models\Membership $membership */
    $member = $membership->member;
    $isLifetime = $membership->period_end === null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membership certificate · {{ $member?->fullName() ?? 'PPRC' }}</title>
    @vite(['resources/css/app.css'])
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .cert-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        }
        .cert-border {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            padding: 3px;
            border-radius: 1rem;
        }
        .cert-inner {
            background: #ffffff;
            border-radius: calc(1rem - 3px);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="no-print mx-auto max-w-3xl px-4 py-6 text-center">
        <button type="button" onclick="window.print()" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800 transition-colors">
            Print / Save as PDF
        </button>
        <p class="mt-2 text-xs text-slate-500">This page is only shown for an active membership period.</p>
    </div>

    <main class="mx-auto max-w-3xl px-4 pb-16">
        <div class="cert-border cert-card">
            <div class="cert-inner p-10">

                {{-- Header --}}
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Pretoria Precision Rifle Club</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Membership Certificate</h1>
                    </div>
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                        <img src="{{ asset('pprclogo.png') }}" alt="PPRC" class="h-full w-full object-contain" />
                    </div>
                </div>

                <div class="mt-8 h-px bg-slate-200"></div>

                {{-- Member details --}}
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-8">
                    <div class="sm:col-span-2 space-y-5">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Certifies that</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $member?->fullName() }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                            @if ($member?->membership_number)
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Member number</p>
                                <p class="mt-1 font-mono text-base font-semibold text-slate-900">{{ $member->membership_number }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Membership type</p>
                                <p class="mt-1 text-base font-medium text-slate-900">{{ $membership->membership_type_name_snapshot }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Valid from</p>
                                <p class="mt-1 text-base tabular-nums text-slate-900">{{ $membership->period_start->format('j F Y') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Valid until</p>
                                <p class="mt-1 text-base tabular-nums text-slate-900">
                                    @if ($isLifetime)
                                        <span class="font-medium">Lifetime</span>
                                    @else
                                        {{ $membership->period_end->format('j F Y') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- QR Code --}}
                    <div class="flex flex-col items-center justify-center text-center">
                        <div id="qr-code" class="rounded-xl border border-slate-200 bg-slate-50 p-3"></div>
                        <p class="mt-2 text-[10px] font-medium text-slate-400">Scan to verify</p>
                    </div>
                </div>

                <div class="mt-8 h-px bg-slate-200"></div>

                {{-- Footer --}}
                <div class="mt-6 flex items-center justify-between text-xs text-slate-400">
                    <div>
                        @if ($membership->certificate_issued_at)
                            Issued {{ $membership->certificate_issued_at->format('j F Y') }}
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="font-mono">{{ Str::upper(Str::substr($membership->certificate_token, 0, 8)) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var qr = qrcode(0, 'M');
            qr.addData(@js($verifyUrl));
            qr.make();

            var container = document.getElementById('qr-code');
            var cellSize = 4;
            var margin = 0;
            container.innerHTML = qr.createSvgTag(cellSize, margin);

            var svg = container.querySelector('svg');
            if (svg) {
                svg.setAttribute('width', '140');
                svg.setAttribute('height', '140');
                svg.style.display = 'block';
            }
        });
    </script>
</body>
</html>
