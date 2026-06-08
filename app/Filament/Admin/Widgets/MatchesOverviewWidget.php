<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Admin\AdminDashboardService;
use Filament\Widgets\Widget;

class MatchesOverviewWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    protected string $view = 'filament.admin.widgets.matches-overview';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('events.view');
    }

    public function getData(): array
    {
        return app(AdminDashboardService::class)->matchesOverview();
    }
}
