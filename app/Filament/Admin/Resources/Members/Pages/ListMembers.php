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
            'onboard' => Member::query()->needsOnboarding()->count(),
            'awaiting_email' => Member::query()->awaitingEmail()->count(),
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
                ->modifyQueryUsing(fn (Builder $query) => $query->needsOnboarding())
                ->badge($counts['onboard'])
                ->badgeColor($counts['onboard'] > 0 ? 'warning' : 'gray'),

            // Kept out of the onboarding queue on purpose: nobody at the club can
            // move these along, only the member can, by clicking the link.
            'awaiting_email' => Tab::make('Awaiting email')
                ->modifyQueryUsing(fn (Builder $query) => $query->awaitingEmail())
                ->badge($counts['awaiting_email'])
                ->badgeColor('gray'),

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
