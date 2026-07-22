<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    /**
     * Custom two-column layout (see the view). We render the widgets ourselves
     * in independent flex columns instead of Filament's shared widget grid,
     * so the tall "Recent activity" feed can't stretch the row and push the
     * Matches widget down — which left a large gap under the revenue cards.
     */
    protected string $view = 'filament.admin.pages.dashboard';

    public function getSubheading(): string|Htmlable|null
    {
        $name = trim((string) (auth()->user()?->name ?? ''));
        $first = $name !== '' ? explode(' ', $name)[0] : null;

        return $first ? "Welcome back, {$first}." : 'Welcome back.';
    }

    /**
     * The widgets are placed explicitly in the custom view, so hand Filament an
     * empty set to prevent it from rendering them a second time in the default
     * dashboard grid.
     *
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public function getWidgets(): array
    {
        return [];
    }
}
