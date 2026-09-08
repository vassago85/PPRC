<?php

namespace App\Filament\Admin\Resources\Memberships\Tables;

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Enums\RenewalSource;
use App\Filament\Admin\Actions\QuickEditMembershipAction;
use App\Filament\Admin\Actions\RenewMembershipAction;
use App\Filament\Admin\Actions\ResendMembershipPaymentRequestAction;
use App\Filament\Admin\Support\MemberSearch;
use App\Filament\Admin\Support\SearchTerm;
use App\Services\Membership\MemberService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MembershipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('period_end', 'desc')
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['member.user', 'payments' => fn ($q) => $q->latest('created_at')]))
            ->searchPlaceholder('Search name, member number, email or payment ref')
            ->columns([
                TextColumn::make('member.membership_number')
                    ->label('#')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->member?->formattedMembershipNumber())
                    ->placeholder('Pending')
                    ->tooltip(fn ($record) => $record->member?->formattedMembershipNumber() === null
                        ? 'Assigned once the membership is approved'
                        : null)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('member.first_name')->label('Member')
                    ->formatStateUsing(fn ($record) => $record->member?->fullName())
                    ->description(fn ($record) => $record->member?->user?->email)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->whereHas('member', fn ($q) => MemberSearch::apply($q, $search))),
                TextColumn::make('membership_type_name_snapshot')->label('Type')->badge()->sortable(),
                TextColumn::make('period_start')->date('d M Y'),
                TextColumn::make('period_end')
                    ->label('Period end')
                    ->formatStateUsing(fn ($record) => $record->isLifetime()
                        ? 'Lifetime'
                        : $record->period_end?->format('d M Y'))
                    ->color(fn ($record) => $record->isLapsed() ? 'danger' : null)
                    ->tooltip(fn ($record) => ($record->isLifetime() && $record->period_end !== null)
                        ? 'No expiry (stored end date: '.$record->period_end->format('d M Y').')'
                        : null)
                    ->sortable(),
                // Shows where the membership actually stands today rather than
                // the last value written to the column, which only moves when
                // the nightly expiry job runs.
                TextColumn::make('status')
                    ->badge()
                    ->state(fn ($record) => $record->effectiveStatus())
                    ->formatStateUsing(fn (?MembershipStatus $state) => $state?->label())
                    ->color(fn (?MembershipStatus $state) => $state?->color() ?? 'gray')
                    ->description(fn ($record) => match (true) {
                        $record->isLapsed() => 'Still marked active — period has ended',
                        $record->isSuperseded() => 'Replaced by a newer membership',
                        default => null,
                    })
                    ->tooltip(fn ($record) => $record->isLapsed()
                        ? 'The nightly expiry check will set this to Expired. Renew or extend the period to keep it active.'
                        : null),

                TextColumn::make('renewal_source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (?RenewalSource $state) => $state?->label())
                    ->color(fn (?RenewalSource $state) => $state?->color() ?? 'gray')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Shown by default because the whole point of scanning this
                // list is usually "who owes what and by what reference?" — a
                // treasurer matching a bank deposit needs to see the ref
                // without hunting through the column toggler.
                TextColumn::make('payment_reference')
                    ->label('Payment ref')
                    ->state(fn ($record) => $record->payments->first()?->reference)
                    ->copyable()
                    ->copyMessage('Reference copied')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable()
                    ->searchable(query: function ($query, string $search) {
                        $term = SearchTerm::make($query, $search);

                        $query->whereHas('payments', fn ($q) => $q
                            ->where($term->column('reference'), 'like', $term->contains()));
                    }),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->state(fn ($record) => $record->payments->first()?->status)
                    ->badge()
                    ->formatStateUsing(fn (?PaymentStatus $state) => $state?->label() ?? '—')
                    ->color(fn (?PaymentStatus $state) => $state?->color() ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price_cents_snapshot')->label('Price paid')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state, $record) {
                        $current = $record->membershipType?->price_cents;
                        if ($state === null) {
                            return $current !== null
                                ? '<span class="text-gray-400">—</span> <span class="text-xs text-gray-500">(type R '
                                    .number_format($current / 100, 2).')</span>'
                                : '<span class="text-gray-400">—</span>';
                        }
                        $paid = 'R '.number_format($state / 100, 2);
                        if ($current !== null && (int) $current !== (int) $state) {
                            $paid .= ' <span class="text-xs text-gray-500">(now R '
                                .number_format($current / 100, 2).')</span>';
                        }

                        return $paid;
                    })
                    ->html()
                    ->alignEnd(),
                TextColumn::make('approved_at')
                    ->label('Approved on')
                    ->dateTime('d M Y')
                    ->tooltip('Date this membership was approved'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(MembershipStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                SelectFilter::make('renewal_source')
                    ->label('Source')
                    ->options(collect(RenewalSource::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->tooltip(fn ($record) => 'Approve membership for '.($record->member?->fullName() ?? 'this member'))
                    ->visible(fn ($record) => in_array($record->status, [MembershipStatus::PendingApproval, MembershipStatus::PendingPayment]))
                    ->requiresConfirmation()
                    ->action(fn ($record) => app(MemberService::class)->activate($record, auth()->user())),
                QuickEditMembershipAction::make(),
                ActionGroup::make([
                    RenewMembershipAction::make(),
                    ResendMembershipPaymentRequestAction::forMembership(),
                    EditAction::make()->label('Full edit'),
                ])->tooltip('More actions'),
            ])
            ->toolbarActions([
                BulkAction::make('approve')
                    ->label('Approve selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Each selected membership awaiting approval or payment will be activated, its pending payment confirmed, and the member emailed.')
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn () => auth()->user()?->can('memberships.manage'))
                    ->action(function ($records): void {
                        $service = app(MemberService::class);
                        $approved = 0;

                        foreach ($records as $record) {
                            if (! in_array($record->status, [
                                MembershipStatus::PendingApproval,
                                MembershipStatus::PendingPayment,
                            ], true)) {
                                continue;
                            }

                            $service->activate($record, auth()->user());
                            $approved++;
                        }

                        Notification::make()
                            ->status($approved > 0 ? 'success' : 'warning')
                            ->title($approved > 0
                                ? "Approved {$approved} membership(s)"
                                : 'Nothing to approve')
                            ->body($approved > 0
                                ? null
                                : 'None of the selected memberships were awaiting approval or payment.')
                            ->send();
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
