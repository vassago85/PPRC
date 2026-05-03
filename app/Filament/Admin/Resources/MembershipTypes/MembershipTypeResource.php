<?php

namespace App\Filament\Admin\Resources\MembershipTypes;

use App\Filament\Admin\Resources\MembershipTypes\Pages\CreateMembershipType;
use App\Filament\Admin\Resources\MembershipTypes\Pages\EditMembershipType;
use App\Filament\Admin\Resources\MembershipTypes\Pages\ListMembershipTypes;
use App\Filament\Admin\Resources\MembershipTypes\Schemas\MembershipTypeForm;
use App\Filament\Admin\Resources\MembershipTypes\Tables\MembershipTypesTable;
use App\Models\MembershipType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MembershipTypeResource extends Resource
{
    protected static ?string $model = MembershipType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('memberships.types.manage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return MembershipTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembershipTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembershipTypes::route('/'),
            'create' => CreateMembershipType::route('/create'),
            'edit' => EditMembershipType::route('/{record}/edit'),
        ];
    }
}
