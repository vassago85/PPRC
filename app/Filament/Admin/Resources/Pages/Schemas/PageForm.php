<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, $get) => $get('slug') ? null : $set('slug', Str::slug((string) $state))),
                    TextInput::make('slug')
                        ->required()
                        ->alphaDash()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true)
                        ->helperText('Used in the URL: /{slug}'),
                    TextInput::make('subtitle')->maxLength(255)->columnSpanFull(),
                    Textarea::make('excerpt')->rows(2)->columnSpanFull(),
                    RichEditor::make('body')->columnSpanFull(),
                    FileUpload::make('hero_image_path')
                        ->label('Hero image')
                        ->image()
                        ->disk('s3')
                        ->directory('pages/hero')
                        ->maxSize(5120)
                        ->columnSpanFull(),
                ]),

            Section::make('SEO')
                ->columns(2)
                ->schema([
                    TextInput::make('meta_title')->maxLength(200),
                    TextInput::make('meta_description')->maxLength(300),
                ]),

            Section::make('Publishing')
                ->columns(2)
                ->schema([
                    Toggle::make('is_published')->default(false),
                    DateTimePicker::make('published_at')->seconds(false),
                    Toggle::make('show_in_nav')->default(false),
                    TextInput::make('nav_sort_order')->numeric()->default(0),
                    Select::make('author_id')
                        ->relationship('author', 'name')
                        ->searchable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
