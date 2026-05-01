<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\EndorsementStatus;
use App\Enums\MemberStatus;
use App\Filament\Admin\Resources\EndorsementRequests\EndorsementRequestResource;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Models\EndorsementRequest;
use App\Models\Member;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * "What needs your attention" — secondary action queue.
 *
 * The marquee approval queues (memberships + payments) are surfaced in
 * PrimaryKpiWidget as "Pending approvals". This widget covers the longer
 * tail: new member onboarding paperwork and endorsement requests.
 *
 * Each stat is colour-coded — warning when something is pending, gray
 * when the queue is clear — and links straight to the right filter.
 */
class ActionRequiredWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('members.view');
    }

    protected function getStats(): array
    {
        $stats = [];

        $pendingMembers = Member::query()
            ->where('status', MemberStatus::Pending->value)
            ->count();

        $stats[] = Stat::make('New members to onboard', number_format($pendingMembers))
            ->description($pendingMembers > 0
                ? 'Awaiting profile completion'
                : 'Nothing outstanding')
            ->descriptionIcon('heroicon-m-user-plus')
            ->color($pendingMembers > 0 ? 'warning' : 'gray')
            ->url(MemberResource::getUrl('index', [
                'tableFilters' => ['status' => ['value' => MemberStatus::Pending->value]],
            ]));

        $pendingEndorsements = EndorsementRequest::query()
            ->where('status', EndorsementStatus::Pending->value)
            ->count();

        $latest = EndorsementRequest::query()
            ->where('status', EndorsementStatus::Pending->value)
            ->latest()
            ->first();

        $stats[] = Stat::make('Endorsements to review', number_format($pendingEndorsements))
            ->description($pendingEndorsements > 0 && $latest
                ? 'Oldest: '.$latest->created_at->diffForHumans()
                : 'Nothing waiting')
            ->descriptionIcon('heroicon-m-shield-check')
            ->color($pendingEndorsements > 0 ? 'warning' : 'gray')
            ->url(EndorsementRequestResource::getUrl('index'));

        return $stats;
    }
}
