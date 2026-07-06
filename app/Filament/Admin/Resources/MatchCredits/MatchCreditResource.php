<?php

namespace App\Filament\Admin\Resources\MatchCredits;

use App\Filament\Admin\Resources\MatchCredits\Pages\CreateMatchCredit;
use App\Filament\Admin\Resources\MatchCredits\Pages\EditMatchCredit;
use App\Filament\Admin\Resources\MatchCredits\Pages\ListMatchCredits;
use App\Filament\Admin\Resources\MatchCredits\Schemas\MatchCreditForm;
use App\Filament\Admin\Resources\MatchCredits\Tables\MatchCreditsTable;
use App\Models\MatchCredit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Ledger of match-fee credits the club owes shooters (e.g. a paid no-show
 * whose fee is held for a future match). Lets a committee member see at a
 * glance who is owed a credit and mark them used once redeemed.
 */
class MatchCreditResource extends Resource
{
    protected static ?string $model = MatchCredit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Matches';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'match credit';

    protected static ?string $pluralModelLabel = 'match credits';

    protected static ?string $recordTitleAttribute = 'payee_name';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('events.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('events.registrations.manage');
    }

    public static function canEdit($record): bool
    {
        return (bool) auth()->user()?->can('events.registrations.manage');
    }

    public static function canDelete($record): bool
    {
        return (bool) auth()->user()?->can('events.registrations.manage');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MatchCredit::query()->available()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Credits still owed';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['payee_name', 'payee_email'];
    }

    public static function form(Schema $schema): Schema
    {
        return MatchCreditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatchCreditsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMatchCredits::route('/'),
            'create' => CreateMatchCredit::route('/create'),
            'edit' => EditMatchCredit::route('/{record}/edit'),
        ];
    }
}
