<?php

namespace App\Filament\Admin\Resources\MembershipTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MembershipTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable()->toggleable(),
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('slug')->badge()->color('gray')->toggleable(),
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => 'R '.number_format($state / 100, 2))
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('duration_months')->label('Months')->alignCenter()->toggleable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                IconColumn::make('show_on_registration')->boolean()->label('Public')->toggleable(),
                IconColumn::make('requires_manual_approval')->boolean()->label('Manual')->toggleable(),
                IconColumn::make('counts_as_member')->boolean()->label('Counts')->toggleable(),
                IconColumn::make('allows_sub_members')->boolean()->label('Parent')->toggleable(),
                TextColumn::make('age_requirement_type')
                    ->label('Age rule')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, $record) => match ($record->age_requirement_type?->value) {
                        'under' => "Under {$record->age_max}",
                        'at_least' => "At least {$record->age_min}",
                        'between' => "{$record->age_min}–{$record->age_max}",
                        default => '—',
                    })
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
                TernaryFilter::make('show_on_registration'),
                TernaryFilter::make('requires_manual_approval'),
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
