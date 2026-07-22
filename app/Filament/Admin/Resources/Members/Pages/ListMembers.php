<?php

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
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

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MemberStatus::Active->value))
                ->badge(Member::where('status', MemberStatus::Active->value)->count()),

            'pending_onboard' => Tab::make('Pending onboard')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MemberStatus::Pending->value))
                ->badge(Member::where('status', MemberStatus::Pending->value)->count())
                ->badgeColor(Member::where('status', MemberStatus::Pending->value)->count() > 0 ? 'warning' : 'gray'),

            'renewal_due' => Tab::make('Renewal due')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', MemberStatus::Active->value)
                    ->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->whereDoesntHave('memberships', fn (Builder $q) => $q->whereIn('status', [
                        MembershipStatus::PendingPayment->value,
                        MembershipStatus::PendingApproval->value,
                    ])))
                ->badge(Member::query()
                    ->where('status', MemberStatus::Active->value)
                    ->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->whereDoesntHave('memberships', fn (Builder $q) => $q->whereIn('status', [
                        MembershipStatus::PendingPayment->value,
                        MembershipStatus::PendingApproval->value,
                    ]))
                    ->count())
                ->badgeColor('info'),

            'lapsed' => Tab::make('Lapsed')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', MemberStatus::Expired->value)
                    ->where('expiry_date', '>=', now()->subDays(60)->toDateString())
                    ->whereDoesntHave('memberships', fn (Builder $q) => $q->whereIn('status', [
                        MembershipStatus::PendingPayment->value,
                        MembershipStatus::PendingApproval->value,
                    ])))
                ->badge(Member::query()
                    ->where('status', MemberStatus::Expired->value)
                    ->where('expiry_date', '>=', now()->subDays(60)->toDateString())
                    ->whereDoesntHave('memberships', fn (Builder $q) => $q->whereIn('status', [
                        MembershipStatus::PendingPayment->value,
                        MembershipStatus::PendingApproval->value,
                    ]))
                    ->count())
                ->badgeColor('danger'),

            'abandoned' => Tab::make('Abandoned')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MemberStatus::Abandoned->value))
                ->badge(Member::where('status', MemberStatus::Abandoned->value)->count())
                ->badgeColor('gray'),
        ];
    }
}
