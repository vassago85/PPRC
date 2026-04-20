<x-site.layout>
    @foreach ($sections as $section)
        @switch($section->type)
            @case('hero')
                <section class="relative isolate overflow-hidden bg-slate-900 text-white">
                    <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 opacity-90"></div>
                    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
                        @if ($section->eyebrow)
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">{{ $section->eyebrow }}</p>
                        @endif
                        <h1 class="mt-4 text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight">{{ $section->title }}</h1>
                        @if ($section->subtitle)
                            <p class="mt-6 max-w-2xl text-lg text-slate-300">{{ $section->subtitle }}</p>
                        @endif
                        @if ($section->cta_label && $section->cta_url)
                            <div class="mt-10 flex items-center gap-4">
                                <a href="{{ $section->cta_url }}" class="inline-flex items-center rounded-md bg-white text-slate-900 px-5 py-2.5 text-sm font-medium hover:bg-slate-100">
                                    {{ $section->cta_label }}
                                </a>
                                <a href="{{ url('/about') }}" class="text-sm text-slate-300 hover:text-white">Learn more →</a>
                            </div>
                        @endif
                    </div>
                </section>
                @break

            @case('stats')
                <section class="border-y border-slate-200 bg-slate-50">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-2 md:grid-cols-4 gap-8">
                        @foreach (($section->meta['items'] ?? []) as $item)
                            <div>
                                <p class="text-3xl font-semibold text-slate-900">{{ $item['value'] ?? '' }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $item['label'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
                @break

            @case('feature_grid')
                <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
                    @if ($section->eyebrow)
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $section->eyebrow }}</p>
                    @endif
                    <h2 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $section->title }}</h2>
                    @if ($section->subtitle)
                        <p class="mt-3 max-w-2xl text-slate-600">{{ $section->subtitle }}</p>
                    @endif
                    <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach (($section->meta['items'] ?? []) as $item)
                            <div class="rounded-xl border border-slate-200 p-6 hover:border-slate-300 transition">
                                <h3 class="text-base font-semibold text-slate-900">{{ $item['title'] ?? '' }}</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ $item['body'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
                @break

            @case('events_teaser')
                <section class="bg-slate-50 border-y border-slate-200">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                        <div>
                            @if ($section->eyebrow)
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $section->eyebrow }}</p>
                            @endif
                            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $section->title }}</h2>
                            @if ($section->subtitle)
                                <p class="mt-2 text-slate-600">{{ $section->subtitle }}</p>
                            @endif
                        </div>
                        @if ($section->cta_url)
                            <a href="{{ $section->cta_url }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100">
                                {{ $section->cta_label ?? 'See all' }}
                            </a>
                        @endif
                    </div>
                </section>
                @break

            @case('cta')
                <section class="bg-slate-900 text-white">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 sm:py-20 text-center">
                        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight">{{ $section->title }}</h2>
                        @if ($section->subtitle)
                            <p class="mt-3 max-w-2xl mx-auto text-slate-300">{{ $section->subtitle }}</p>
                        @endif
                        @if ($section->cta_label && $section->cta_url)
                            <a href="{{ $section->cta_url }}" class="mt-8 inline-flex items-center rounded-md bg-white text-slate-900 px-5 py-2.5 text-sm font-medium hover:bg-slate-100">
                                {{ $section->cta_label }}
                            </a>
                        @endif
                    </div>
                </section>
                @break

            @case('rich_text')
                <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-16">
                    @if ($section->title)
                        <h2 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $section->title }}</h2>
                    @endif
                    @if ($section->body)
                        <div class="prose prose-slate mt-6 max-w-none">{!! $section->body !!}</div>
                    @endif
                </section>
                @break
        @endswitch
    @endforeach

    @if ($announcements->count())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
            <div class="flex items-end justify-between mb-8">
                <h2 class="text-2xl font-semibold text-slate-900">Latest from the club</h2>
                <a href="{{ url('/news') }}" class="text-sm text-slate-600 hover:text-slate-900">All news →</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($announcements as $a)
                    <a href="{{ url('/news/'.$a->slug) }}" class="block rounded-xl border border-slate-200 p-6 hover:border-slate-300 transition">
                        @if ($a->is_pinned)<span class="inline-flex rounded-full bg-amber-100 text-amber-800 text-xs px-2 py-0.5 mb-3">Pinned</span>@endif
                        <p class="text-xs text-slate-500">{{ $a->published_at?->format('d M Y') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $a->title }}</h3>
                        @if ($a->excerpt)<p class="mt-2 text-sm text-slate-600">{{ $a->excerpt }}</p>@endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-site.layout>
