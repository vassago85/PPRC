<?php

namespace App\Filament\Admin\Resources\ShopOrders\Schemas;

use App\Enums\ShopOrderStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ShopOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->options(collect(ShopOrderStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                ->required()
                ->disabled(fn () => ! auth()->user()?->can('shop.orders.manage')),
        ]);
    }
}
