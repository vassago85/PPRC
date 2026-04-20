@props([
    'result',
])
@php
    $title   = data_get($result, 'event_title');
    $date    = data_get($result, 'event_date');
    $winner  = data_get($result, 'winner');
    $format  = data_get($result, 'format');
    $url     = data_get($result, 'url') ?? url('/results');
@endphp
<a href="{{ $url }}"
   {{ $attributes->class([
        'group flex items-start gap-4 rounded-2xl bg-white/[0.03] border border-white/10 p-5',
        'transition duration-200 hover:border-brand-400/40 hover:bg-white/[0.05] hover:-translate-y-0.5',
   ]) }}>
    <div class="flex flex-col items-center justify-center rounded-xl bg-brand-500/10 border border-brand-400/20 w-14 h-14 shrink-0">
        <span class="text-[10px] font-bold uppercase tracking-wider text-brand-300 leading-none">{{ optional($date)->format('M') }}</span>
        <span class="text-lg font-bold text-brand-100 leading-tight">{{ optional($date)->format('d') }}</span>
    </div>
    <div class="min-w-0 flex-1">
        <h3 class="font-semibold text-white group-hover:text-brand-200 transition leading-snug line-clamp-2">{{ $title }}</h3>
        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-400">
            @if ($format)<span>{{ $format }}</span>@endif
            @if ($winner)
                <span class="inline-flex items-center gap-1.5">
                    <svg class="size-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-4.5A3.375 3.375 0 0 0 13.125 10.875h-2.25A3.375 3.375 0 0 0 7.5 14.25v4.5m8.25-12 3-3m0 0-3-3m3 3h-15" />
                    </svg>
                    Winner — <span class="text-slate-200 font-medium">{{ $winner }}</span>
                </span>
            @endif
        </div>
    </div>
</a>
