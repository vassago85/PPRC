<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Admin\AdminDashboardService;
use Filament\Widgets\Widget;

class NeedsAttentionWidget extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    protected string $view = 'filament.admin.widgets.needs-attention';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('members.view')
            || $user?->can('payments.view')
            || $user?->can('events.view')
            || $user?->can('memberships.approve'));
    }

    public function canViewRevenue(): bool
    {
        return (bool) auth()->user()?->can('payments.view');
    }

    public function getItems(): array
    {
        return app(AdminDashboardService::class)->needsAttention();
    }

    /**
     * Revenue MTD/YTD from the payments overview — the only stats not
     * already represented as alert cards in the inbox.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRevenueStats(): array
    {
        $items = app(AdminDashboardService::class)->paymentsOverview();

        return array_values(array_filter(
            $items,
            fn (array $item) => in_array($item['label'], ['Revenue MTD', 'Revenue YTD'], true),
        ));
    }

    public function hasActiveItems(): bool
    {
        return collect($this->getItems())->contains(fn ($item) => $item['value'] > 0);
    }
}
