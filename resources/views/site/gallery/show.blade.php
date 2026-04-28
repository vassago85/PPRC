<x-site.layout :title="$event->title . ' — Gallery'">
    <x-site.section padding="lg">
        <x-site.eyebrow>
            <a href="{{ route('gallery') }}" class="hover:text-white transition">Gallery</a>
            <span class="mx-2 text-slate-600">/</span>
            {{ $event->title }}
        </x-site.eyebrow>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">{{ $event->title }}</h1>
        <p class="mt-5 text-slate-300">
            <time datetime="{{ $event->start_date->toDateString() }}">{{ $event->start_date->format('j F Y') }}</time>
            <span class="mx-2 text-slate-600">&middot;</span>
            {{ $event->galleryPhotos->count() }} {{ Str::plural('photo', $event->galleryPhotos->count()) }}
        </p>
    </x-site.section>

    <x-site.section tone="muted" padding="default">
        @if ($event->galleryPhotos->isEmpty())
            <x-site.card padding="lg" class="text-center border-dashed">
                <p class="text-slate-300">No photos in this album yet.</p>
            </x-site.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($event->galleryPhotos as $photo)
                    <figure class="group overflow-hidden rounded-xl bg-white/[0.03] border border-white/10">
                        <div class="aspect-[4/3] overflow-hidden">
                            <img
                                src="{{ $photo->publicUrl() }}"
                                alt="{{ $photo->caption ?? $event->title }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                loading="lazy"
                            >
                        </div>
                        @if ($photo->caption)
                            <figcaption class="px-4 py-3 text-sm text-slate-300">
                                {{ $photo->caption }}
                            </figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        @endif
    </x-site.section>
</x-site.layout>
