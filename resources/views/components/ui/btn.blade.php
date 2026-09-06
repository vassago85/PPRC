@props([
    'variant' => 'default', // default | primary | ghost | danger-quiet
    'size' => 'md',          // md | sm
    'as' => null,            // null (button), 'a' (anchor), 'submit'
    'href' => null,
    'icon' => null,
])

@php
    $classes = ['ui-btn'];
    $classes[] = match ($variant) {
        'primary' => 'ui-btn--primary',
        'ghost' => 'ui-btn--ghost',
        'danger-quiet' => 'ui-btn--danger-quiet',
        default => '',
    };
    if ($size === 'sm') {
        $classes[] = 'ui-btn--sm';
    }
    $classes = array_filter($classes);
    $tag = $href ? 'a' : ($as === 'submit' ? 'button' : 'button');
    $type = $as === 'submit' ? 'submit' : 'button';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-dynamic-component :component="$icon" class="w-4 h-4" aria-hidden="true" />
        @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-dynamic-component :component="$icon" class="w-4 h-4" aria-hidden="true" />
        @endif
        <span>{{ $slot }}</span>
    </button>
@endif
