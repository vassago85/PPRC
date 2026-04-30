<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\MemberStatus;
use App\Enums\MembershipStatus;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Models\Event;
use App\Models\Member;
use App\Models\Membership;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClubOverviewStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('members.view');
    }

    protected function getStats(): array
    {
        $activeMembers = Member::query()
            ->where('status', MemberStatus::Active->value)
            ->count();

        $totalMembers = Member::query()->count();

        $activeMemberships = Membership::query()
            ->where('status', MembershipStatus::Active->value)
            ->where('period_end', '>=', now()->toDateString())
            ->count();

        $upcomingMatches = Event::query()
            ->upcoming()
            ->count();

        return [
            Stat::make('Active members', number_format($activeMembers))
                ->description("of {$totalMembers} total")
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->url(MemberResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'active']]])),

            Stat::make('Active memberships', number_format($activeMemberships))
                ->description('currently paid up')
                ->descriptionIcon('heroicon-m-identification')
                ->color('info')
                ->url(MembershipResource::getUrl('index')),

            Stat::make('Upcoming matches', number_format($upcomingMatches))
                ->description('published and scheduled')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->url(EventResource::getUrl('index')),
        ];
    }
}
