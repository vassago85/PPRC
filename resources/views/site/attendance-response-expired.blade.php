<x-site.layout title="Match has already run">
    <x-site.section padding="default">
        <div class="mx-auto max-w-xl">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center sm:p-10">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-500/15 text-slate-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">That match has already run</h1>
                <p class="mt-3 text-slate-300">
                    <strong>{{ $event?->title }}</strong> was on {{ $event?->start_date?->format('d F Y') }}, so there's nothing left to confirm.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ url('/matches') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-brand-400">
                        Browse upcoming matches
                    </a>
                </div>
            </div>
        </div>
    </x-site.section>
</x-site.layout>
