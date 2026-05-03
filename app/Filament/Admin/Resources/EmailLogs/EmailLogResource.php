<?php

namespace App\Filament\Admin\Resources\EmailLogs;

use App\Filament\Admin\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Filament\Admin\Resources\EmailLogs\Pages\ViewEmailLog;
use App\Models\EmailLog;
use BackedEnum;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                'created_at',
                '>=',
                now()->subDays(30),
            ))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('to_email')
                    ->label('To')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied'),
                TextColumn::make('subject')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('mailable_class')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('error')
                    ->limit(40)
                    ->placeholder('—')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                        'pending' => 'Pending',
                    ]),
            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist->schema([
            Section::make('Envelope')->schema([
                TextEntry::make('to_email')->label('To')->copyable(),
                TextEntry::make('to_name')->label('Recipient name')->placeholder('—'),
                TextEntry::make('from_email')->label('From')->placeholder('—'),
                TextEntry::make('from_name')->label('From name')->placeholder('—'),
                TextEntry::make('subject'),
            ])->columns(2),

            Section::make('Delivery')->schema([
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextEntry::make('sent_at')->label('Sent at')->dateTime('d M Y H:i:s')->placeholder('Not sent'),
                TextEntry::make('created_at')->label('Queued at')->dateTime('d M Y H:i:s'),
                TextEntry::make('message_id')->label('Message ID')->copyable()->placeholder('—'),
                TextEntry::make('mailable_class')
                    ->label('Mailable class')
                    ->placeholder('—'),
                TextEntry::make('error')
                    ->label('Error')
                    ->placeholder('No errors')
                    ->color('danger')
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Context')->schema([
                TextEntry::make('context')
                    ->label('')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—')
                    ->fontFamily('mono')
                    ->columnSpanFull(),
            ])->collapsible()->collapsed(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
            'view' => ViewEmailLog::route('/{record}'),
        ];
    }
}
