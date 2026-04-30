@php
    $valid = $membership
        && $membership->status === \App\Enums\MembershipStatus::Active
        && ($membership->period_end === null || $membership->period_end->isFuture());
    $member = $membership?->member;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify membership · PPRC</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="mx-auto max-w-md px-4 py-12">
        <div class="text-center mb-8">
            <img src="{{ asset('pprclogo.png') }}" alt="PPRC" class="mx-auto h-16 w-16 rounded-full border-2 border-slate-200" />
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                Pretoria Precision Rifle Club
            </p>
        </div>

        @if ($valid)
            <div class="rounded-2xl border border-green-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-green-800">Verified member</h1>
                        <p class="text-sm text-green-600">This membership is active and valid.</p>
                    </div>
                </div>

                <dl class="space-y-3 border-t border-slate-100 pt-5">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Name</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $member?->fullName() }}</dd>
                    </div>
                    @if ($member?->membership_number)
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Member #</dt>
                        <dd class="text-sm font-mono font-medium text-slate-900">{{ $member->membership_number }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Membership</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $membership->membership_type_name_snapshot }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Valid from</dt>
                        <dd class="text-sm tabular-nums text-slate-900">{{ $membership->period_start->format('j M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Valid until</dt>
                        <dd class="text-sm tabular-nums text-slate-900">
                            {{ $membership->period_end ? $membership->period_end->format('j M Y') : 'Lifetime' }}
                        </dd>
                    </div>
                </dl>
            </div>
        @else
            <div class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-red-800">Not verified</h1>
                        <p class="text-sm text-red-600">
                            @if (! $membership)
                                This certificate link is invalid or has expired.
                            @elseif ($membership->status !== \App\Enums\MembershipStatus::Active)
                                This membership is no longer active (status: {{ $membership->status->value }}).
                            @else
                                This membership period has ended.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <p class="mt-6 text-center text-xs text-slate-400">
            <a href="{{ url('/') }}" class="hover:text-slate-600">pretoriaprc.co.za</a>
        </p>
    </main>
</body>
</html>
