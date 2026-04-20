<?php

namespace App\Filament\Admin\Resources\SaprfShooters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SaprfShootersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                TextColumn::make('membership_number')->label('SAPRF #')->badge()->searchable()->sortable(),
                TextColumn::make('first_name')->searchable(),
                TextColumn::make('last_name')->searchable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('verified_on')->date('d M Y'),
                TextColumn::make('importedBy.name')->label('Imported by')->toggleable(),
                TextColumn::make('imported_at')->dateTime('d M Y H:i')->toggleable(),
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
