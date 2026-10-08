@php
    $bannerUrl = $event->bannerUrl();
@endphp
<x-site.layout
    :title="$event->title"
    :description="$event->summary ?? 'PPRC match details'"
>
    {{-- Match header. When results are published the match is "closed off": the
         results table becomes the primary content and the match info collapses
         into a disclosure further down. --}}
    <x-site.section :padding="$resultsPublished ? 'lg-hero' : 'match-main'">
        <div>
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
                        @if ($resultsPublished)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-success-400/30 bg-success-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-success-200">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                Results in
                            </span>
                        @endif
                    </div>
                    <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $event->title }}</h1>
                    @if ($event->summary)
                        <p class="max-w-3xl text-lg text-slate-300">{{ $event->summary }}</p>
                    @endif
                </div>

                {{-- Registration-first summary: date, venue, prices, deadline
                     and (when open) a prominent Enter match button. Match book
                     links sit below it as secondary context. --}}
                @unless ($resultsPublished)
                    <div class="mt-6">
                        @include('site.matches._registration-summary')
                    </div>
                @endunless

                @if ($event->hasMatchBook())
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <a href="{{ $event->matchBookUrl() }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H6.75A2.25 2.25 0 0 0 4.5 8.25v9A2.25 2.25 0 0 0 6.75 19.5h9a2.25 2.25 0 0 0 2.25-2.25V10.5M19.5 4.5h-6m6 0v6m0-6L9 15"/></svg>
                            Open match book
                        </a>
                        <a href="{{ $event->matchBookUrl() }}" download
                           class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9 4.5 4.5m0 0 4.5-4.5m-4.5 4.5V3"/></svg>
                            Download PDF
                        </a>
                    </div>
                @endif

                @if ($bannerUrl)
                    <div class="mx-auto mt-8 max-w-sm">
                        <x-site.match-banner :src="$bannerUrl" :alt="$event->title" size="sidebar" />
                    </div>
                @endif

                @unless ($resultsPublished)
                    <div class="mt-8">
                        @include('site.matches._details')
                    </div>

                    @unless ($event->isFinished())
                        <div id="enter" class="scroll-mt-28 mt-10">
                            <livewire:site.event-register :event="$event" :key="'evt-reg-'.$event->id" />
                        </div>
                    @endunless
                @endunless
        </div>
    </x-site.section>

    @if ($resultsPublished)
        {{-- Results take centre stage once the match is closed off. --}}
        @include('site.matches._results')

        {{-- Everything about the match is tucked behind a toggle — it's not
             needed once results are out, but stays available on demand. --}}
        <x-site.section padding="sm">
            <div x-data="{ open: false }" class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02]">
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-white/[0.03] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/60 sm:px-6"
                >
                    <span class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-400">Match details</span>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/[0.03] text-slate-300">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                             class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }">
                            <path d="m5 7.5 5 5 5-5" />
                        </svg>
                    </span>
                </button>
                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="border-t border-white/10 px-5 py-6 sm:px-6 sm:py-8"
                >
                    @include('site.matches._details')
                </div>
            </div>
        </x-site.section>
    @else
        @include('site.matches._results')
    @endif

    @php
        $galleryPhotos = $event->galleryPhotos;
        $hasGallery = $galleryPhotos->isNotEmpty();
    @endphp

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
        @php
            // Only a real, positive squad number counts as "squadded" — entries
            // with a null / 0 / blank squad number are simply registered shooters.
            $isSquadded = $squads->keys()->contains(fn ($k) => filled($k) && (int) $k > 0);
            $orderedKeys = $squads->keys()
                ->sortBy(fn ($k) => filled($k) && (int) $k > 0 ? (int) $k : PHP_INT_MAX)
                ->values();
        @endphp
        <x-site.section padding="default" id="squads">
            {{-- The shooter list is useful but long; on a phone it buries the
                 details and gallery further down. We collapse it below md and
                 open it by default on tablet/desktop so no behaviour changes
                 above sm. `matchMedia` keeps the state in sync when the
                 viewport is resized. --}}
            <div
                x-data="{
                    open: window.matchMedia('(min-width: 768px)').matches,
                    init() {
                        const mq = window.matchMedia('(min-width: 768px)');
                        const sync = (e) => { this.open = e.matches || this.open; };
                        if (mq.addEventListener) {
                            mq.addEventListener('change', sync);
                        } else {
                            mq.addListener(sync);
                        }
                    },
                }"
                class="rounded-2xl border border-white/10 bg-white/[0.02]"
            >
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-controls="squad-list"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-white/[0.03] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/60 sm:px-6"
                >
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Shooters</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight sm:text-2xl">{{ $isSquadded ? 'Squads' : 'Registered shooters' }}</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-500">{{ $squads->flatten()->count() }} {{ \Illuminate\Support\Str::plural('shooter', $squads->flatten()->count()) }}</span>
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/[0.03] text-slate-300 md:hidden">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                 class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }" aria-hidden="true">
                                <path d="m5 7.5 5 5 5-5" />
                            </svg>
                        </span>
                    </div>
                </button>

                <div
                    id="squad-list"
                    x-show="open"
                    x-cloak
                    class="border-t border-white/10 px-5 py-6 sm:px-6"
                >
            <div @class(['grid gap-5', 'md:grid-cols-2 xl:grid-cols-3' => $isSquadded])>
                @foreach ($orderedKeys as $squadKey)
                    @php
                        $entries = $squads[$squadKey];
                        $isRealSquad = filled($squadKey) && (int) $squadKey > 0;
                    @endphp
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                        <div @class(['flex items-baseline gap-3 border-b border-white/10 pb-3', 'justify-between' => $isSquadded, 'justify-end' => ! $isSquadded])>
                            @if ($isSquadded)
                                <h3 class="text-base font-semibold text-white">
                                    {{ $isRealSquad ? 'Squad '.$squadKey : 'Unassigned' }}
                                </h3>
                            @endif
                            <span class="text-xs uppercase tracking-wider text-slate-500">
                                {{ $entries->count() }} {{ \Illuminate\Support\Str::plural('shooter', $entries->count()) }}
                            </span>
                        </div>
                        <ol class="mt-3 space-y-2 text-sm">
                            @foreach ($entries as $entry)
                                @php
                                    $name = $entry->shooterName();
                                    $division = $entry->division;
                                    $category = $entry->category;
                                    $courseLabel = $event->courseLabel($entry->course);
                                    $rounds = $event->roundsForCourse($entry->course);
                                @endphp
                                <li class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 rounded-lg px-2 py-1.5 hover:bg-white/[0.04]">
                                    @if ($entry->firing_order)
                                        <span class="w-5 shrink-0 text-xs tabular-nums text-slate-500">{{ $entry->firing_order }}.</span>
                                    @endif
                                    <span class="font-medium text-white">{{ $name }}</span>
                                    @if ($entry->isMatchDirector())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-300 ring-1 ring-inset ring-amber-500/30" title="Match Director">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                                            MD
                                        </span>
                                    @endif
                                    @if ($entry->paymentConfirmed())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-300 ring-1 ring-inset ring-emerald-500/30">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                            Confirmed
                                        </span>
                                    @endif
                                    @if ($division)
                                        <span class="text-xs uppercase tracking-wider text-slate-400">{{ $division }}</span>
                                    @endif
                                    @if ($category)
                                        <span class="text-xs text-slate-500">·</span>
                                        <span class="text-xs uppercase tracking-wider text-slate-400">{{ $category }}</span>
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
                </div>
            </div>
        </x-site.section>
    @endif

    {{-- Mobile-only sticky Enter-match bar. Only rendered when the match is
         actually accepting entries — a closed or full match must never offer
         an entry affordance. The bar links to #enter which scrolls to the
         Livewire registration component higher up the page. --}}
    @if (! $resultsPublished && $event->isRegistrationOpen())
        @php $memberCents = $event->memberPriceCents(); @endphp
        <div class="pointer-events-none fixed inset-x-0 bottom-0 z-30 flex justify-center p-3 lg:hidden" role="region" aria-label="Enter this match">
            <a href="#enter"
               class="pointer-events-auto flex w-full max-w-md items-center justify-between gap-3 rounded-2xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-[0_18px_40px_-12px_rgba(0,0,0,0.65)] ring-1 ring-white/10 transition hover:bg-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40">
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Enter match
                </span>
                @if ($memberCents !== null)
                    <span class="tabular-nums text-xs font-medium text-white/80">from R {{ number_format($memberCents / 100, 2) }}</span>
                @endif
            </a>
        </div>
        {{-- Leave breathing room so the sticky bar never covers the footer. --}}
        <div class="h-20 lg:hidden" aria-hidden="true"></div>
    @endif
</x-site.layout>
