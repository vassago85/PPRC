<?php

namespace App\Filament\Admin\Resources\ShopRuns;

use App\Filament\Admin\Resources\ShopRuns\Pages\CreateShopRun;
use App\Filament\Admin\Resources\ShopRuns\Pages\EditShopRun;
use App\Filament\Admin\Resources\ShopRuns\Pages\ListShopRuns;
use App\Filament\Admin\Resources\ShopRuns\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\ShopRuns\RelationManagers\ProductsRelationManager;
use App\Filament\Admin\Resources\ShopRuns\Schemas\ShopRunForm;
use App\Filament\Admin\Resources\ShopRuns\Tables\ShopRunsTable;
use App\Models\ShopRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShopRunResource extends Resource
{
    protected static ?string $model = ShopRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'apparel run';

    protected static ?string $pluralModelLabel = 'apparel runs';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('shop.products.manage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return ShopRunForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShopRunsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShopRuns::route('/'),
            'create' => CreateShopRun::route('/create'),
            'edit' => EditShopRun::route('/{record}/edit'),
        ];
    }
}
