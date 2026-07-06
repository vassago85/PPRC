<?php

namespace App\Filament\Admin\Resources\MatchCredits\Tables;

use App\Enums\MatchCreditStatus;
use App\Models\MatchCredit;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MatchCreditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'member.user', 'sourceEvent', 'usedEvent',
            ]))
            ->columns([
                TextColumn::make('payee_name')
                    ->label('Owed to')
                    ->state(fn (MatchCredit $r) => $r->payeeName())
                    ->description(fn (MatchCredit $r) => $r->payee_email ?: ($r->member ? 'Member' : 'Guest'))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('payee_name', 'like', "%{$search}%")
                        ->orWhere('payee_email', 'like', "%{$search}%"))
                    ->sortable(),

                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (?int $state) => 'R '.number_format((int) $state / 100, 2))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?MatchCreditStatus $state) => $state?->label())
                    ->color(fn (?MatchCreditStatus $state) => $state?->color() ?? 'gray')
                    ->sortable(),

                TextColumn::make('sourceEvent.title')
                    ->label('From match')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('usedEvent.title')
                    ->label('Used on')
                    ->placeholder('—')
                    ->description(fn (MatchCredit $r) => $r->used_at?->format('d M Y'))
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(MatchCreditStatus::options()),
            ])
            ->recordActions([
                Action::make('mark_used')
                    ->label('Mark used')
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->visible(fn (MatchCredit $r) => $r->isAvailable()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->requiresConfirmation()
                    ->modalHeading('Mark credit as used')
                    ->modalDescription(fn (MatchCredit $r) => 'Mark '.$r->payeeName().'\'s R '
                        .number_format($r->amount_cents / 100, 2).' credit as used?')
                    ->action(function (MatchCredit $r) {
                        $r->update([
                            'status' => MatchCreditStatus::Used->value,
                            'used_at' => $r->used_at ?? now(),
                        ]);

                        Notification::make()->success()->title('Credit marked as used')->send();
                    }),

                Action::make('mark_available')
                    ->label('Reinstate')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (MatchCredit $r) => ! $r->isAvailable()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->requiresConfirmation()
                    ->modalHeading('Reinstate credit')
                    ->action(function (MatchCredit $r) {
                        $r->update([
                            'status' => MatchCreditStatus::Available->value,
                            'used_event_id' => null,
                            'used_at' => null,
                        ]);

                        Notification::make()->success()->title('Credit reinstated')->send();
                    }),

                EditAction::make()
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
                ]),
            ]);
    }
}
