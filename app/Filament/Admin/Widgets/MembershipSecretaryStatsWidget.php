<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\MemberStatus;
use App\Enums\MembershipStatus;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Models\Member;
use App\Models\Membership;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Membership-secretary view of the renewal pipeline.
 *
 * "Memberships awaiting approval" lives in PrimaryKpiWidget now (under
 * Pending Approvals), so this widget focuses on the upstream signals:
 * who's about to expire, who has already lapsed, and how many fresh
 * applications came in this month.
 */
class MembershipSecretaryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('memberships.approve');
    }

    protected function getStats(): array
    {
        $expiringSoon = Membership::query()
            ->where('status', MembershipStatus::Active->value)
            ->whereBetween('period_end', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        $lapsedRecently = Membership::query()
            ->where('status', MembershipStatus::Expired->value)
            ->where('period_end', '>=', now()->subDays(60)->toDateString())
            ->count();

        $newThisMonth = Member::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $pendingMembers = Member::query()
            ->where('status', MemberStatus::Pending->value)
            ->count();

        return [
            Stat::make('Expiring in 30 days', number_format($expiringSoon))
                ->description($expiringSoon > 0 ? 'Renewal reminders are due' : 'Nobody expiring soon')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringSoon > 0 ? 'warning' : 'gray')
                ->url(MembershipResource::getUrl('index')),

            Stat::make('Recently lapsed', number_format($lapsedRecently))
                ->description('Expired in the last 60 days')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($lapsedRecently > 0 ? 'danger' : 'gray')
                ->url(MembershipResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MembershipStatus::Expired->value]],
                ])),

            Stat::make('New members this month', number_format($newThisMonth))
                ->description($pendingMembers > 0
                    ? $pendingMembers.' still pending onboarding'
                    : 'All onboarded')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success')
                ->url(MemberResource::getUrl('index')),
        ];
    }
}
