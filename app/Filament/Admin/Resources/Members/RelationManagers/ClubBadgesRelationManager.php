<?php

namespace App\Filament\Admin\Resources\Members\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClubBadgesRelationManager extends RelationManager
{
    protected static string $relationship = 'clubBadges';

    protected static ?string $title = 'Badges';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('club_badges.sort_order')
            ->columns([
                TextColumn::make('name')->label('Badge'),
                TextColumn::make('slug')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }
}
