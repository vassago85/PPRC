<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Announcement;
use App\Models\EmailLog;
use App\Models\ExcoMember;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecretaryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('content.announcements.manage');
    }

    protected function getStats(): array
    {
        $liveAnnouncements = Announcement::query()->live()->count();

        $draftAnnouncements = Announcement::query()
            ->where('is_published', false)
            ->count();

        $excoCount = ExcoMember::query()->where('is_current', true)->count();

        $emailsThisMonth = EmailLog::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            Stat::make('Live announcements', number_format($liveAnnouncements))
                ->description($draftAnnouncements.' in draft')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),

            Stat::make('Committee members', number_format($excoCount))
                ->description('shown on /about')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),

            Stat::make('Emails sent this month', number_format($emailsThisMonth))
                ->description('system + committee outbound')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('primary'),
        ];
    }
}
