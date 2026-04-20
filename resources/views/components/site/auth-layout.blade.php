@props([
    'title' => 'Sign in',
    'eyebrow' => null,
    'heading' => null,
    'subheading' => null,
])
<x-site.layout :title="$title">
    <section class="relative isolate overflow-hidden bg-slate-950">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,0.06),transparent_60%)]"></div>
        </div>

        <x-site.container width="tight">
            <div class="py-20 sm:py-28 max-w-md mx-auto">
                @if ($eyebrow)
                    <x-site.eyebrow>{{ $eyebrow }}</x-site.eyebrow>
                @endif
                @if ($heading)
                    <h1 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">{{ $heading }}</h1>
                @endif
                @if ($subheading)
                    <p class="mt-3 text-slate-400">{{ $subheading }}</p>
                @endif

                @if (session('status'))
                    <div class="mt-6 rounded-md border border-emerald-400/30 bg-emerald-400/5 px-4 py-3 text-sm text-emerald-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-md border border-rose-400/30 bg-rose-500/5 px-4 py-3 text-sm text-rose-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-8">
                    {{ $slot }}
                </div>

                @isset($footer)
                    <div class="mt-8 text-sm text-slate-400 text-center">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </x-site.container>
    </section>
</x-site.layout>
