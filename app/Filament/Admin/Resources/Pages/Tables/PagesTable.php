<?php

namespace App\Filament\Admin\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nav_sort_order')
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->toggleable(),
                IconColumn::make('is_published')->boolean()->label('Published'),
                IconColumn::make('show_in_nav')->boolean()->label('In nav'),
                TextColumn::make('nav_sort_order')->numeric()->sortable()->label('Order'),
                TextColumn::make('published_at')->dateTime('d M Y H:i')->sortable()->toggleable(),
                TextColumn::make('author.name')->toggleable(),
                TextColumn::make('updated_at')->dateTime('d M Y')->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_published'),
                TernaryFilter::make('show_in_nav'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
