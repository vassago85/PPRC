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
            <div class="aspect-[21/9] w-full sm:aspect-[3/1]">
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

            @if ($event->hasMatchBook())
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <a href="{{ $event->matchBookUrl() }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H6.75A2.25 2.25 0 0 0 4.5 8.25v9A2.25 2.25 0 0 0 6.75 19.5h9a2.25 2.25 0 0 0 2.25-2.25V10.5M19.5 4.5h-6m6 0v6m0-6L9 15"/></svg>
                        Open match book
                    </a>
                    <a href="{{ $event->matchBookUrl() }}" download
                       class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-brand-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9 4.5 4.5m0 0 4.5-4.5m-4.5 4.5V3"/></svg>
                        Download PDF
                    </a>
                </div>
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
            <div class="prose prose-invert mt-10 max-w-3xl border-t border-white/10 pt-10">
                {!! $event->description !!}
            </div>
        @endif

        @unless ($event->isFinished())
            <div id="enter" class="scroll-mt-28 {{ filled($event->description) ? 'mt-10' : 'mt-8' }}">
                <livewire:site.event-register :event="$event" :key="'evt-reg-'.$event->id" />
            </div>
        @endunless
    </x-site.section>

    @php
        $galleryPhotos = $event->galleryPhotos;
        $hasGallery = $galleryPhotos->isNotEmpty();
        $sidebarPhotos = $hasGallery ? $galleryPhotos->take(9) : collect();
        $featuredPhoto = $sidebarPhotos->first();
        $thumbPhotos = $sidebarPhotos->slice(1)->take(8);
        $hasMorePhotos = $galleryPhotos->count() > $sidebarPhotos->count();
    @endphp

    <x-site.section padding="default" id="results">
        <div class="flex gap-8">
            {{-- Main results column --}}
            <div class="{{ $hasGallery ? 'w-full xl:w-[70%]' : 'w-full' }} min-w-0">
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
                    @php
                        $divisions = $results->pluck('division')->filter()->unique()->sort()->values();
                        $categories = $results->pluck('category')->filter()->unique()->sort()->values();

                        $selectedDivision = trim((string) request('division', ''));
                        $selectedCategory = trim((string) request('category', ''));
                        $openOnly = request()->boolean('open_only');

                        $filtered = $results
                            ->when($selectedDivision !== '', fn ($c) => $c->where('division', $selectedDivision))
                            ->when($selectedCategory !== '', fn ($c) => $c->where('category', $selectedCategory))
                            ->when($openOnly, fn ($c) => $c->whereNull('category'))
                            ->values();

                        $filterBaseUrl = route('matches.show', ['event' => $event->slug]).'#results';
                        $hasActiveFilter = $selectedDivision !== '' || $selectedCategory !== '' || $openOnly;
                    @endphp

                    @if ($divisions->isNotEmpty() || $categories->isNotEmpty())
                        <form method="get" action="{{ route('matches.show', ['event' => $event->slug]) }}#results"
                              class="mb-5 flex flex-wrap items-end gap-3">
                            @if ($divisions->isNotEmpty())
                                <label class="flex flex-col gap-1 text-xs uppercase tracking-[0.16em] text-slate-500">
                                    Division
                                    <select name="division" onchange="this.form.submit()"
                                            class="min-w-[10rem] rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                        <option value="">All</option>
                                        @foreach ($divisions as $d)
                                            <option value="{{ $d }}" {{ $selectedDivision === $d ? 'selected' : '' }}>{{ $d }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                            @if ($categories->isNotEmpty())
                                <label class="flex flex-col gap-1 text-xs uppercase tracking-[0.16em] text-slate-500">
                                    Category
                                    <select name="category" {{ $openOnly ? 'disabled' : '' }} onchange="this.form.submit()"
                                            class="min-w-[10rem] rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:opacity-40 disabled:cursor-not-allowed">
                                        <option value="">All</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c }}" {{ $selectedCategory === $c ? 'selected' : '' }}>{{ $c }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            @if ($categories->isNotEmpty())
                                <label class="flex cursor-pointer items-center gap-2 self-center rounded-lg border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-200 transition hover:border-white/20 hover:bg-slate-900/60">
                                    <input type="checkbox" name="open_only" value="1" {{ $openOnly ? 'checked' : '' }} onchange="this.form.submit()"
                                           class="h-4 w-4 rounded border-white/20 bg-slate-950 text-brand-500 focus:ring-2 focus:ring-brand-500/30" />
                                    <span>Open only <span class="text-xs text-slate-500">(hide {{ $categories->implode(', ') }})</span></span>
                                </label>
                            @endif

                            @if ($hasActiveFilter)
                                <a href="{{ $filterBaseUrl }}"
                                   class="rounded-lg border border-white/10 px-3 py-2 text-sm text-slate-300 transition hover:border-white/25 hover:bg-white/5">
                                    Clear
                                </a>
                            @endif

                            <span class="ml-auto self-end text-xs text-slate-500">
                                {{ $filtered->count() }} of {{ $results->count() }} shooters
                            </span>
                        </form>
                    @endif

                    @if ($filtered->isEmpty())
                        <x-site.card padding="lg" class="text-center border-dashed">
                            <p class="text-slate-300">No results match the current filter.</p>
                        </x-site.card>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-white/10">
                            <table class="min-w-full divide-y divide-white/10 text-sm">
                                <thead class="bg-white/[0.03] text-left text-xs uppercase tracking-[0.16em] text-slate-400">
                                    <tr>
                                        <th class="px-2 py-3 font-semibold sm:px-4">#</th>
                                        <th class="px-2 py-3 font-semibold sm:px-4">Shooter</th>
                                        <th class="hidden px-4 py-3 font-semibold md:table-cell">Division</th>
                                        <th class="hidden px-4 py-3 font-semibold md:table-cell">Category</th>
                                        <th class="px-2 py-3 text-right font-semibold sm:px-4">Score</th>
                                        <th class="px-2 py-3 text-right font-semibold sm:px-4">%</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 bg-slate-950/40">
                                    @foreach ($filtered as $r)
                                        <tr class="hover:bg-white/[0.02]">
                                            <td class="px-2 py-2.5 font-semibold text-white sm:px-4 sm:py-3">{{ $hasActiveFilter ? $loop->iteration : ($r->rank ?? '—') }}</td>
                                            <td class="px-2 py-2.5 sm:px-4 sm:py-3">
                                                <span class="text-slate-200">{{ $r->shooter_name }}</span>
                                                {{-- Show division/category inline on mobile --}}
                                                @if ($r->division || $r->category)
                                                    <span class="block text-xs text-slate-500 md:hidden">
                                                        {{ $r->division ?? '' }}{{ $r->division && $r->category ? ' · ' : '' }}{{ $r->category ?? '' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="hidden px-4 py-3 text-slate-400 md:table-cell">{{ $r->division ?? '—' }}</td>
                                            <td class="hidden px-4 py-3 text-slate-400 md:table-cell">{{ $r->category ?? '—' }}</td>
                                            <td class="px-2 py-2.5 text-right tabular-nums text-slate-200 sm:px-4 sm:py-3">{{ $r->displayScore() }}</td>
                                            <td class="px-2 py-2.5 text-right tabular-nums text-slate-400 sm:px-4 sm:py-3">
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
                @endif

                {{-- Mobile gallery: shown after results, hidden on xl --}}
                @if ($hasGallery)
                    <div class="mt-10 xl:hidden" id="gallery-mobile">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-white">Match Photos</h3>
                                <span class="text-xs text-slate-500">{{ $galleryPhotos->count() }} {{ \Illuminate\Support\Str::plural('photo', $galleryPhotos->count()) }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($sidebarPhotos->take(6) as $photo)
                                    <a href="{{ $photo->publicUrl() }}" target="_blank" rel="noopener"
                                       class="group aspect-[4/3] overflow-hidden rounded-xl">
                                        <img
                                            src="{{ $photo->publicUrl() }}"
                                            alt="{{ $photo->caption ?: $event->title }}"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-80"
                                            loading="lazy"
                                        />
                                    </a>
                                @endforeach
                            </div>
                            @if ($hasMorePhotos || $galleryPhotos->count() > 6)
                                <a href="#gallery" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-brand-400 transition hover:text-brand-300">
                                    View full gallery
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3"/></svg>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Desktop sticky gallery sidebar --}}
            @if ($hasGallery)
                <aside class="hidden xl:block xl:w-[30%]">
                    <div class="sticky top-24 space-y-4">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-white">Match Photos</h3>
                                <span class="text-xs text-slate-500">{{ $galleryPhotos->count() }} {{ \Illuminate\Support\Str::plural('photo', $galleryPhotos->count()) }}</span>
                            </div>

                            {{-- Featured image --}}
                            @if ($featuredPhoto)
                                <a href="{{ $featuredPhoto->publicUrl() }}" target="_blank" rel="noopener"
                                   class="group block aspect-[16/10] overflow-hidden rounded-xl">
                                    <img
                                        src="{{ $featuredPhoto->publicUrl() }}"
                                        alt="{{ $featuredPhoto->caption ?: $event->title }}"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03] group-hover:opacity-90"
                                        loading="lazy"
                                    />
                                </a>
                                @if ($featuredPhoto->caption)
                                    <p class="mt-2 text-xs text-slate-500">{{ $featuredPhoto->caption }}</p>
                                @endif
                            @endif

                            {{-- Thumbnail grid --}}
                            @if ($thumbPhotos->isNotEmpty())
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    @foreach ($thumbPhotos as $photo)
                                        <a href="{{ $photo->publicUrl() }}" target="_blank" rel="noopener"
                                           class="group aspect-[4/3] overflow-hidden rounded-lg">
                                            <img
                                                src="{{ $photo->publicUrl() }}"
                                                alt="{{ $photo->caption ?: $event->title }}"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-80"
                                                loading="lazy"
                                            />
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            {{-- CTA --}}
                            @if ($hasMorePhotos)
                                <a href="#gallery"
                                   class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-white/10">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/></svg>
                                    View full gallery
                                </a>
                            @endif
                        </div>
                    </div>
                </aside>
            @endif
        </div>
    </x-site.section>

    @if ($hasGallery)
        <x-site.section padding="default" id="gallery">
            <div class="mb-8 flex items-end justify-between gap-4">
                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Gallery</h2>
                <span class="text-sm text-slate-500">{{ $galleryPhotos->count() }} {{ \Illuminate\Support\Str::plural('photo', $galleryPhotos->count()) }}</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($galleryPhotos as $photo)
                    <figure class="group overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02]">
                        <a href="{{ $photo->publicUrl() }}" target="_blank" rel="noopener" class="block aspect-[4/3] overflow-hidden">
                            <img
                                src="{{ $photo->publicUrl() }}"
                                alt="{{ $photo->caption ?: $event->title }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                loading="lazy"
                            />
                        </a>
                        @if ($photo->caption)
                            <figcaption class="px-4 py-3 text-sm text-slate-400">{{ $photo->caption }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        </x-site.section>
    @endif

    @if ($squads->isNotEmpty() && $squads->flatten()->count() > 0)
        <x-site.section padding="default" id="squads">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Shooters</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Squads</h2>
                </div>
                <p class="text-sm text-slate-500">{{ $squads->flatten()->count() }} entries</p>
            </div>

            @php
                $orderedKeys = $squads->keys()
                    ->sortBy(fn ($k) => $k === null ? PHP_INT_MAX : (int) $k)
                    ->values();
            @endphp
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($orderedKeys as $squadKey)
                    @php $entries = $squads[$squadKey]; @endphp
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                        <div class="flex items-baseline justify-between gap-3 border-b border-white/10 pb-3">
                            <h3 class="text-base font-semibold text-white">
                                {{ $squadKey === null ? 'Unassigned' : 'Squad '.$squadKey }}
                            </h3>
                            <span class="text-xs uppercase tracking-wider text-slate-500">
                                {{ $entries->count() }} {{ \Illuminate\Support\Str::plural('shooter', $entries->count()) }}
                            </span>
                        </div>
                        <ol class="mt-3 space-y-2 text-sm">
                            @foreach ($entries as $entry)
                                @php
                                    $name = $entry->shooterName();
                                    $division = $entry->division;
                                    $courseLabel = $event->courseLabel($entry->course);
                                    $rounds = $event->roundsForCourse($entry->course);
                                @endphp
                                <li class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 rounded-lg px-2 py-1.5 hover:bg-white/[0.04]">
                                    @if ($entry->firing_order)
                                        <span class="w-5 shrink-0 text-xs tabular-nums text-slate-500">{{ $entry->firing_order }}.</span>
                                    @endif
                                    <span class="font-medium text-white">{{ $name }}</span>
                                    @if ($division)
                                        <span class="text-xs uppercase tracking-wider text-slate-400">{{ $division }}</span>
                                    @endif
                                    @if ($courseLabel)
                                        <span class="text-xs text-slate-500">·</span>
                                        <span class="text-xs text-slate-400">{{ $courseLabel }}</span>
                                    @endif
                                    @if ($rounds)
                                        <span class="text-xs text-slate-500">·</span>
                                        <span class="text-xs tabular-nums text-slate-400">{{ $rounds }} rounds</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </div>
        </x-site.section>
    @endif
</x-site.layout>
