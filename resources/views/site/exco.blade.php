<x-site.layout title="Committee">
    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-16">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Committee</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-900">The PPRC Executive Committee</h1>
            <p class="mt-4 max-w-2xl text-slate-600">The committee is elected by members each year. They run the club's day-to-day operations, finances, events, and member admin.</p>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($members as $m)
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <div class="flex items-center gap-4">
                        @if ($m->photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($m->photo_path) }}" alt="{{ $m->full_name }}" class="h-16 w-16 rounded-full object-cover bg-slate-200" />
                        @else
                            <div class="h-16 w-16 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-medium">
                                {{ strtoupper(mb_substr($m->full_name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-slate-900">{{ $m->full_name }}</p>
                            <p class="text-sm text-slate-600">{{ $m->position }}</p>
                        </div>
                    </div>
                    @if ($m->bio)<p class="mt-4 text-sm text-slate-600">{{ $m->bio }}</p>@endif
                    @if ($m->email)<p class="mt-3 text-sm"><a href="mailto:{{ $m->email }}" class="text-slate-600 hover:text-slate-900">{{ $m->email }}</a></p>@endif
                </div>
            @empty
                <p class="text-slate-600">Committee members will be listed here soon.</p>
            @endforelse
        </div>
    </section>
</x-site.layout>
