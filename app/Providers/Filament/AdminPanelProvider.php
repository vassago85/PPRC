<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(isSimple: false)
            ->brandName('PPRC Admin')
            ->colors([
                'primary' => Color::Slate,
            ])
            // 236px dark rail — matches the prototype exactly. The custom
            // theme paints the rail dark and pins the user block at the
            // bottom via the SIDEBAR_NAV_END render hook below.
            ->sidebarWidth('236px')
            ->collapsedSidebarWidth('4.5rem')
            ->sidebarCollapsibleOnDesktop()
            // Let the content area breathe full-width across modern monitors.
            ->maxContentWidth(Width::Full)
            // Custom CSS layer for premium SaaS chrome (typography, shadows,
            // dark active nav item, table polish, etc.).
            ->viteTheme('resources/css/filament/admin/theme.css')
            // Five job-based groups. Every resource sets its $navigationGroup
            // to exactly one of these — items are grouped by what an admin
            // sits down to do, not by which model they belong to.
            ->navigationGroups([
                NavigationGroup::make('Matches'),
                NavigationGroup::make('Members'),
                NavigationGroup::make('Money'),
                NavigationGroup::make('Club'),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Auto-discovery of Filament stat/table widgets is intentionally
            // off: the rebuilt Dashboard renders its own queue / money strip
            // / activity feed via the custom Blade view. Leaving discovery on
            // would double up the same numbers underneath.
            ->widgets([])
            // User block pinned to the bottom of the dark rail (avatar,
            // name + role). Uses `SIDEBAR_NAV_END` so it sits below the last
            // nav group but inside the sidebar column.
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn (): View => view('filament.admin.partials.sidebar-footer'),
            )
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Account & password')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): string => route('portal.account.password')),
                MenuItem::make()
                    ->label('Member portal')
                    ->icon('heroicon-o-home')
                    ->url(fn (): string => url('/portal')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
