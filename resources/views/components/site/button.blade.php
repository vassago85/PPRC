@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
    'size' => 'md',
    'fullWidth' => false,
])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-md font-medium tracking-tight transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';

    $sizes = match ($size) {
        'sm' => 'px-3.5 py-2 text-xs',
        'lg' => 'px-6 py-3.5 text-base',
        default => 'px-5 py-2.5 text-sm',
    };

    $variants = match ($variant) {
        'primary'   => 'bg-white text-slate-950 hover:bg-slate-200 active:bg-slate-300',
        'secondary' => 'border border-white/25 text-white hover:bg-white/10',
        'ghost'     => 'text-white hover:text-slate-300',
        'on-light-primary'   => 'bg-slate-950 text-white hover:bg-slate-800',
        'on-light-secondary' => 'border border-slate-300 text-slate-900 hover:bg-slate-100',
        default     => 'bg-white text-slate-950 hover:bg-slate-200',
    };

    $width = $fullWidth ? 'w-full' : '';

    $classes = trim("$base $sizes $variants $width");
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </button>
@endif
