@props([
    'label' => 'Row actions',
])

{{--
    Alpine-powered ⋯ dropdown. Renders one trigger button and a menu panel.
    Slot: the menu items — usually one <a class="ui-menu__item"> per action.
    Wrap destructive items in `ui-menu__item--danger`.
--}}

<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    class="relative inline-flex"
>
    <button
        type="button"
        class="ui-dots"
        aria-haspopup="menu"
        :aria-expanded="open ? 'true' : 'false'"
        aria-label="{{ $label }}"
        @click="open = ! open"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
             viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="5" cy="12" r="1"></circle>
            <circle cx="12" cy="12" r="1"></circle>
            <circle cx="19" cy="12" r="1"></circle>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        role="menu"
        class="ui-menu absolute right-0 top-full mt-1"
    >
        {{ $slot }}
    </div>
</div>
