<?php

namespace App\Filament\Admin\Resources\MatchCredits\Tables;

use App\Enums\MatchCreditStatus;
use App\Filament\Admin\Actions\UseMatchCreditAction;
use App\Filament\Admin\Support\SearchTerm;
use App\Models\MatchCredit;
use App\Services\Events\MatchEntryTransferService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

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
                    ->searchable(query: function (Builder $query, string $search) {
                        $term = SearchTerm::make($query, $search);

                        $query
                            ->where($term->column('payee_name'), 'like', $term->contains())
                            ->orWhere($term->column('payee_email'), 'like', $term->contains());
                    })
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
                    ->description(fn (MatchCredit $r) => collect([
                        $r->used_at?->format('d M Y'),
                        $r->used_registration_id ? 'settled an entry' : null,
                    ])->filter()->implode(' · ') ?: null)
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
                UseMatchCreditAction::make(),

                Action::make('mark_used')
                    ->tooltip('Write the credit off the ledger without entering them in a match')
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
                    ->modalDescription(fn (MatchCredit $r) => $r->used_registration_id
                        ? 'This credit paid for an entry on '.($r->usedEvent?->title ?? 'a match')
                            .'. Reinstating it takes the money back off that entry, so it will owe again '
                            .'unless it was also paid in cash.'
                        : null)
                    ->action(function (MatchCredit $r) {
                        if ($r->used_registration_id === null) {
                            $r->update([
                                'status' => MatchCreditStatus::Available->value,
                                'used_event_id' => null,
                                'used_at' => null,
                            ]);

                            Notification::make()->success()->title('Credit reinstated')->send();

                            return;
                        }

                        try {
                            app(MatchEntryTransferService::class)->unsettle($r, auth()->user());

                            Notification::make()->success()
                                ->title('Credit reinstated')
                                ->body('Taken back off the entry it had settled.')
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()
                                ->title('Could not reinstate this credit')
                                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                                ->send();
                        }
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
