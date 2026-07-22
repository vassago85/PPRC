<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Admin\AdminDashboardService;
use Filament\Widgets\Widget;

class MatchesOverviewWidget extends Widget
{
    // Placement is controlled by the Dashboard page's custom view; the widget
    // just fills whatever container it's rendered into.
    protected int|string|array $columnSpan = 'full';

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
