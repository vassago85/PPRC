<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Match-director secondary metrics.
 *
 * "Upcoming matches" sits in PrimaryKpiWidget now, so this widget covers
 * the editorial side: drafts not yet published and finished matches still
 * waiting on results.
 */
class MatchDirectorStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('events.view');
    }

    protected function getStats(): array
    {
        $drafts = Event::query()
            ->where('status', EventStatus::Draft->value)
            ->count();

        $awaitingResults = Event::query()
            ->where('status', EventStatus::Completed->value)
            ->whereNull('results_published_at')
            ->count();

        $registrationsThisMonth = Event::query()
            ->where('start_date', '>=', now()->startOfMonth())
            ->where('start_date', '<=', now()->endOfMonth())
            ->withCount('registrations')
            ->get()
            ->sum('registrations_count');

        return [
            Stat::make('Draft matches', number_format($drafts))
                ->description($drafts > 0 ? 'Not yet published to the site' : 'Nothing in draft')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($drafts > 0 ? 'warning' : 'gray')
                ->url(EventResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'draft']]])),

            Stat::make('Results to publish', number_format($awaitingResults))
                ->description($awaitingResults > 0 ? 'Completed matches without results' : 'All caught up')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color($awaitingResults > 0 ? 'warning' : 'gray')
                ->url(EventResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'completed']]])),

            Stat::make('Registrations this month', number_format($registrationsThisMonth))
                ->description('Across matches starting in '.now()->format('F'))
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info')
                ->url(EventResource::getUrl('index')),
        ];
    }
}
