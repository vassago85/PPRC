<x-site.layout title="Matches">
    <x-site.section padding="lg">
        <x-site.eyebrow>Matches</x-site.eyebrow>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">Club matches and SAPRF events</h1>
        <p class="mt-5 max-w-2xl text-slate-300">
            The full match calendar will be published here. Members will be notified by email when registrations open.
        </p>
    </x-site.section>

    <x-site.section tone="muted" padding="default">
        <x-site.card padding="lg" class="text-center border-dashed">
            <p class="text-slate-300">No upcoming matches are listed yet.</p>
            <p class="mt-2 text-sm text-slate-500">Check back soon, or join the club to get match updates by email.</p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <x-site.button :href="url('/register')">Join PPRC</x-site.button>
                <x-site.button :href="url('/contact')" variant="secondary">Contact us</x-site.button>
            </div>
        </x-site.card>
    </x-site.section>
</x-site.layout>
