<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

/**
 * PPRC admin dashboard.
 *
 * Subclass purely for the visual hierarchy: a 4-column widget grid (so the
 * top KPI row aligns to four cards) and a friendly subheading under the
 * "Dashboard" title — the rest of Filament's behaviour is inherited.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getSubheading(): string|Htmlable|null
    {
        $name = trim((string) (auth()->user()?->name ?? ''));
        $first = $name !== '' ? explode(' ', $name)[0] : null;

        $hello = $first ? "Welcome back, {$first}." : 'Welcome back.';

        return "{$hello} Here's what needs your attention today.";
    }

    public function getColumns(): int|array
    {
        // Four-up grid lines up the top KPI cards (Revenue / Pending /
        // Upcoming matches / Active members) on desktop, gracefully
        // collapsing to two on tablets and one on phones.
        return [
            'default' => 1,
            'sm' => 2,
            'lg' => 4,
        ];
    }
}
