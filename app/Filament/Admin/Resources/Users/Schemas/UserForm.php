<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(150),
                    TextInput::make('email')->required()->email()->maxLength(150)->unique(ignoreRecord: true),
                    DateTimePicker::make('email_verified_at')
                        ->label('Email verified at')
                        ->seconds(false)
                        ->helperText('Clear this field to force re-verification.'),
                ]),

            Section::make('Roles')
                ->description('Spatie roles control admin access and member perks (e.g. free match entry). Only the Chair (or a developer account) can change role assignments — everyone else sees this section hidden.')
                ->visible(fn () => (bool) auth()->user()?->can('settings.roles.assign'))
                ->schema([
                    CheckboxList::make('roles')
                        ->relationship('roles', 'name')
                        ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'id'))
                        ->bulkToggleable()
                        ->columns(2)
                        ->searchable(),
                ]),
        ]);
    }
}
