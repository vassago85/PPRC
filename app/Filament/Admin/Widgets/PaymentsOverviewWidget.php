<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Admin\AdminDashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Payments';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('payments.view');
    }

    protected function getStats(): array
    {
        $items = app(AdminDashboardService::class)->paymentsOverview();

        return array_map(fn (array $item) => Stat::make($item['label'], $item['formatted'] ?? number_format($item['value']))
            ->description($item['description'])
            ->descriptionIcon($item['icon'])
            ->color($item['color'])
            ->url($item['url']),
            $items,
        );
    }
}
