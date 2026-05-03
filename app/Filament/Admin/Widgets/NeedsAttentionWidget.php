<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Admin\AdminDashboardService;
use Filament\Widgets\Widget;

class NeedsAttentionWidget extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.needs-attention';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('members.view')
            || $user?->can('payments.view')
            || $user?->can('events.view')
            || $user?->can('memberships.approve'));
    }

    public function getItems(): array
    {
        return app(AdminDashboardService::class)->needsAttention();
    }

    public function hasActiveItems(): bool
    {
        return collect($this->getItems())->contains(fn ($item) => $item['value'] > 0);
    }
}
