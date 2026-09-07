<?php

namespace App\Filament\Admin\Pages;

use App\Services\Admin\AdminDashboardService;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Overview';

    /**
     * The rebuilt Dashboard renders the whole prototype in one Blade view.
     * The default Filament page-header row is hidden by getHeading() /
     * getSubheading() returning null; the view paints its own `.pp-phead`
     * so the title lives inside the canvas, not above it.
     */
    protected string $view = 'filament.admin.pages.dashboard';

    public function getHeading(): string|Htmlable
    {
        // Emptying the heading collapses the Filament page-header row and
        // gives the custom `.pp-phead` its own top margin without a big
        // duplicated title stack.
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * Live overview subtitle rendered inside the custom page head. Falls
     * back to a plain weekday label when there is no upcoming match.
     */
    public function overviewSubtitle(): HtmlString
    {
        return new HtmlString(
            app(AdminDashboardService::class)->overviewSubtitle()
        );
    }

    /**
     * No auto widgets — the prototype uses a single custom canvas.
     *
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public function getWidgets(): array
    {
        return [];
    }

    /** No header widgets either. */
    public function getHeaderWidgets(): array
    {
        return [];
    }

    /** No footer widgets either. */
    public function getFooterWidgets(): array
    {
        return [];
    }
}
