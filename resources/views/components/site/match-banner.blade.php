@props([
    'src',
    'alt' => '',
    /** card | featured | sidebar */
    'size' => 'card',
])

@php
    $frameClass = match ($size) {
        'featured' => 'overflow-hidden rounded-2xl border border-white/10 bg-slate-900/90',
        'sidebar' => 'overflow-hidden rounded-2xl border border-white/10 bg-slate-900/90 shadow-lg shadow-black/20',
        default => 'overflow-hidden rounded-t-2xl bg-slate-900/90',
    };

    $imgClass = match ($size) {
        'featured' => 'mx-auto h-auto w-full max-h-[32rem] object-contain',
        'sidebar' => 'mx-auto h-auto w-full object-contain',
        default => 'mx-auto h-full w-full max-h-64 object-contain sm:max-h-72',
    };

    $wrapperClass = match ($size) {
        'featured' => 'flex min-h-[16rem] items-center justify-center p-3 sm:p-4',
        'sidebar' => 'flex items-center justify-center p-3',
        default => 'flex aspect-[4/5] max-h-72 items-center justify-center',
    };
@endphp

<div {{ $attributes->class([$frameClass]) }}>
    <div @class([$wrapperClass])>
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            @class([$imgClass])
            loading="lazy"
        />
    </div>
</div>
