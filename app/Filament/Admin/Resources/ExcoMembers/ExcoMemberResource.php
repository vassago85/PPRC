<?php

namespace App\Filament\Admin\Resources\ExcoMembers;

use App\Filament\Admin\Resources\ExcoMembers\Pages\CreateExcoMember;
use App\Filament\Admin\Resources\ExcoMembers\Pages\EditExcoMember;
use App\Filament\Admin\Resources\ExcoMembers\Pages\ListExcoMembers;
use App\Filament\Admin\Resources\ExcoMembers\Schemas\ExcoMemberForm;
use App\Filament\Admin\Resources\ExcoMembers\Tables\ExcoMembersTable;
use App\Models\ExcoMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ExcoMemberResource extends Resource
{
    protected static ?string $model = ExcoMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $modelLabel = 'Exco member';

    protected static ?string $pluralModelLabel = 'Exco members';

    public static function form(Schema $schema): Schema
    {
        return ExcoMemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExcoMembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExcoMembers::route('/'),
            'create' => CreateExcoMember::route('/create'),
            'edit' => EditExcoMember::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
