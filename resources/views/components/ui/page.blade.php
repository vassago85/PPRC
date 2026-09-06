@props([
    'title' => null,
    'subtitle' => null,
    'crumb' => [],
])

{{--
    Shared admin page shell.

    Props:
    - title:    string, required for a page header (renders as h1).
    - subtitle: string, the live count sentence — never decoration.
    - crumb:    array of ['label' => string, 'url' => ?string]. If empty and a
                title is given, one root crumb is inferred from the title.

    Slots:
    - actions:  right-aligned action buttons in the header.
    - default:  the page body (usually one or more <x-ui.panel>s).
--}}

<div {{ $attributes->class('ui-page') }}>
    @if (! empty($crumb))
        <nav class="ui-page__crumb" aria-label="Breadcrumb">
            @foreach ($crumb as $index => $item)
                @if (($item['url'] ?? null) && $index !== array_key_last($crumb))
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span>{{ $item['label'] }}</span>
                @endif

                @if ($index !== array_key_last($crumb))
                    <span class="ui-page__crumb-sep">/</span>
                @endif
            @endforeach
        </nav>
    @endif

    @if ($title || isset($actions))
        <header class="ui-page__head">
            <div>
                @if ($title)
                    <h1 class="ui-page__title">{{ $title }}</h1>
                @endif
                @if ($subtitle)
                    <p class="ui-page__subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="ui-page__actions">
                    {{ $actions }}
                </div>
            @endisset
        </header>
    @endif

    <div class="ui-page__content">
        {{ $slot }}
    </div>
</div>
