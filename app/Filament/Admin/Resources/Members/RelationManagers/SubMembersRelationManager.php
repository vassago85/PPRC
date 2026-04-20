<?php

namespace App\Filament\Admin\Resources\Members\RelationManagers;

use App\Enums\MemberStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'subMembers';

    protected static ?string $title = 'Sub-members';

    protected static ?string $modelLabel = 'sub-member';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('first_name')->required()->maxLength(80),
                TextInput::make('last_name')->required()->maxLength(80),
                DatePicker::make('date_of_birth')
                    ->required()
                    ->maxDate(now())
                    ->helperText('Required so age-based rules can be applied'),
                TextInput::make('known_as')->maxLength(80),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                TextColumn::make('first_name')->searchable(),
                TextColumn::make('last_name')->searchable(),
                TextColumn::make('date_of_birth')->date('d M Y')->label('DOB'),
                TextColumn::make('current_membership_type')
                    ->label('Current type')
                    ->state(fn ($record) => $record->currentMembership()?->membership_type_name_snapshot ?? '—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (MemberStatus $state) => $state->label()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = MemberStatus::Active->value;
                        $data['country'] = $data['country'] ?? 'ZA';

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
