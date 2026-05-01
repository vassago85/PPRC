<?php

namespace App\Filament\Admin\Resources\Members;

use App\Filament\Admin\Resources\Members\Pages\CreateMember;
use App\Filament\Admin\Resources\Members\Pages\EditMember;
use App\Filament\Admin\Resources\Members\Pages\ListMembers;
use App\Filament\Admin\Resources\Members\RelationManagers\ClubBadgesRelationManager;
use App\Filament\Admin\Resources\Members\RelationManagers\MembershipsRelationManager;
use App\Filament\Admin\Resources\Members\RelationManagers\SubMembersRelationManager;
use App\Filament\Admin\Resources\Members\Schemas\MemberForm;
use App\Filament\Admin\Resources\Members\Tables\MembersTable;
use App\Enums\MemberStatus;
use App\Models\Member;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'membership_number';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('members.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Member::query()
            ->where('status', MemberStatus::Pending->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Members awaiting onboarding';
    }

    /**
     * Powers the global search field in the topbar. We index the columns
     * an admin would actually type — name, membership number, contact.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'membership_number',
            'first_name',
            'last_name',
            'phone_number',
            'id_number',
            'saprf_membership_number',
        ];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        $name = trim($record->first_name.' '.$record->last_name);

        return $name !== '' ? $name : ('Member #'.$record->membership_number);
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return array_filter([
            'Member #' => $record->membership_number,
            'Phone' => $record->phone_number,
            'Status' => $record->status?->label() ?? null,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MembershipsRelationManager::class,
            SubMembersRelationManager::class,
            ClubBadgesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}
