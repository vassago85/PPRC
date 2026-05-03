<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Admin\AdminDashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MembershipOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Membership';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('members.view') || $user?->can('memberships.approve'));
    }

    protected function getStats(): array
    {
        $items = app(AdminDashboardService::class)->membershipOverview();

        return array_map(fn (array $item) => Stat::make($item['label'], number_format($item['value']))
            ->description($item['description'])
            ->descriptionIcon($item['icon'])
            ->color($item['color'])
            ->url($item['url']),
            $items,
        );
    }
}
