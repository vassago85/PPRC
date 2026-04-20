<x-site.layout :title="$page->meta_title ?? $page->title" :description="$page->meta_description">
    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-16">
            <h1 class="text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900">{{ $page->title }}</h1>
            @if ($page->subtitle)
                <p class="mt-4 text-lg text-slate-600 max-w-2xl">{{ $page->subtitle }}</p>
            @endif
        </div>
    </section>

    <article class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12 prose prose-slate">
        {!! $page->body !!}
    </article>
</x-site.layout>
