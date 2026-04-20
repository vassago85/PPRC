<?php

namespace App\Filament\Admin\Resources\SaprfShooters;

use App\Filament\Admin\Resources\SaprfShooters\Pages\CreateSaprfShooter;
use App\Filament\Admin\Resources\SaprfShooters\Pages\EditSaprfShooter;
use App\Filament\Admin\Resources\SaprfShooters\Pages\ListSaprfShooters;
use App\Filament\Admin\Resources\SaprfShooters\Schemas\SaprfShooterForm;
use App\Filament\Admin\Resources\SaprfShooters\Tables\SaprfShootersTable;
use App\Models\SaprfShooter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SaprfShooterResource extends Resource
{
    protected static ?string $model = SaprfShooter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'SAPRF shooter';

    protected static ?string $pluralModelLabel = 'SAPRF shooters';

    protected static ?string $recordTitleAttribute = 'membership_number';

    public static function form(Schema $schema): Schema
    {
        return SaprfShooterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaprfShootersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaprfShooters::route('/'),
            'create' => CreateSaprfShooter::route('/create'),
            'edit' => EditSaprfShooter::route('/{record}/edit'),
        ];
    }
}
