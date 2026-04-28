<x-site.layout title="Gallery">
    <x-site.section padding="lg">
        <x-site.eyebrow>Gallery</x-site.eyebrow>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">Event photos</h1>
        <p class="mt-5 max-w-2xl text-slate-300">
            Browse photo albums from our matches and events.
        </p>
    </x-site.section>

    <x-site.section tone="muted" padding="default">
        @if ($events->isEmpty())
            <x-site.card padding="lg" class="text-center border-dashed">
                <p class="text-slate-300">No albums to show yet.</p>
            </x-site.card>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    @php
                        $thumb = $event->galleryPhotos->first();
                    @endphp
                    <x-site.card padding="none" hoverable :href="route('gallery.show', $event)" class="group">
                        <div class="aspect-[4/3] overflow-hidden rounded-t-xl">
                            @if ($thumb)
                                <img
                                    src="{{ $thumb->publicUrl() }}"
                                    alt="{{ $event->title }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-slate-800">
                                    <svg class="h-12 w-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="p-5">
                            <h3 class="text-lg font-semibold text-white">{{ $event->title }}</h3>
                            <div class="mt-2 flex items-center gap-3 text-sm text-slate-400">
                                <time datetime="{{ $event->start_date->toDateString() }}">
                                    {{ $event->start_date->format('j M Y') }}
                                </time>
                                <span class="text-slate-600">&middot;</span>
                                <span>{{ $event->galleryPhotos->count() }} {{ Str::plural('photo', $event->galleryPhotos->count()) }}</span>
                            </div>
                        </div>
                    </x-site.card>
                @endforeach
            </div>
        @endif
    </x-site.section>
</x-site.layout>
