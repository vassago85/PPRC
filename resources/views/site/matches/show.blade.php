@php
    $memberCents = $event->memberPriceCents();
    $nonMemberCents = $event->nonMemberPriceCents();
    $juniorCents = $event->junior_price_cents;
    $bannerUrl = $event->bannerUrl();
@endphp
<x-site.layout
    :title="$event->title"
    :description="$event->summary ?? 'PPRC match details'"
>
    @if ($bannerUrl)
        <div class="relative overflow-hidden">
            <div class="aspect-[21/9] w-full sm:aspect-[21/7]">
                <img src="{{ $bannerUrl }}" alt="{{ $event->title }}" class="h-full w-full object-cover" />
            </div>
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-slate-950/10"></div>
        </div>
    @endif

    <x-site.section padding="match-main">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center gap-3">
                @if ($event->matchFormat)
                    <span class="inline-flex items-center rounded-full border border-brand-400/30 bg-brand-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-brand-200">
                        {{ $event->matchFormat->name }}
                    </span>
                @endif
                <span class="text-sm text-slate-400">
                    {{ $event->start_date?->format('l, d F Y') }}
                    @if ($event->start_time)
                        &middot; {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}
                    @endif
                </span>
            </div>
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $event->title }}</h1>
            @if ($event->summary)
                <p class="max-w-3xl text-lg text-slate-300">{{ $event->summary }}</p>
            @endif
        </div>

        <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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

        @if ($event->is_saprf_match)
            <div class="mt-8 overflow-hidden rounded-2xl border border-amber-400/30 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-amber-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">SAPRF-sanctioned match</p>
                            <p class="mt-1 text-sm text-slate-200">
                                This match counts towards SAPRF rankings. SAPRF members can enter and pay through the SAPRF portal — entry is still free of charge to PPRC for SAPRF entries.
                            </p>
                        </div>
                    </div>
                    @if ($event->saprf_url && ! $event->isFinished())
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
            <div class="prose prose-invert mt-10 max-w-3xl border-t border-white/10 pt-10">
                {!! $event->description !!}
            </div>
        @endif

        @unless ($event->isFinished())
            <div id="enter" @class(['scroll-mt-28', 'mt-10' => filled($event->description), 'mt-8' => ! filled($event->description)])>
                <livewire:site.event-register :event="$event" :key="'evt-reg-'.$event->id" />
            </div>
        @endunless
    </x-site.section>

    @if ($event->hasMatchBook())
        <x-site.section padding="default" id="match-book">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Stage briefing</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Match book</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $event->matchBookUrl() }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H6.75A2.25 2.25 0 0 0 4.5 8.25v9A2.25 2.25 0 0 0 6.75 19.5h9a2.25 2.25 0 0 0 2.25-2.25V10.5M19.5 4.5h-6m6 0v6m0-6L9 15"/></svg>
                        Open in new tab
                    </a>
                    <a href="{{ $event->matchBookUrl() }}" download
                       class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-brand-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9 4.5 4.5m0 0 4.5-4.5m-4.5 4.5V3"/></svg>
                        Download PDF
                    </a>
                </div>
            </div>

            {{-- Browser PDF viewer. Uses <object> with a <embed> fallback so iOS Safari, --}}
            {{-- Firefox and Chrome all render natively without an external JS lib. --}}
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/40">
                <object
                    data="{{ $event->matchBookUrl() }}#view=FitH"
                    type="application/pdf"
                    class="block w-full"
                    style="height: min(80vh, 900px); min-height: 480px;">
                    <div class="p-8 text-center text-sm text-slate-300">
                        <p>Your browser couldn't open the PDF inline.</p>
                        <p class="mt-2">
                            <a href="{{ $event->matchBookUrl() }}" target="_blank" rel="noopener" class="font-semibold text-brand-300 hover:text-brand-200">
                                Open the match book in a new tab
                            </a>
                            or use the Download button above.
                        </p>
                    </div>
                </object>
            </div>
        </x-site.section>
    @endif

    @if ($event->galleryPhotos->isNotEmpty())
        <x-site.section padding="default" id="gallery">
            <div class="mb-8 flex items-end justify-between gap-4">
                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Gallery</h2>
                <span class="text-sm text-slate-500">{{ $event->galleryPhotos->count() }} photos</span>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($event->galleryPhotos as $photo)
                    <figure class="group overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02]">
                        <div class="aspect-[4/3] overflow-hidden">
                            <img
                                src="{{ $photo->publicUrl() }}"
                                alt="{{ $photo->caption ?: $event->title }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                loading="lazy"
                            />
                        </div>
                        @if ($photo->caption)
                            <figcaption class="px-4 py-3 text-sm text-slate-400">{{ $photo->caption }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        </x-site.section>
    @endif

    <x-site.section padding="default" id="results">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Results</h2>
            @if ($resultsPublished)
                <span class="text-sm text-slate-500">Published {{ $event->results_published_at?->format('d M Y') }}</span>
            @endif
        </div>

        @if (! $resultsPublished)
            <x-site.card padding="lg" class="text-center border-dashed">
                <p class="text-slate-300">Results for this match haven't been published yet.</p>
                <p class="mt-2 text-sm text-slate-500">Placings will appear here after the match director publishes them.</p>
            </x-site.card>
        @elseif ($results->isEmpty())
            <x-site.card padding="lg" class="text-center border-dashed">
                <p class="text-slate-300">No results were recorded for this match.</p>
            </x-site.card>
        @else
            <div class="overflow-hidden rounded-2xl border border-white/10">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/[0.03] text-left text-xs uppercase tracking-[0.16em] text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">#</th>
                            <th class="px-4 py-3 font-semibold">Shooter</th>
                            <th class="px-4 py-3 font-semibold">Division</th>
                            <th class="px-4 py-3 font-semibold">Class</th>
                            <th class="px-4 py-3 text-right font-semibold">Score</th>
                            <th class="px-4 py-3 text-right font-semibold">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 bg-slate-950/40">
                        @foreach ($results as $r)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-semibold text-white">{{ $r->rank ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-200">{{ $r->shooter_name }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $r->division ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $r->class ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-200">{{ $r->displayScore() }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-400">
                                    @if ($r->score_percentage !== null)
                                        {{ number_format((float) $r->score_percentage, 2) }}%
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-site.section>
</x-site.layout>
