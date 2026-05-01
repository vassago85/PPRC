<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Models\MembershipPayment;
use App\Services\Membership\MemberService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * "Recent renewals & payments" — the at-a-glance list a treasurer or
 * membership secretary actually wants on the dashboard. Drops people
 * straight into the payments queue and lets them confirm inline.
 *
 * Kept intentionally minimal: TableWidget forces simple pagination
 * and a fixed page size, so we don't try to override either.
 */
class RecentMembershipPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent renewals & payments';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('payments.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MembershipPayment::query()
                    ->with(['membership.member', 'membership.membershipType'])
                    ->latest('created_at'),
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M H:i')
                    ->sortable(),

                TextColumn::make('reference')
                    ->label('Ref')
                    ->copyable()
                    ->copyMessage('Reference copied')
                    ->fontFamily('mono')
                    ->weight('semibold')
                    ->placeholder('—'),

                TextColumn::make('member_number')
                    ->label('#')
                    ->state(fn (MembershipPayment $r) => $r->membership?->member?->membership_number)
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('member_name')
                    ->label('Member')
                    ->state(fn (MembershipPayment $r) => $r->membership?->member?->fullName() ?? '—')
                    ->description(fn (MembershipPayment $r) => $r->membership?->membership_type_name_snapshot),

                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (?int $s) => $s !== null ? 'R '.number_format($s / 100, 2) : '—')
                    ->alignEnd(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?PaymentStatus $s) => $s?->label())
                    ->color(fn (?PaymentStatus $s) => $s?->color() ?? 'gray'),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->size('sm')
                    ->visible(fn (MembershipPayment $r) => auth()->user()?->can('payments.eft.confirm')
                        && in_array($r->status, [PaymentStatus::Pending, PaymentStatus::Submitted], true))
                    ->requiresConfirmation()
                    ->action(function (MembershipPayment $r) {
                        if (! $r->membership) {
                            Notification::make()->danger()->title('No membership attached')->send();

                            return;
                        }
                        app(MemberService::class)->activate($r->membership, auth()->user());
                        Notification::make()->success()->title('Payment confirmed')->send();
                    }),
                Action::make('view')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->size('sm')
                    ->color('gray')
                    ->url(fn (MembershipPayment $r) => MembershipPaymentResource::getUrl('edit', ['record' => $r])),
            ])
            ->emptyStateHeading('No payments yet')
            ->emptyStateDescription('When a member starts an EFT renewal the payment record (with reference) will appear here.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
