<?php

namespace App\Filament\Admin\Resources\ClubBadges;

use App\Filament\Admin\Resources\ClubBadges\Pages\CreateClubBadge;
use App\Filament\Admin\Resources\ClubBadges\Pages\EditClubBadge;
use App\Filament\Admin\Resources\ClubBadges\Pages\ListClubBadges;
use App\Models\ClubBadge;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ClubBadgeResource extends Resource
{
    protected static ?string $model = ClubBadge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 35;

    protected static ?string $navigationLabel = 'Club badges';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('members.update');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(80)
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                TextInput::make('accent_color')->maxLength(32)->default('brand')->helperText('Tailwind token hint, e.g. brand, emerald, amber.'),
                TextInput::make('sort_order')->numeric()->default(0),
                Textarea::make('description')->rows(2)->maxLength(500)->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->toggleable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClubBadges::route('/'),
            'create' => CreateClubBadge::route('/create'),
            'edit' => EditClubBadge::route('/{record}/edit'),
        ];
    }
}
