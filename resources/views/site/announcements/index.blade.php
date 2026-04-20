<x-site.layout title="News">
    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-16">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">News & announcements</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-900">What's new at PPRC</h1>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 space-y-6">
        @forelse ($announcements as $a)
            <a href="{{ url('/news/'.$a->slug) }}" class="block rounded-xl border border-slate-200 p-6 hover:border-slate-300">
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    @if ($a->is_pinned)<span class="rounded-full bg-amber-100 text-amber-800 px-2 py-0.5">Pinned</span>@endif
                    <span>{{ $a->published_at?->format('d M Y') }}</span>
                </div>
                <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ $a->title }}</h2>
                @if ($a->excerpt)<p class="mt-2 text-slate-600">{{ $a->excerpt }}</p>@endif
            </a>
        @empty
            <p class="text-slate-600">No announcements yet. Check back soon.</p>
        @endforelse

        <div>{{ $announcements->links() }}</div>
    </section>
</x-site.layout>
