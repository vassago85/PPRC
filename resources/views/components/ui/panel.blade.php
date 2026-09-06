@props([
    'title' => null,
    'flush' => false,
])

{{--
    Bordered surface. The only shadow user; nested panels drop the shadow
    automatically. Use `flush` when the body is a table or a fully-styled
    section that supplies its own padding.

    Slots:
    - head:    optional heading row (title + optional actions).
    - default: body.
    - foot:    optional footer strip (totals, help text, pagination).
--}}

<section {{ $attributes->class(['ui-panel', 'ui-panel--flush' => $flush]) }}>
    @if ($title || isset($head))
        <header class="ui-panel__head">
            @if ($title)
                <h2 class="ui-panel__title">{{ $title }}</h2>
            @endif
            @isset($head)
                <div class="ui-panel__head-slot">{{ $head }}</div>
            @endisset
        </header>
    @endif

    <div class="ui-panel__body">
        {{ $slot }}
    </div>

    @isset($foot)
        <footer class="ui-panel__foot">{{ $foot }}</footer>
    @endisset
</section>
