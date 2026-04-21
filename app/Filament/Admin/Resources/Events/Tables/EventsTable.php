<?php

namespace App\Filament\Admin\Resources\Events\Tables;

use App\Enums\EventStatus;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('start_date')->date('D d M Y')->sortable()->label('Date'),
                TextColumn::make('title')->searchable()->sortable()->wrap(),
                TextColumn::make('matchFormat.short_name')
                    ->label('Format')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?EventStatus $state) => $state?->label())
                    ->color(fn (?EventStatus $state) => $state?->color() ?? 'gray'),
                IconColumn::make('registrations_open')->boolean()->label('Reg open'),
                TextColumn::make('registrations_count')
                    ->counts('registrations')
                    ->label('Entries')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('matchDirector.name')->label('MD')->toggleable()->searchable(),
                TextColumn::make('published_at')->dateTime('d M Y H:i')->toggleable()->label('Published'),
                TextColumn::make('results_published_at')
                    ->dateTime('d M Y H:i')
                    ->toggleable()
                    ->label('Results'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(EventStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                SelectFilter::make('match_format_id')
                    ->label('Format')
                    ->relationship('matchFormat', 'name'),
                TernaryFilter::make('registrations_open'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->icon('heroicon-o-megaphone')
                    ->color('success')
                    ->visible(fn (Event $record) => $record->status === EventStatus::Draft
                        && auth()->user()?->can('events.publish'))
                    ->requiresConfirmation()
                    ->action(function (Event $record) {
                        $record->update([
                            'status' => EventStatus::Published,
                            'published_at' => $record->published_at ?? now(),
                        ]);
                    }),
                Action::make('complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (Event $record) => $record->status === EventStatus::Published)
                    ->requiresConfirmation()
                    ->action(fn (Event $record) => $record->update(['status' => EventStatus::Completed])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
