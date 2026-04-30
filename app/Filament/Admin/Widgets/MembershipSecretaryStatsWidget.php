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

class MembershipSecretaryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('memberships.approve');
    }

    protected function getStats(): array
    {
        $pendingApproval = Membership::query()
            ->where('status', MembershipStatus::PendingApproval->value)
            ->count();

        $expiringSoon = Membership::query()
            ->where('status', MembershipStatus::Active->value)
            ->whereBetween('period_end', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        $newThisMonth = Member::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $pendingMembers = Member::query()
            ->where('status', MemberStatus::Pending->value)
            ->count();

        return [
            Stat::make('Memberships awaiting approval', number_format($pendingApproval))
                ->description('ready for committee sign-off')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($pendingApproval > 0 ? 'warning' : 'gray')
                ->url(MembershipResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'pending_approval']]])),

            Stat::make('Expiring in 30 days', number_format($expiringSoon))
                ->description('renewal reminders due')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringSoon > 0 ? 'warning' : 'gray')
                ->url(MembershipResource::getUrl('index')),

            Stat::make('New members this month', number_format($newThisMonth))
                ->description($pendingMembers.' still pending onboarding')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success')
                ->url(MemberResource::getUrl('index')),
        ];
    }
}
