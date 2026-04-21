<?php

namespace App\Filament\Admin\Resources\HomepageSections;

use App\Filament\Admin\Resources\HomepageSections\Pages\CreateHomepageSection;
use App\Filament\Admin\Resources\HomepageSections\Pages\EditHomepageSection;
use App\Filament\Admin\Resources\HomepageSections\Pages\ListHomepageSections;
use App\Filament\Admin\Resources\HomepageSections\Schemas\HomepageSectionForm;
use App\Filament\Admin\Resources\HomepageSections\Tables\HomepageSectionsTable;
use App\Models\HomepageSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HomepageSectionResource extends Resource
{
    protected static ?string $model = HomepageSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'key';

    protected static ?string $modelLabel = 'homepage section';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('content.home.manage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return HomepageSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomepageSectionsTable::configure($table);
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
            'index' => ListHomepageSections::route('/'),
            'create' => CreateHomepageSection::route('/create'),
            'edit' => EditHomepageSection::route('/{record}/edit'),
        ];
    }
}
