<?php

namespace App\Filament\Admin\Resources\MembershipPayments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Models\MembershipPayment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMembershipPayments extends ListRecords
{
    protected static string $resource = MembershipPaymentResource::class;

    public function getTabs(): array
    {
        $count = fn (?string $status) => MembershipPayment::query()
            ->when($status !== null, fn (Builder $q) => $q->where('status', $status))
            ->count();

        $awaitingCount = $count(PaymentStatus::Submitted->value);

        return [
            'awaiting' => Tab::make('Awaiting verification')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', PaymentStatus::Submitted->value))
                ->badge($awaitingCount)
                ->badgeColor($awaitingCount > 0 ? 'warning' : 'gray'),

            'pending' => Tab::make('Pending (no proof yet)')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', PaymentStatus::Pending->value))
                ->badge($count(PaymentStatus::Pending->value)),

            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', PaymentStatus::Confirmed->value)),

            'failed' => Tab::make('Failed / cancelled')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', [
                    PaymentStatus::Failed->value,
                    PaymentStatus::Cancelled->value,
                ])),

            'all' => Tab::make('All payments'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'awaiting';
    }
}
