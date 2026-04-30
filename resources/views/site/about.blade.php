<x-site.layout
    title="About"
    description="Pretoria Precision Rifle Club — a home for PRS and PR22 shooters in Gauteng. Meet the committee and learn about the club."
>
    <x-site.section padding="lg">
        <x-site.eyebrow>About PPRC</x-site.eyebrow>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">A home for PRS shooters.</h1>
        <p class="mt-5 max-w-2xl text-lg text-slate-300">
            Started in 2023 by precision rifle shooters, for precision rifle shooters.
        </p>
    </x-site.section>

    <x-site.section tone="muted" padding="default">
        <div class="grid gap-10 lg:grid-cols-2">
            <div class="space-y-5 text-slate-300">
                <h2 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Who we are</h2>
                <p>
                    Pretoria Precision Rifle Club (PPRC) is a Precision Rifle club based in Pretoria, Gauteng.
                    We host PRS (centerfire) and PR22 rimfire matches throughout the year and run the club as a
                    registered SAPRF affiliate.
                </p>
                <p>
                    PPRC was founded in 2023 by a group of local PRS shooters who wanted a dedicated home for the
                    sport — one run by shooters, for shooters.
                </p>
            </div>
            <div class="space-y-5 text-slate-300">
                <h2 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Our vision</h2>
                <p>
                    Build a sustainable, family-style club environment where every precision rifle shooter — new or
                    seasoned — can <span class="text-white font-medium">grow, belong and compete</span>.
                </p>
                <p>
                    Practically that means well-run matches, clear rules, consistent scoring, and a culture where
                    more experienced shooters bring the next group of shooters through.
                </p>
            </div>
        </div>
    </x-site.section>

    <x-site.section padding="default" id="committee">
        <div class="mb-10 flex items-end justify-between gap-4">
            <div>
                <x-site.eyebrow>Committee</x-site.eyebrow>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">The PPRC Executive</h2>
                <p class="mt-3 max-w-2xl text-slate-400">
                    The committee is elected by members each year. They run the club's day-to-day operations,
                    finances, matches, and member admin.
                </p>
            </div>
            @if ($committee->isNotEmpty())
                <span class="hidden sm:block text-sm text-slate-500">{{ $committee->count() }} members</span>
            @endif
        </div>

        @if ($committee->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($committee as $m)
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                        <div class="flex items-center gap-4">
                            @if ($m->photo_path)
                                <img
                                    src="{{ \App\Support\MediaDisk::url($m->photo_path) }}"
                                    alt="{{ $m->full_name }}"
                                    class="h-16 w-16 rounded-full object-cover bg-white/5"
                                />
                            @else
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-500/10 text-lg font-semibold text-brand-200 ring-1 ring-inset ring-brand-400/20">
                                    {{ strtoupper(mb_substr($m->full_name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold text-white">{{ $m->full_name }}</p>
                                <p class="text-sm text-brand-200">{{ $m->position }}</p>
                            </div>
                        </div>
                        @if ($m->bio)
                            <p class="mt-4 text-sm text-slate-400">{{ $m->bio }}</p>
                        @endif
                        @if ($m->email)
                            <p class="mt-4 text-sm">
                                <a href="mailto:{{ $m->email }}" class="text-slate-400 hover:text-white">
                                    {{ $m->email }}
                                </a>
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <x-site.card padding="lg" class="text-center border-dashed">
                <p class="text-slate-300">The committee roster will be published here after each AGM.</p>
            </x-site.card>
        @endif
    </x-site.section>
</x-site.layout>
