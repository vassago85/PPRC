@php
    $memberCents = $event->memberPriceCents();
    $nonMemberCents = $event->nonMemberPriceCents();
    $juniorCents = $event->junior_price_cents;
@endphp

<dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @if ($event->location_name)
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Venue</dt>
            <dd class="mt-2 font-medium text-white">{{ $event->location_name }}</dd>
            @if ($event->location_address)
                <dd class="mt-1 text-sm text-slate-400">{{ $event->location_address }}</dd>
            @endif
        </div>
    @endif

    @if ($event->round_count || $event->club_round_count)
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Rounds</dt>
            @if ($event->club_round_count !== null)
                <dd class="mt-3 space-y-2 text-sm">
                    @if ($event->round_count)
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-slate-400">Provincial / full course</span>
                            <span class="font-semibold tabular-nums text-white">{{ $event->round_count }}</span>
                        </div>
                    @endif
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-slate-400">PPRC club course</span>
                        <span class="font-semibold tabular-nums text-white">{{ $event->club_round_count }}</span>
                    </div>
                </dd>
            @else
                <dd class="mt-2 font-medium text-white">{{ $event->round_count }}</dd>
            @endif
        </div>
    @endif

    @if ($event->matchDirectorDisplay() !== '')
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Match director</dt>
            <dd class="mt-2 font-medium text-white">{{ $event->matchDirectorDisplay() }}</dd>
        </div>
    @endif

    @if ($memberCents !== null || $nonMemberCents !== null || $juniorCents !== null)
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Entry fee</dt>
            <dd class="mt-2 space-y-1 text-sm">
                @if ($memberCents !== null)
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-slate-400">Members</span>
                        <span class="font-semibold text-white tabular-nums">R {{ number_format($memberCents / 100, 2) }}</span>
                    </div>
                @endif
                @if ($juniorCents !== null)
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-slate-400">Juniors</span>
                        <span class="font-semibold text-white tabular-nums">R {{ number_format($juniorCents / 100, 2) }}</span>
                    </div>
                @endif
                @if ($nonMemberCents !== null)
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-slate-400">Non-members</span>
                        <span class="font-semibold text-white tabular-nums">R {{ number_format($nonMemberCents / 100, 2) }}</span>
                    </div>
                @endif
            </dd>
        </div>
    @endif

    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Entries</dt>
        <dd class="mt-2 font-medium text-white">
            {{ $event->registrations_count ?? 0 }}@if ($event->max_entries) / {{ $event->max_entries }}@endif
        </dd>
        @if ($event->isFinished())
            <dd class="mt-1 text-sm text-slate-400">Match {{ $event->status->value === 'cancelled' ? 'cancelled' : 'completed' }}</dd>
        @elseif ($event->isRegistrationOpen())
            <dd class="mt-1 text-sm text-success-300">Registrations open</dd>
        @elseif ($event->registrations_open === false)
            <dd class="mt-1 text-sm text-slate-400">Registrations closed</dd>
        @endif
    </div>
</dl>

@if ($event->stage_count || $event->stage_time_seconds || $event->shots_per_stage_full || $event->shots_per_stage_club || $event->tiebreaker_stage_number)
    <div class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-5 sm:p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Stage spec</p>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @if ($event->stage_count)
                <div>
                    <dt class="text-xs uppercase tracking-wider text-slate-500">Stages</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums text-white">{{ $event->stage_count }}</dd>
                </div>
            @endif
            @if ($event->shots_per_stage_full)
                <div>
                    <dt class="text-xs uppercase tracking-wider text-slate-500">Shots / stage{{ $event->offersBothCourses() ? ' (full)' : '' }}</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums text-white">{{ $event->shots_per_stage_full }}</dd>
                </div>
            @endif
            @if ($event->shots_per_stage_club)
                <div>
                    <dt class="text-xs uppercase tracking-wider text-slate-500">Shots / stage (club)</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums text-white">{{ $event->shots_per_stage_club }}</dd>
                </div>
            @endif
            @if ($event->stage_time_seconds)
                <div>
                    <dt class="text-xs uppercase tracking-wider text-slate-500">Time / stage</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums text-white">{{ $event->stage_time_seconds }}<span class="ml-1 text-base font-normal text-slate-400">sec</span></dd>
                </div>
            @endif
            @if ($event->tiebreaker_stage_number)
                <div>
                    <dt class="text-xs uppercase tracking-wider text-slate-500">Tie-breaker</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums text-white">Stage {{ $event->tiebreaker_stage_number }}</dd>
                </div>
            @endif
        </dl>
    </div>
@endif

@if ($event->is_saprf_match && ! $event->isFinished())
    <div class="mt-8 overflow-hidden rounded-2xl border border-amber-400/30 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-amber-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">SAPRF-sanctioned match</p>
                    <p class="mt-1 text-sm text-slate-200">
                        This match counts towards SAPRF rankings. SAPRF members can enter and pay through the SAPRF portal.
                    </p>
                </div>
            </div>
            @if ($event->saprf_url)
                <a href="{{ $event->saprf_url }}" target="_blank" rel="noopener"
                   class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-md transition hover:bg-amber-400">
                    Register on SAPRF
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H6.75A2.25 2.25 0 004.5 8.25v9A2.25 2.25 0 006.75 19.5h9a2.25 2.25 0 002.25-2.25V10.5M19.5 4.5h-6m6 0v6m0-6L9 15"/></svg>
                </a>
            @endif
        </div>
    </div>
@endif

@if ($event->description)
    <div class="prose prose-invert mt-8 max-w-3xl">
        {!! $event->description !!}
    </div>
@endif
