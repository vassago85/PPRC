<?php

namespace App\Filament\Admin\Resources\ShopOrders;

use App\Filament\Admin\Resources\ShopOrders\Pages\EditShopOrder;
use App\Filament\Admin\Resources\ShopOrders\Pages\ListShopOrders;
use App\Filament\Admin\Resources\ShopOrders\Schemas\ShopOrderForm;
use App\Filament\Admin\Resources\ShopOrders\Tables\ShopOrdersTable;
use App\Models\ShopOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShopOrderResource extends Resource
{
    protected static ?string $model = ShopOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'shop order';

    protected static ?string $pluralModelLabel = 'shop orders';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('shop.orders.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return ShopOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShopOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShopOrders::route('/'),
            'edit' => EditShopOrder::route('/{record}/edit'),
        ];
    }
}
