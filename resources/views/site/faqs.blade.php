<x-site.layout title="FAQs">
    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-16">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Help</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-900">Frequently asked questions</h1>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 space-y-12">
        @forelse ($faqs as $category => $items)
            <div>
                <h2 class="text-xl font-semibold text-slate-900 capitalize">{{ $category }}</h2>
                <dl class="mt-4 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white" x-data>
                    @foreach ($items as $faq)
                        <div x-data="{ open: false }" class="p-6">
                            <dt>
                                <button @click="open = !open" class="flex w-full items-start justify-between text-left">
                                    <span class="text-base font-medium text-slate-900">{{ $faq->question }}</span>
                                    <span class="ml-4 text-slate-400" x-text="open ? '−' : '+'"></span>
                                </button>
                            </dt>
                            <dd x-show="open" x-cloak class="mt-3 text-sm text-slate-600 prose prose-slate max-w-none">
                                {!! nl2br(e($faq->answer)) !!}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @empty
            <p class="text-slate-600">FAQs will appear here soon.</p>
        @endforelse
    </section>
</x-site.layout>
