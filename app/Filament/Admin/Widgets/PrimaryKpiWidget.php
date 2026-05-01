<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\MemberStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Models\Event;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Top KPI strip on the admin dashboard.
 *
 * Single source of truth for the four numbers exec actually scans for first:
 * Revenue this month, Pending Approvals (memberships + payment proofs),
 * Upcoming matches, Active members.
 *
 * Each card deep-links into the relevant resource with the right filter
 * applied so the user can drill in with one click.
 */
class PrimaryKpiWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('members.view')
            || $user?->can('payments.view')
            || $user?->can('events.view')
            || $user?->can('memberships.approve'));
    }

    protected function getStats(): array
    {
        return [
            $this->revenueThisMonth(),
            $this->pendingApprovals(),
            $this->upcomingMatches(),
            $this->activeMembers(),
        ];
    }

    private function revenueThisMonth(): Stat
    {
        $cents = MembershipPayment::query()
            ->where('status', PaymentStatus::Confirmed->value)
            ->where('confirmed_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');

        return Stat::make('Revenue this month', 'R '.number_format($cents / 100, 2))
            ->description('Confirmed membership payments since '.now()->startOfMonth()->format('j M'))
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('success')
            ->url(MembershipPaymentResource::getUrl('index', ['activeTab' => 'confirmed']));
    }

    private function pendingApprovals(): Stat
    {
        // "Pending approvals" rolls together the two queues a treasurer or
        // membership secretary actually has to clear: payments awaiting
        // verification + memberships ready for committee sign-off.
        $awaitingProofs = MembershipPayment::query()
            ->where('status', PaymentStatus::Submitted->value)
            ->count();

        $awaitingApproval = Membership::query()
            ->where('status', MembershipStatus::PendingApproval->value)
            ->count();

        $total = $awaitingProofs + $awaitingApproval;

        $description = match (true) {
            $awaitingProofs > 0 && $awaitingApproval > 0 => "{$awaitingProofs} proof".($awaitingProofs === 1 ? '' : 's')." · {$awaitingApproval} membership".($awaitingApproval === 1 ? '' : 's'),
            $awaitingProofs > 0 => "{$awaitingProofs} EFT proof".($awaitingProofs === 1 ? '' : 's').' to verify',
            $awaitingApproval > 0 => "{$awaitingApproval} membership".($awaitingApproval === 1 ? '' : 's').' to sign off',
            default => 'Inbox is clear',
        };

        // Land on the most actionable queue: proofs first if any, else memberships.
        $url = $awaitingProofs > 0
            ? MembershipPaymentResource::getUrl('index', ['activeTab' => 'awaiting'])
            : MembershipResource::getUrl('index', [
                'tableFilters' => ['status' => ['value' => MembershipStatus::PendingApproval->value]],
            ]);

        return Stat::make('Pending approvals', number_format($total))
            ->description($description)
            ->descriptionIcon('heroicon-m-inbox-stack')
            ->color($total > 0 ? 'warning' : 'gray')
            ->url($url);
    }

    private function upcomingMatches(): Stat
    {
        $count = Event::query()->upcoming()->count();

        $next = Event::query()
            ->upcoming()
            ->orderBy('start_date')
            ->first();

        $description = $next?->start_date
            ? 'Next: '.$next->start_date->format('D j M')
            : 'Nothing scheduled yet';

        return Stat::make('Upcoming matches', number_format($count))
            ->description($description)
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color($count > 0 ? 'info' : 'gray')
            ->url(EventResource::getUrl('index'));
    }

    private function activeMembers(): Stat
    {
        $active = Member::query()
            ->where('status', MemberStatus::Active->value)
            ->count();

        $total = Member::query()->count();

        return Stat::make('Active members', number_format($active))
            ->description("of {$total} total on record")
            ->descriptionIcon('heroicon-m-users')
            ->color('success')
            ->url(MemberResource::getUrl('index', [
                'tableFilters' => ['status' => ['value' => MemberStatus::Active->value]],
            ]));
    }
}
