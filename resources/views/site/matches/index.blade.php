<x-site.layout
    title="Matches"
    description="PPRC match calendar — PRS (Centerfire) and PR22 matches hosted by Pretoria Precision Rifle Club."
>
    <x-site.section padding="lg">
        <x-site.eyebrow>Matches</x-site.eyebrow>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">Club matches &amp; events</h1>
        <p class="mt-5 max-w-2xl text-slate-300">
            PRS (Centerfire) and PR22 matches hosted by PPRC. Pick an upcoming match below to see the details and enter online. Members are notified by email when registrations open.
        </p>
        <p class="mt-3 max-w-2xl text-sm text-slate-500">
            Need help with an entry you've already made? <a href="{{ url('/contact') }}" class="font-medium text-slate-300 underline underline-offset-2 hover:text-white">Contact the club</a>.
        </p>
    </x-site.section>

    <x-site.section tone="muted" padding="default">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Upcoming</h2>
            @if ($upcoming->isNotEmpty())
                <span class="text-sm text-slate-500">{{ $upcoming->count() }} scheduled</span>
            @endif
        </div>

        @if ($upcoming->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($upcoming as $match)
                    <x-site.match-card :match="$match" />
                @endforeach
            </div>
        @else
            <x-site.card padding="lg" class="text-center border-dashed">
                <p class="text-slate-300">No upcoming matches are listed yet.</p>
                <p class="mt-2 text-sm text-slate-500">Check back soon. Members get match updates by email as soon as the calendar is published.</p>
                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <x-site.button :href="url('/contact')">Contact the club</x-site.button>
                    <x-site.button :href="url('/membership')" variant="secondary">About PPRC membership</x-site.button>
                </div>
            </x-site.card>
        @endif
    </x-site.section>

    @if ($past->isNotEmpty())
        <x-site.section padding="default">
            <div class="mb-8">
                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Past matches</h2>
                <p class="mt-2 text-slate-400">A running record of PPRC-hosted matches.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($past as $match)
                    <x-site.match-card :match="$match" />
                @endforeach
            </div>
        </x-site.section>
    @endif
</x-site.layout>
