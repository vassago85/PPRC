<?php

namespace App\Filament\Admin\Resources\Faqs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')
                ->options([
                    'general' => 'General',
                    'membership' => 'Membership',
                    'matches' => 'Matches',
                    'safety' => 'Safety',
                    'payments' => 'Payments',
                ])
                ->default('general')
                ->required()
                ->native(false),
            TextInput::make('question')->required()->maxLength(300)->columnSpanFull(),
            Textarea::make('answer')->required()->rows(5)->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_published')->default(true),
        ]);
    }
}
