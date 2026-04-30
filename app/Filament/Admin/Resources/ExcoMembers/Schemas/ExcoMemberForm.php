<?php

namespace App\Filament\Admin\Resources\ExcoMembers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExcoMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Person')
                ->columns(2)
                ->schema([
                    TextInput::make('full_name')->required()->maxLength(150),
                    TextInput::make('position')->required()->maxLength(120)
                        ->placeholder('Chairperson, Treasurer, Secretary…'),
                    FileUpload::make('photo_path')
                        ->label('Photo')
                        ->image()
                        ->disk(\App\Support\MediaDisk::name())
                        ->directory('exco')
                        ->columnSpanFull(),
                    Textarea::make('bio')->rows(4)->columnSpanFull(),
                ]),
            Section::make('Contact (optional)')
                ->columns(2)
                ->schema([
                    TextInput::make('email')->email()->maxLength(190),
                    TextInput::make('phone')->tel()->maxLength(40),
                    Select::make('linked_user_id')
                        ->relationship('linkedUser', 'name')
                        ->searchable()
                        ->label('Linked user account')
                        ->columnSpanFull(),
                ]),
            Section::make('Term')
                ->columns(3)
                ->schema([
                    DatePicker::make('term_started_on'),
                    DatePicker::make('term_ends_on'),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Toggle::make('is_current')->default(true)->columnSpanFull(),
                ]),
        ]);
    }
}
