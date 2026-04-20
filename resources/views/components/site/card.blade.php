@props([
    'tone' => 'dark',
    'padding' => 'md',
    'hoverable' => false,
    'href' => null,
])
@php
    $p = match ($padding) {
        'sm' => 'p-5',
        'lg' => 'p-8',
        'none' => '',
        default => 'p-6',
    };

    $toneClasses = match ($tone) {
        'dark'    => 'bg-white/[0.03] border border-white/10 text-white',
        'raised'  => 'bg-slate-900 border border-white/10 text-white',
        'light'   => 'bg-white border border-slate-200 text-slate-900 shadow-sm',
        default   => 'bg-white/[0.03] border border-white/10 text-white',
    };

    $hover = $hoverable
        ? ($tone === 'light' ? 'hover:border-slate-300 transition' : 'hover:border-white/25 hover:bg-white/[0.05] transition')
        : '';

    $classes = trim("rounded-xl {$p} {$toneClasses} {$hover}");
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes, 'block']) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </div>
@endif
