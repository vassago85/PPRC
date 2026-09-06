@props([
    'columns' => [],
    'selectable' => false,
])

{{--
    Sticky-header, 42-px row, horizontally scrolling table.

    Props:
    - columns:    array of ['key' => 'name', 'label' => 'Name', 'align' => 'left|right', 'width' => '4rem'?]
                  When empty the caller renders their own <thead>.
    - selectable: when true, prepends a checkbox column. The parent is
                  responsible for wire:model'ing the selection state onto the
                  input via `x-ui.table.row` (or their own <tr>).

    Slots:
    - head:    override the whole <thead>.
    - default: the tbody rows.
--}}

<div class="ui-table-wrap">
    <table {{ $attributes->class('ui-table') }}>
        @isset($head)
            <thead>{{ $head }}</thead>
        @elseif (! empty($columns))
            <thead>
                <tr>
                    @if ($selectable)
                        <th class="ui-cell--check" scope="col"></th>
                    @endif
                    @foreach ($columns as $col)
                        <th
                            scope="col"
                            @if (! empty($col['width'])) style="width: {{ $col['width'] }}" @endif
                            class="{{ ($col['align'] ?? 'left') === 'right' ? 'ui-cell--right' : '' }}"
                        >{{ $col['label'] ?? '' }}</th>
                    @endforeach
                    <th class="ui-cell--actions" scope="col"></th>
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
