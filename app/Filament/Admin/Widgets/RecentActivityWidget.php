<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Admin\AdminDashboardService;
use Filament\Widgets\Widget;

class RecentActivityWidget extends Widget
{
    protected static ?int $sort = 4;

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
