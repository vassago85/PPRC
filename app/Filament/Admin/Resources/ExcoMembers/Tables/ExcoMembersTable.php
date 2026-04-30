<?php

namespace App\Filament\Admin\Resources\ExcoMembers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ExcoMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('photo_path')->circular()->disk(\App\Support\MediaDisk::name()),
                TextColumn::make('full_name')->searchable()->sortable(),
                TextColumn::make('position')->badge(),
                IconColumn::make('is_current')->boolean()->label('Current'),
                TextColumn::make('term_started_on')->date('d M Y')->toggleable(),
                TextColumn::make('term_ends_on')->date('d M Y')->toggleable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_current'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
