<x-site.layout
    title="Results"
    description="Recent PPRC match results — PRS (Centerfire) and PR22."
>
    <x-site.section padding="lg">
        <x-site.eyebrow>Results</x-site.eyebrow>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">Match results</h1>
        <p class="mt-5 max-w-2xl text-slate-300">
            Placings and scorecards from PPRC matches. Click through to see full results per event.
        </p>
    </x-site.section>

    <x-site.section tone="muted" padding="default">
        @if ($cards->isEmpty())
            <x-site.card padding="lg" class="text-center border-dashed">
                <p class="text-slate-300">No results have been published yet.</p>
                <p class="mt-2 text-sm text-slate-500">Results appear here once a match is complete and published.</p>
            </x-site.card>
        @else
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ($cards as $result)
                    <x-site.result-card :result="$result" />
                @endforeach
            </div>
        @endif
    </x-site.section>
</x-site.layout>
