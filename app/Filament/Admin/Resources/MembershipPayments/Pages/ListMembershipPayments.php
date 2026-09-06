<?php

namespace App\Filament\Admin\Resources\MembershipPayments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Models\MembershipPayment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListMembershipPayments extends ListRecords
{
    protected static string $resource = MembershipPaymentResource::class;

    /**
     * A live subtitle carrying the totals for each segment — the treasurer
     * lands on this page to answer "who owes us" and "who am I chasing", so
     * the header sentence is the answer.
     */
    public function getSubheading(): string|Htmlable|null
    {
        $chaseCents = (int) MembershipPayment::query()
            ->where('status', PaymentStatus::Pending->value)
            ->sum('amount_cents');
        $reviewCount = MembershipPayment::query()
            ->where('status', PaymentStatus::Submitted->value)
            ->count();
        $confirmedThisMonthCents = (int) MembershipPayment::query()
            ->where('status', PaymentStatus::Confirmed->value)
            ->where('confirmed_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');

        return 'R '.number_format($chaseCents / 100, 2).' being chased · '
            .$reviewCount.' awaiting review · R '
            .number_format($confirmedThisMonthCents / 100, 2).' confirmed this month';
    }

    public function getTabs(): array
    {
        $count = fn (?string $status) => MembershipPayment::query()
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->count();

        $chaseCount = $count(PaymentStatus::Pending->value);
        $awaitingCount = $count(PaymentStatus::Submitted->value);

        return [
            // Default: what the club has to chase.
            'pending' => Tab::make('Needs chasing')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', PaymentStatus::Pending->value))
                ->badge($chaseCount)
                ->badgeColor($chaseCount > 0 ? 'warning' : 'gray'),

            'awaiting' => Tab::make('Awaiting review')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', PaymentStatus::Submitted->value))
                ->badge($awaitingCount)
                ->badgeColor($awaitingCount > 0 ? 'warning' : 'gray'),

            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', PaymentStatus::Confirmed->value)),

            'failed' => Tab::make('Failed / cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    PaymentStatus::Failed->value,
                    PaymentStatus::Cancelled->value,
                ])),

            'all' => Tab::make('All payments'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }
}
