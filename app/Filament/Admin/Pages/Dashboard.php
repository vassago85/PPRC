<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getSubheading(): string|Htmlable|null
    {
        $name = trim((string) (auth()->user()?->name ?? ''));
        $first = $name !== '' ? explode(' ', $name)[0] : null;

        return $first ? "Welcome back, {$first}." : 'Welcome back.';
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'lg' => 1,
        ];
    }
}
