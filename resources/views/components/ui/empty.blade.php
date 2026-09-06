@props([
    'icon' => 'heroicon-o-inbox',
    'title' => null,
    'description' => null,
])

{{--
    Empty state. Never ✕. One line of what would be here, and the action that
    would create it. Slot `action` for the CTA button.
--}}

<div {{ $attributes->class('ui-empty') }}>
    @if ($icon)
        <x-dynamic-component :component="$icon" class="ui-empty__icon" aria-hidden="true" />
    @endif

    @if ($title)
        <p class="ui-empty__title">{{ $title }}</p>
    @endif

    @if ($description)
        <p class="ui-empty__desc">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="ui-empty__action">{{ $action }}</div>
    @endisset

    {{ $slot }}
</div>
