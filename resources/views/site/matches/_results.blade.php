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
