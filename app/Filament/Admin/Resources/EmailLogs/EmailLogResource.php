<?php

namespace App\Filament\Admin\Resources\EmailLogs;

use App\Filament\Admin\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Models\EmailLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Email log';

    protected static ?string $recordTitleAttribute = 'subject';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('settings.site.manage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
                'sent_at',
                '>=',
                now()->subDays(7),
            ))
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('sent_at')->dateTime()->sortable(),
                TextColumn::make('to_email')->label('To')->searchable(),
                TextColumn::make('subject')->limit(40)->wrap(),
                TextColumn::make('mailable_class')->label('Mailable')->limit(36)->toggleable(),
                TextColumn::make('status')->badge(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
        ];
    }
}
