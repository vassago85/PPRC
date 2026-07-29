<?php

namespace App\Filament\Admin\Resources\Memberships\Pages;

use App\Enums\MembershipStatus;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Models\Membership;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMemberships extends ListRecords
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $needsAction = Membership::query()->needsAction()->count();
        $lapsed = Membership::query()->lapsed()->count();

        return [
            // "All" leads, matching the Members list, and keeps the dashboard's
            // ?tableFilters[status] deep-links landing on an unscoped tab.
            'all' => Tab::make('All'),

            'needs_action' => Tab::make('Needs action')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsAction())
                ->badge($needsAction)
                ->badgeColor($needsAction > 0 ? 'warning' : 'gray'),

            'current' => Tab::make('Current')
                ->modifyQueryUsing(fn (Builder $query) => $query->current())
                ->badge(Membership::query()->current()->count()),

            // Rows the nightly expiry job has not caught up with yet. These are
            // the ones that read as "Active" while no longer covering today,
            // which is exactly where the list used to mislead.
            'lapsed' => Tab::make('Lapsed')
                ->modifyQueryUsing(fn (Builder $query) => $query->lapsed())
                ->badge($lapsed)
                ->badgeColor($lapsed > 0 ? 'danger' : 'gray'),

            'expired' => Tab::make('Expired')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', MembershipStatus::Expired->value))
                ->badgeColor('gray'),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', MembershipStatus::Cancelled->value))
                ->badgeColor('gray'),
        ];
    }
}
