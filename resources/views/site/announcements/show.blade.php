<x-site.layout :title="$announcement->title" :description="$announcement->excerpt">
    <article class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-16">
        <p class="text-xs text-slate-500">{{ $announcement->published_at?->format('d M Y') }}</p>
        <h1 class="mt-2 text-4xl font-semibold tracking-tight text-slate-900">{{ $announcement->title }}</h1>
        @if ($announcement->excerpt)<p class="mt-4 text-lg text-slate-600">{{ $announcement->excerpt }}</p>@endif

        <div class="prose prose-slate mt-8 max-w-none">{!! $announcement->body !!}</div>

        <div class="mt-12">
            <a href="{{ url('/news') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to news</a>
        </div>
    </article>
</x-site.layout>
