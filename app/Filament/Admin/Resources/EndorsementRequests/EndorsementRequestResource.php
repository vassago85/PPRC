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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\Contracts\View\View;
use UnitEnum;

class EndorsementRequestResource extends Resource
{
    protected static ?string $model = EndorsementRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Endorsements';

    public static function getNavigationBadge(): ?string
    {
        $count = EndorsementRequest::query()
            ->where('status', EndorsementStatus::Pending->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Endorsement requests awaiting review';
    }

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
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (EndorsementRequest $record) => "Endorsement request — {$record->member?->fullName()}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (EndorsementRequest $record): View => view(
                        'filament.admin.endorsements.review-modal',
                        ['record' => $record]
                    )),
                Action::make('edit_request')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (EndorsementRequest $record) => $record->status === EndorsementStatus::Pending)
                    ->modalHeading(fn (EndorsementRequest $record) => "Edit endorsement — {$record->member?->fullName()}")
                    ->modalDescription('Tidy up the request before issuing the letter. Members will see your edits on their copy.')
                    ->modalSubmitActionLabel('Save changes')
                    ->fillForm(fn (EndorsementRequest $record): array => [
                        'reason' => $record->reason,
                        'item_type' => $record->item_type ?? 'rifle',
                        'firearm_type' => $record->firearm_type,
                        'component_type' => $record->component_type,
                        'make' => $record->make,
                        'calibre' => $record->calibre,
                        'action_serial_number' => $record->action_serial_number,
                        'barrel_serial_number' => $record->barrel_serial_number,
                        'firearm_details' => $record->firearm_details,
                        'id_number' => $record->member?->id_number,
                    ])
                    ->form([
                        Grid::make(2)->schema([
                            TextInput::make('id_number')
                                ->label('Member RSA ID number')
                                ->maxLength(32)
                                ->helperText('Saved on the member profile.'),
                            TextInput::make('reason')
                                ->label('Purpose')
                                ->required()
                                ->maxLength(255),
                        ]),
                        Select::make('item_type')
                            ->label('Item type')
                            ->options(['rifle' => 'Complete rifle', 'component' => 'Component / part'])
                            ->required()
                            ->live(),
                        Grid::make(2)->schema([
                            Select::make('firearm_type')
                                ->label('Action type')
                                ->options([
                                    'Bolt action' => 'Bolt action',
                                    'Semi-automatic' => 'Semi-automatic',
                                    'Lever action' => 'Lever action',
                                    'Pump action' => 'Pump action',
                                    'Single shot' => 'Single shot',
                                    'Other' => 'Other',
                                ])
                                ->visible(fn ($get) => ($get('item_type') ?? 'rifle') === 'rifle'),
                            Select::make('component_type')
                                ->label('Component')
                                ->helperText('SA law only requires endorsements for barrels and actions.')
                                ->options([
                                    'Barrel' => 'Barrel',
                                    'Action' => 'Action / receiver',
                                ])
                                ->visible(fn ($get) => $get('item_type') === 'component')
                                ->requiredIf('item_type', 'component'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('make')
                                ->label('Make / brand')
                                ->required()
                                ->maxLength(120),
                            TextInput::make('calibre')
                                ->label('Calibre')
                                ->required()
                                ->maxLength(60),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('action_serial_number')
                                ->label('Action serial')
                                ->maxLength(80)
                                ->requiredWithout('barrel_serial_number')
                                ->helperText('At least one of action / barrel serial is required.'),
                            TextInput::make('barrel_serial_number')
                                ->label('Barrel serial')
                                ->maxLength(80)
                                ->requiredWithout('action_serial_number'),
                        ]),
                        Textarea::make('firearm_details')
                            ->label('Additional details')
                            ->rows(2)
                            ->maxLength(1000),
                    ])
                    ->action(function (EndorsementRequest $record, array $data) {
                        $idNumber = $data['id_number'] ?? null;
                        unset($data['id_number']);

                        $isComponent = ($data['item_type'] ?? 'rifle') === 'component';
                        if ($isComponent) {
                            $data['firearm_type'] = null;
                        } else {
                            $data['component_type'] = null;
                        }

                        $record->update($data);

                        if ($record->member && $idNumber !== null && $record->member->id_number !== $idNumber) {
                            $record->member->update(['id_number' => $idNumber]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Endorsement updated')
                            ->body('Saved. You can now preview or approve.')
                            ->send();
                    }),
                Action::make('preview_letter')
                    ->label('Preview letter')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->url(fn (EndorsementRequest $record) => route('admin.endorsements.preview-letter', $record))
                    ->openUrlInNewTab(),
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
                    ->label('Issued letter')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
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
