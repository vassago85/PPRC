@props([
    'segments' => [],
    'active' => null,
    'name' => 'segment',
])

{{--
    Tab strip above a table. Replaces the old floating pill group AND the
    "Active filters" bar — one filter system per page.

    Each segment is an associative array:
        [
            'key'   => 'current',        // required
            'label' => 'Current',        // required
            'count' => 103,              // optional
            'url'   => '/admin/members?segment=current', // optional
        ]

    When `url` is set the item renders as a link. Otherwise it dispatches a
    Livewire event ("wire:click=$set('activeSegment','current')" style) — the
    parent component decides how to react. Set `name` to change the model
    key used for the wire dispatch.
--}}

<nav {{ $attributes->class('ui-segments') }} role="tablist">
    @foreach ($segments as $seg)
        @php
            $key = $seg['key'];
            $isActive = $active !== null && (string) $active === (string) $key;
            $count = $seg['count'] ?? null;
        @endphp

        @if (! empty($seg['url']))
            <a
                href="{{ $seg['url'] }}"
                class="ui-segments__item {{ $isActive ? 'is-active' : '' }}"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
            >
                <span>{{ $seg['label'] }}</span>
                @if ($count !== null)
                    <span class="ui-segments__count">{{ number_format((int) $count) }}</span>
                @endif
            </a>
        @else
            <button
                type="button"
                wire:click="$set('{{ $name }}', '{{ $key }}')"
                class="ui-segments__item {{ $isActive ? 'is-active' : '' }}"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
            >
                <span>{{ $seg['label'] }}</span>
                @if ($count !== null)
                    <span class="ui-segments__count">{{ number_format((int) $count) }}</span>
                @endif
            </button>
        @endif
    @endforeach
</nav>
