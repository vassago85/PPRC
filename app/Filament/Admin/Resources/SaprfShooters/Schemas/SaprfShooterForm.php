<?php

namespace App\Filament\Admin\Resources\SaprfShooters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SaprfShooterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('membership_number')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(64),
                TextInput::make('first_name')->maxLength(80),
                TextInput::make('last_name')->maxLength(80),
                TextInput::make('email')->email()->maxLength(160),
                DatePicker::make('verified_on')->native(false),
                Textarea::make('notes')->columnSpanFull()->rows(2),
            ])->columns(2);
    }
}
