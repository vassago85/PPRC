<?php

namespace App\Filament\Admin\Resources\ShopRuns\Schemas;

use App\Enums\ShopRunStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShopRunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Run details')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(180)
                        ->columnSpanFull(),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true)
                        ->helperText('URL segment, e.g. spring-2026-hoodies'),
                    Select::make('status')
                        ->options(collect(ShopRunStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                        ->required()
                        ->native(false),
                    Toggle::make('preview_visible')
                        ->label('Show catalog on public shop while in preview')
                        ->inline(false),
                    Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('announcement')
                        ->label('Email blurb')
                        ->rows(2)
                        ->helperText('Short line included in waitlist “run opened” emails.')
                        ->columnSpanFull(),
                    DateTimePicker::make('orders_open_at')
                        ->label('Orders open at')
                        ->seconds(false),
                    DateTimePicker::make('orders_close_at')
                        ->label('Orders close at')
                        ->seconds(false),
                ]),
        ]);
    }
}
