<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TreasurerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('payments.view');
    }

    protected function getStats(): array
    {
        $awaitingVerification = MembershipPayment::query()
            ->where('status', PaymentStatus::Submitted->value)
            ->count();

        $pendingApproval = Membership::query()
            ->where('status', MembershipStatus::PendingApproval->value)
            ->count();

        $monthTotal = MembershipPayment::query()
            ->where('status', PaymentStatus::Confirmed->value)
            ->where('confirmed_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');

        return [
            Stat::make('Proofs to verify', number_format($awaitingVerification))
                ->description('EFT payments submitted, awaiting your check')
                ->descriptionIcon('heroicon-m-document-check')
                ->color($awaitingVerification > 0 ? 'warning' : 'gray')
                // Land on the Payments queue filtered to Submitted so the
                // treasurer sees exactly what they need to action.
                ->url(MembershipPaymentResource::getUrl('index', [
                    'activeTab' => 'awaiting',
                ])),

            Stat::make('Memberships awaiting approval', number_format($pendingApproval))
                ->description('payment verified, not yet active')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingApproval > 0 ? 'warning' : 'gray')
                ->url(MembershipResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'pending_approval']]])),

            Stat::make('Revenue this month', 'R '.number_format($monthTotal / 100, 2))
                ->description('membership payments confirmed this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->url(MembershipPaymentResource::getUrl('index', [
                    'activeTab' => 'confirmed',
                ])),
        ];
    }
}
