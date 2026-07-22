{{--
    Two-column dashboard.

    Left column stacks the actionable widgets (Action inbox → revenue → Matches)
    as one continuous flow. The right column holds the tall "Recent activity"
    feed on its own. `lg:items-start` keeps each column top-aligned, so a short
    column is never stretched to match the taller one — which is what previously
    opened a gap between the revenue cards and the Matches widget.

    On smaller screens the grid collapses to a single column and everything
    stacks in order: Action inbox, Matches, then Recent activity.
--}}
<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        {{-- Left: everything actionable, tightly stacked --}}
        <div class="flex flex-col gap-6 lg:col-span-2">
            @if (\App\Filament\Admin\Widgets\NeedsAttentionWidget::canView())
                @livewire(\App\Filament\Admin\Widgets\NeedsAttentionWidget::class)
            @endif

            @if (\App\Filament\Admin\Widgets\MatchesOverviewWidget::canView())
                @livewire(\App\Filament\Admin\Widgets\MatchesOverviewWidget::class)
            @endif
        </div>

        {{-- Right: the tall activity feed, independent height --}}
        <div class="lg:col-span-1">
            @if (\App\Filament\Admin\Widgets\RecentActivityWidget::canView())
                @livewire(\App\Filament\Admin\Widgets\RecentActivityWidget::class)
            @endif
        </div>
    </div>
</x-filament-panels::page>
