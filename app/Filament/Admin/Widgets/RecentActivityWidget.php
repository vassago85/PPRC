<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Admin\AdminDashboardService;
use Filament\Widgets\Widget;

class RecentActivityWidget extends Widget
{
    // Placement is controlled by the Dashboard page's custom view; the widget
    // just fills whatever container it's rendered into.
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.recent-activity';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('members.view') || $user?->can('payments.view'));
    }

    public function getItems(): array
    {
        return app(AdminDashboardService::class)->recentActivity();
    }
}
