<?php

namespace App\Filament\Admin\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup('category')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('category')->badge()->toggleable(),
                TextColumn::make('question')->limit(90)->searchable()->wrap(),
                IconColumn::make('is_published')->boolean()->label('Live'),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->options([
                    'general' => 'General',
                    'membership' => 'Membership',
                    'matches' => 'Matches',
                    'safety' => 'Safety',
                    'payments' => 'Payments',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
