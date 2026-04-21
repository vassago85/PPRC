<?php

namespace App\Filament\Admin\Resources\ShopOrders\Tables;

use App\Enums\ShopOrderStatus;
use App\Models\ShopRun;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShopOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('run.title')->label('Run')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Account')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $enum = $state instanceof ShopOrderStatus ? $state : ShopOrderStatus::from((string) $state);

                        return $enum->label();
                    })
                    ->sortable(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => 'R '.number_format($state / 100, 2)),
                TextColumn::make('eft_reference')->label('EFT ref')->copyable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('shop_run_id')
                    ->label('Run')
                    ->options(fn () => ShopRun::query()->orderByDesc('id')->pluck('title', 'id')->all()),
                SelectFilter::make('status')
                    ->options(collect(ShopOrderStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
            ])
            ->recordActions([
                EditAction::make()->visible(fn () => auth()->user()?->can('shop.orders.manage')),
            ])
            ->defaultSort('id', 'desc');
    }
}
