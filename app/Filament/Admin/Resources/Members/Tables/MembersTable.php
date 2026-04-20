<?php

namespace App\Filament\Admin\Resources\Members\Tables;

use App\Enums\MemberStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('profile_photo_path')
                    ->label('')
                    ->disk('s3')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=PPRC&background=64748b&color=fff'),
                TextColumn::make('membership_number')->label('Number')->badge()->sortable()->searchable(),
                TextColumn::make('first_name')->label('Name')
                    ->formatStateUsing(fn ($record) => $record->fullName())
                    ->sortable(['last_name', 'first_name'])
                    ->searchable(['first_name', 'last_name', 'known_as']),
                TextColumn::make('user.email')->searchable()->copyable(),
                TextColumn::make('phone_number')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?MemberStatus $state) => $state?->label())
                    ->color(fn (?MemberStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('join_date')->date('d M Y')->toggleable(),
                TextColumn::make('expiry_date')->date('d M Y')
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date->isPast() ? 'danger' : null)
                    ->sortable(),
                TextColumn::make('saprf_membership_number')->label('SAPRF #')->toggleable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(MemberStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                        ->all()),
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
