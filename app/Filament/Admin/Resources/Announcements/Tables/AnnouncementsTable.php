<?php

namespace App\Filament\Admin\Resources\Announcements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                IconColumn::make('is_pinned')->boolean()->label('Pin'),
                TextColumn::make('title')->searchable()->sortable(),
                IconColumn::make('is_published')->boolean()->label('Live'),
                TextColumn::make('published_at')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('expires_at')->dateTime('d M Y H:i')->toggleable(),
                TextColumn::make('author.name')->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_published'),
                TernaryFilter::make('is_pinned'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
