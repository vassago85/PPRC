@php
    $memberCents = $event->memberPriceCents();
    $nonMemberCents = $event->nonMemberPriceCents();
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

    <x-site.section padding="lg">
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

        <dl class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @if ($event->location_name)
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Venue</dt>
                    <dd class="mt-2 font-medium text-white">{{ $event->location_name }}</dd>
                    @if ($event->location_address)
                        <dd class="mt-1 text-sm text-slate-400">{{ $event->location_address }}</dd>
                    @endif
                </div>
            @endif

            @if ($event->round_count)
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Rounds</dt>
                    <dd class="mt-2 font-medium text-white">{{ $event->round_count }}</dd>
                </div>
            @endif

            @if ($event->matchDirectorDisplay() !== '')
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Match director</dt>
                    <dd class="mt-2 font-medium text-white">{{ $event->matchDirectorDisplay() }}</dd>
                </div>
            @endif

            @if ($memberCents !== null || $nonMemberCents !== null)
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Entry fee</dt>
                    <dd class="mt-2 space-y-1 text-sm">
                        @if ($memberCents !== null)
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-slate-400">Members</span>
                                <span class="font-semibold text-white tabular-nums">R {{ number_format($memberCents / 100, 2) }}</span>
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
                @if ($event->isRegistrationOpen())
                    <dd class="mt-1 text-sm text-success-300">Registrations open</dd>
                @elseif ($event->registrations_open === false)
                    <dd class="mt-1 text-sm text-slate-400">Registrations closed</dd>
                @endif
            </div>
        </dl>
    </x-site.section>

    @if ($event->description)
        <x-site.section tone="muted" padding="default">
            <div class="prose prose-invert max-w-3xl">
                {!! $event->description !!}
            </div>
        </x-site.section>
    @endif

    <x-site.section padding="default" id="enter">
        <livewire:site.event-register :event="$event" :key="'evt-reg-'.$event->id" />
    </x-site.section>

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
