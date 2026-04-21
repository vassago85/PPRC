<?php

namespace App\Filament\Admin\Resources\ShopRuns\RelationManagers;

use App\Enums\ShopOrderStatus;
use App\Filament\Admin\Resources\ShopOrders\ShopOrderResource;
use App\Models\ShopOrder;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('shop.orders.view');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('user.name')->label('Member account'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $enum = $state instanceof ShopOrderStatus ? $state : ShopOrderStatus::from((string) $state);

                        return $enum->label();
                    }),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => 'R '.number_format($state / 100, 2)),
                TextColumn::make('eft_reference')->label('EFT ref')->copyable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn (ShopOrder $record): string => ShopOrderResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('id', 'desc');
    }
}
