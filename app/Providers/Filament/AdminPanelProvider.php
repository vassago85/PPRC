<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\ActionRequiredWidget;
use App\Filament\Admin\Widgets\MatchDirectorStatsWidget;
use App\Filament\Admin\Widgets\MembershipSecretaryStatsWidget;
use App\Filament\Admin\Widgets\PrimaryKpiWidget;
use App\Filament\Admin\Widgets\RecentMembershipPaymentsWidget;
use App\Filament\Admin\Widgets\SecretaryStatsWidget;
use App\Filament\Admin\Widgets\TreasurerStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
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
            ->brandName('PPRC Admin')
            ->colors([
                'primary' => Color::Slate,
            ])
            // 230px sidebar — Linear/Stripe-style narrow nav. The custom
            // theme tightens internal spacing so this still reads well.
            ->sidebarWidth('230px')
            ->collapsedSidebarWidth('4.5rem')
            ->sidebarCollapsibleOnDesktop()
            // Let the content area breathe full-width across modern monitors.
            ->maxContentWidth(Width::Full)
            // Custom CSS layer for premium SaaS chrome (typography, shadows,
            // dark active nav item, table polish, etc.).
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            // Widget order on the dashboard (lower sort = earlier). Each
            // widget also declares its own ::$sort, but listing them here
            // gives a single canonical order to read at a glance.
            ->widgets([
                PrimaryKpiWidget::class,
                ActionRequiredWidget::class,
                TreasurerStatsWidget::class,
                MembershipSecretaryStatsWidget::class,
                MatchDirectorStatsWidget::class,
                SecretaryStatsWidget::class,
                RecentMembershipPaymentsWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Member portal')
                    ->icon('heroicon-o-user-circle')
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
