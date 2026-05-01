<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Models\MembershipPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Treasurer-only secondary metrics.
 *
 * The big-ticket numbers (revenue this month, pending approvals) now live
 * in PrimaryKpiWidget, so this widget focuses on the secondary cash-flow
 * signals a treasurer cares about: confirmed payments this week and
 * year-to-date revenue.
 */
class TreasurerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('payments.view');
    }

    protected function getStats(): array
    {
        $confirmedThisWeek = MembershipPayment::query()
            ->where('status', PaymentStatus::Confirmed->value)
            ->where('confirmed_at', '>=', now()->startOfWeek())
            ->count();

        $ytdCents = MembershipPayment::query()
            ->where('status', PaymentStatus::Confirmed->value)
            ->where('confirmed_at', '>=', now()->startOfYear())
            ->sum('amount_cents');

        $failedThisMonth = MembershipPayment::query()
            ->whereIn('status', [PaymentStatus::Failed->value, PaymentStatus::Cancelled->value])
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();

        return [
            Stat::make('Payments confirmed this week', number_format($confirmedThisWeek))
                ->description('Since '.now()->startOfWeek()->format('D j M'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->url(MembershipPaymentResource::getUrl('index', ['activeTab' => 'confirmed'])),

            Stat::make('Revenue YTD', 'R '.number_format($ytdCents / 100, 2))
                ->description('Confirmed since '.now()->startOfYear()->format('j M Y'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info')
                ->url(MembershipPaymentResource::getUrl('index', ['activeTab' => 'confirmed'])),

            Stat::make('Failed / cancelled this month', number_format($failedThisMonth))
                ->description($failedThisMonth > 0
                    ? 'Worth a look — member may need a nudge'
                    : 'Nothing rejected this month')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedThisMonth > 0 ? 'danger' : 'gray')
                ->url(MembershipPaymentResource::getUrl('index', ['activeTab' => 'failed'])),
        ];
    }
}
