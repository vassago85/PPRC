@props([
    'match',
    'compact' => false,
])
@php
    // Accept either an Eloquent model or an array. Normalise to dot-access.
    $title    = data_get($match, 'title');
    $url      = data_get($match, 'url') ?? url('/matches');
    $location = data_get($match, 'location');
    $startsAt = data_get($match, 'starts_at');
    $featured = (bool) data_get($match, 'is_featured', false);
    $format   = data_get($match, 'format'); // optional: "PRS (Centerfire)", "PR22", etc.
@endphp
<a href="{{ $url }}"
   {{ $attributes->class([
        'group block rounded-2xl bg-white/[0.03] border border-white/10 transition duration-200',
        'hover:border-brand-400/40 hover:bg-white/[0.05] hover:-translate-y-0.5',
        'p-4' => $compact,
        'p-5' => ! $compact,
   ]) }}>
    <div class="flex items-start gap-4">
        {{-- Date badge --}}
        <div @class([
            'flex flex-col items-center justify-center rounded-xl bg-brand-500/10 border border-brand-400/20 shrink-0',
            'w-11 h-11' => $compact,
            'w-14 h-14' => ! $compact,
        ])>
            <span @class([
                'font-bold uppercase tracking-wider text-brand-300 leading-none',
                'text-[8px]' => $compact,
                'text-[10px]' => ! $compact,
            ])>{{ optional($startsAt)->format('M') }}</span>
            <span @class([
                'font-bold text-brand-100 leading-tight',
                'text-[13px]' => $compact,
                'text-lg' => ! $compact,
            ])>{{ optional($startsAt)->format('d') }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <h3 @class([
                    'font-semibold text-white group-hover:text-brand-200 transition leading-snug line-clamp-2',
                    'text-[13px]' => $compact,
                    'text-base' => ! $compact,
                ])>{{ $title }}</h3>

                @if ($featured && ! $compact)
                    <span class="rounded-md bg-amber-400/10 text-amber-300 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ring-1 ring-inset ring-amber-400/30 shrink-0">Featured</span>
                @endif
            </div>

            @if ($format || $location)
                <div @class([
                    'mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-slate-400',
                    'text-[11px]' => $compact,
                    'text-sm' => ! $compact,
                ])>
                    @if ($format)
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="size-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                            <span class="truncate">{{ $format }}</span>
                        </span>
                    @endif
                    @if ($location)
                        <span class="inline-flex items-center gap-1.5 min-w-0">
                            <svg class="size-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 0 1 15 0Z" />
                            </svg>
                            <span class="truncate">{{ $location }}</span>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</a>
