<?php

namespace App\Filament\Admin\Resources\MembershipPayments;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\MembershipPayments\Pages\ListMembershipPayments;
use App\Filament\Admin\Resources\MembershipPayments\Schemas\MembershipPaymentForm;
use App\Filament\Admin\Resources\MembershipPayments\Tables\MembershipPaymentsTable;
use App\Models\MembershipPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * The single source of truth for membership payments in the admin.
 *
 * Lists every payment (EFT or Paystack) with its reference, status, and the
 * member it belongs to so a treasurer can match against bank statements
 * without leaving Filament. Confirm/reject actions live on the table.
 */
class MembershipPaymentResource extends Resource
{
    protected static ?string $model = MembershipPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 16;

    protected static ?string $modelLabel = 'payment';

    protected static ?string $pluralModelLabel = 'payments';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('payments.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MembershipPayment::query()
            ->where('status', PaymentStatus::Submitted->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Payments awaiting verification';
    }

    public static function form(Schema $schema): Schema
    {
        return MembershipPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembershipPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembershipPayments::route('/'),
            'edit' => Pages\EditMembershipPayment::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['membership.member', 'membership.membershipType', 'confirmedBy']);
    }
}
