@props(['width' => 'default'])
@php
    $max = match ($width) {
        'tight' => 'max-w-4xl',
        'prose' => 'max-w-3xl',
        default => 'max-w-7xl',
    };
@endphp
<div {{ $attributes->class(['mx-auto px-4 sm:px-6 lg:px-8', $max]) }}>
    {{ $slot }}
</div>
