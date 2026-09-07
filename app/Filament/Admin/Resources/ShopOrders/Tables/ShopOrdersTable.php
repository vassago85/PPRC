<?php

namespace App\Filament\Admin\Resources\ShopOrders\Tables;

use App\Enums\InvoiceType;
use App\Enums\ShopOrderStatus;
use App\Invoices\InvoiceFactory;
use App\Invoices\InvoiceUrl;
use App\Models\ShopOrder;
use App\Models\ShopRun;
use Filament\Actions\Action;
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
                Action::make('viewInvoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(fn (ShopOrder $r) => app(InvoiceFactory::class)->canGenerate($r))
                    ->url(fn (ShopOrder $r) => InvoiceUrl::signed(InvoiceType::Shop, $r->id), shouldOpenInNewTab: true),
                EditAction::make()->visible(fn () => auth()->user()?->can('shop.orders.manage')),
            ])
            ->defaultSort('id', 'desc');
    }
}
