<?php

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Filament\Admin\Resources\Members\MemberResource;
use App\Models\Member;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Every tab is a lifecycle scope from the Member model, so these counts are
     * the same numbers the dashboard, the nav badges and the scheduled reminders
     * use. They used to be hand-written here and drift from everywhere else.
     */
    public function getTabs(): array
    {
        $counts = [
            'active' => Member::query()->active()->count(),
            'pending' => Member::query()->pending()->count(),
            'renewal_due' => Member::query()->renewalDue()->count(),
            'lapsed' => Member::query()->recentlyLapsed()->count(),
            'suspended' => Member::query()->suspended()->count(),
            'abandoned' => Member::query()->abandoned()->count(),
        ];

        return [
            'all' => Tab::make('All'),

            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->active())
                ->badge($counts['active']),

            'pending_onboard' => Tab::make('Pending onboard')
                ->modifyQueryUsing(fn (Builder $query) => $query->pending())
                ->badge($counts['pending'])
                ->badgeColor($counts['pending'] > 0 ? 'warning' : 'gray'),

            'renewal_due' => Tab::make('Renewal due')
                ->modifyQueryUsing(fn (Builder $query) => $query->renewalDue())
                ->badge($counts['renewal_due'])
                ->badgeColor('info'),

            'lapsed' => Tab::make('Lapsed')
                ->modifyQueryUsing(fn (Builder $query) => $query->recentlyLapsed())
                ->badge($counts['lapsed'])
                ->badgeColor('danger'),

            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn (Builder $query) => $query->suspended())
                ->badge($counts['suspended'])
                ->badgeColor($counts['suspended'] > 0 ? 'danger' : 'gray'),

            'abandoned' => Tab::make('Abandoned')
                ->modifyQueryUsing(fn (Builder $query) => $query->abandoned())
                ->badge($counts['abandoned'])
                ->badgeColor('gray'),
        ];
    }
}
