<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\EventStatus;
use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MatchDirectorStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('events.view');
    }

    protected function getStats(): array
    {
        $upcoming = Event::query()->upcoming()->count();

        $drafts = Event::query()
            ->where('status', EventStatus::Draft->value)
            ->count();

        $awaitingResults = Event::query()
            ->where('status', EventStatus::Completed->value)
            ->whereNull('results_published_at')
            ->count();

        return [
            Stat::make('Upcoming matches', number_format($upcoming))
                ->description('published and scheduled')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Draft matches', number_format($drafts))
                ->description('not yet published')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($drafts > 0 ? 'warning' : 'gray'),

            Stat::make('Results to publish', number_format($awaitingResults))
                ->description('completed matches without results')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color($awaitingResults > 0 ? 'warning' : 'gray'),
        ];
    }
}
