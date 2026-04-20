@props(['tone' => 'dark'])
@php
    $color = match ($tone) {
        'light' => 'text-slate-500',
        default => 'text-slate-400',
    };
@endphp
<p {{ $attributes->class(['text-xs font-semibold uppercase tracking-[0.2em]', $color]) }}>
    {{ $slot }}
</p>
