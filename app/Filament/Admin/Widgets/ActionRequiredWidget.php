<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\EndorsementStatus;
use App\Enums\MemberStatus;
use App\Enums\MembershipStatus;
use App\Filament\Admin\Resources\EndorsementRequests\EndorsementRequestResource;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Models\EndorsementRequest;
use App\Models\Member;
use App\Models\Membership;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Top-of-dashboard "what needs your attention right now" panel.
 *
 * Each stat is colour-coded — warning when there is something pending,
 * gray when everything is clear — and links straight to the relevant
 * resource with the right filter applied so the admin can act in one click.
 */
class ActionRequiredWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('members.view')
            || $user?->can('memberships.approve')
            || $user?->can('memberships.manage'));
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->can('members.view')) {
            $pendingMembers = Member::query()
                ->where('status', MemberStatus::Pending->value)
                ->count();

            $stats[] = Stat::make('New members to onboard', number_format($pendingMembers))
                ->description($pendingMembers > 0
                    ? 'awaiting profile completion'
                    : 'nothing outstanding')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($pendingMembers > 0 ? 'warning' : 'gray')
                ->url(MemberResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MemberStatus::Pending->value]],
                ]));
        }

        if ($user?->can('memberships.approve') || $user?->can('memberships.manage')) {
            $awaitingApproval = Membership::query()
                ->where('status', MembershipStatus::PendingApproval->value)
                ->count();

            $awaitingPayment = Membership::query()
                ->where('status', MembershipStatus::PendingPayment->value)
                ->count();

            $stats[] = Stat::make('Memberships to approve', number_format($awaitingApproval))
                ->description($awaitingPayment > 0
                    ? "{$awaitingPayment} awaiting payment"
                    : 'committee sign-off')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($awaitingApproval > 0 ? 'warning' : 'gray')
                ->url(MembershipResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MembershipStatus::PendingApproval->value]],
                ]));
        }

        // Endorsement requests are gated by the same Members section access:
        // any admin who can view members can review endorsements.
        if ($user?->can('members.view')) {
            $pendingEndorsements = EndorsementRequest::query()
                ->where('status', EndorsementStatus::Pending->value)
                ->count();

            $latest = EndorsementRequest::query()
                ->where('status', EndorsementStatus::Pending->value)
                ->latest()
                ->first();

            $description = $pendingEndorsements > 0 && $latest
                ? 'oldest: '.$latest->created_at->diffForHumans()
                : 'nothing waiting';

            $stats[] = Stat::make('Endorsements to review', number_format($pendingEndorsements))
                ->description($description)
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($pendingEndorsements > 0 ? 'warning' : 'gray')
                ->url(EndorsementRequestResource::getUrl('index'));
        }

        return $stats;
    }
}
