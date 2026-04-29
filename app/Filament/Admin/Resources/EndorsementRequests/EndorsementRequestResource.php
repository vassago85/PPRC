<?php

namespace App\Filament\Admin\Resources\EndorsementRequests;

use App\Enums\EndorsementStatus;
use App\Filament\Admin\Resources\EndorsementRequests\Pages\ListEndorsementRequests;
use App\Models\EndorsementRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;
use UnitEnum;

class EndorsementRequestResource extends Resource
{
    protected static ?string $model = EndorsementRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Endorsements';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('member.membership_number')->label('#')->badge()->searchable(),
                TextColumn::make('member.first_name')->label('Member')
                    ->formatStateUsing(fn ($record) => $record->member?->fullName())
                    ->searchable(['member.first_name', 'member.last_name']),
                TextColumn::make('firearm_type')->label('Firearm')->limit(30),
                TextColumn::make('reason')->limit(40),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?EndorsementStatus $state) => $state?->label())
                    ->color(fn (?EndorsementStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('created_at')->dateTime('d M Y H:i')->label('Requested'),
                TextColumn::make('reviewed_at')->dateTime('d M Y')->label('Reviewed')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(EndorsementStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->default(EndorsementStatus::Pending->value),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EndorsementRequest $record) => $record->status === EndorsementStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('Approve endorsement')
                    ->modalDescription(fn (EndorsementRequest $record) => "Approve endorsement for {$record->member?->fullName()} — {$record->firearm_type}?")
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Admin notes (optional)')
                            ->rows(2),
                    ])
                    ->action(function (EndorsementRequest $record, array $data) {
                        $record->update([
                            'status' => EndorsementStatus::Approved,
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_at' => now(),
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                        ]);
                    }),
                Action::make('decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EndorsementRequest $record) => $record->status === EndorsementStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('Decline endorsement')
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Reason for declining')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (EndorsementRequest $record, array $data) {
                        $record->update([
                            'status' => EndorsementStatus::Declined,
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_at' => now(),
                            'admin_notes' => $data['admin_notes'],
                        ]);
                    }),
                Action::make('view_letter')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (EndorsementRequest $record) => $record->token
                        ? route('portal.documents.endorsement', $record->token)
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (EndorsementRequest $record) => $record->status === EndorsementStatus::Approved && $record->token),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEndorsementRequests::route('/'),
        ];
    }
}
