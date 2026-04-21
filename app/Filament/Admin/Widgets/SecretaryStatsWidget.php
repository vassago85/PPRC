<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Announcement;
use App\Models\EmailLog;
use App\Models\Page;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

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

        $publishedPages = Page::query()
            ->when(
                Schema::hasColumn('pages', 'is_published'),
                fn ($q) => $q->where('is_published', true),
            )
            ->count();

        $emailsThisMonth = EmailLog::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            Stat::make('Live announcements', number_format($liveAnnouncements))
                ->description($draftAnnouncements.' in draft')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),

            Stat::make('Published pages', number_format($publishedPages))
                ->description('CMS content on public site')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Emails sent this month', number_format($emailsThisMonth))
                ->description('system + committee outbound')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('primary'),
        ];
    }
}
