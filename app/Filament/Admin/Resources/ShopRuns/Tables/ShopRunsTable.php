<?php

namespace App\Filament\Admin\Resources\ShopRuns\Tables;

use App\Enums\ShopRunStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShopRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $enum = $state instanceof ShopRunStatus ? $state : ShopRunStatus::from((string) $state);

                        return $enum->label();
                    })
                    ->sortable(),
                IconColumn::make('preview_visible')->boolean()->label('Preview'),
                TextColumn::make('orders_open_at')->dateTime()->sortable(),
                TextColumn::make('orders_close_at')->dateTime()->sortable(),
                TextColumn::make('waitlist_last_notified_at')->dateTime()->label('Last waitlist email'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ShopRunStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
