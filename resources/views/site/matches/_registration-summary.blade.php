@php
    /** @var \App\Models\Event $event */
    $state = $event->registrationState();
    $memberCents = $event->memberPriceCents();
    $nonMemberCents = $event->nonMemberPriceCents();
    $juniorCents = $event->junior_price_cents;

    // Status badge class map — stays in blade so Tailwind's purge sees every
    // concrete class string.
    $stateClass = match ($state) {
        \App\Enums\RegistrationState::Open       => 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200',
        \App\Enums\RegistrationState::Closed     => 'border-slate-400/30 bg-slate-500/10 text-slate-300',
        \App\Enums\RegistrationState::Full       => 'border-amber-400/40 bg-amber-500/10 text-amber-200',
        \App\Enums\RegistrationState::NotYetOpen => 'border-sky-400/30 bg-sky-500/10 text-sky-200',
        \App\Enums\RegistrationState::Finished   => 'border-slate-400/30 bg-slate-500/10 text-slate-300',
    };
@endphp

<section
    aria-labelledby="match-reg-heading"
    class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/[0.07] to-white/[0.02] p-5 shadow-[0_20px_50px_-24px_rgba(0,0,0,0.65)] sm:p-6"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <h2 id="match-reg-heading" class="sr-only">Match summary and registration</h2>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $stateClass }}">
                {{ $state->label() }}
            </span>
            @if ($state === \App\Enums\RegistrationState::NotYetOpen && $event->registrations_open_at)
                <span class="text-xs text-slate-400">Opens {{ $event->registrations_open_at->format('d M, H:i') }}</span>
            @elseif ($state === \App\Enums\RegistrationState::Open && $event->registrations_close_at)
                <span class="text-xs text-slate-400">Closes {{ $event->registrations_close_at->format('d M, H:i') }}</span>
            @elseif ($state === \App\Enums\RegistrationState::Closed && $event->registrations_close_at)
                <span class="text-xs text-slate-400">Closed {{ $event->registrations_close_at->format('d M') }}</span>
            @endif
        </div>

        @if ($state === \App\Enums\RegistrationState::Open)
            <a
                href="#enter"
                class="btn-brand inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:brightness-110"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Enter match
            </a>
        @endif
    </div>

    <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">When</dt>
            <dd class="mt-1.5 text-sm text-white">
                {{ $event->start_date?->format('l, d F Y') ?? 'Date to be confirmed' }}
                @if ($event->start_time)
                    <br><span class="text-slate-400">{{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}</span>
                @endif
            </dd>
        </div>

        @if ($event->location_name)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Where</dt>
                <dd class="mt-1.5 text-sm text-white">
                    {{ $event->location_name }}
                    @if ($event->location_address)
                        <br><span class="text-slate-400">{{ $event->location_address }}</span>
                    @endif
                </dd>
            </div>
        @endif

        @if ($memberCents !== null || $nonMemberCents !== null || $juniorCents !== null)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Entry fee</dt>
                <dd class="mt-1.5 space-y-0.5 text-sm">
                    @if ($memberCents !== null)
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-slate-400">Members</span>
                            <span class="font-semibold tabular-nums text-white">R {{ number_format($memberCents / 100, 2) }}</span>
                        </div>
                    @endif
                    @if ($nonMemberCents !== null)
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-slate-400">Guests</span>
                            <span class="font-semibold tabular-nums text-white">R {{ number_format($nonMemberCents / 100, 2) }}</span>
                        </div>
                    @endif
                    @if ($juniorCents !== null)
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-slate-400">Juniors</span>
                            <span class="font-semibold tabular-nums text-white">R {{ number_format($juniorCents / 100, 2) }}</span>
                        </div>
                    @endif
                </dd>
            </div>
        @endif

        <div>
            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Registration deadline</dt>
            <dd class="mt-1.5 text-sm text-white">
                @if ($event->registrations_close_at)
                    {{ $event->registrations_close_at->format('D d M') }}
                    <br><span class="text-slate-400">by {{ $event->registrations_close_at->format('H:i') }}</span>
                @else
                    <span class="text-slate-400">No fixed deadline</span>
                @endif
            </dd>
        </div>
    </dl>
</section>
