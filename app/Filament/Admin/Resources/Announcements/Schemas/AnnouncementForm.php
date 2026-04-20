<?php

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Announcement')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, $get) => $get('slug') ? null : $set('slug', Str::slug((string) $state))),
                    TextInput::make('slug')->required()->alphaDash()->maxLength(150)->unique(ignoreRecord: true),
                    Textarea::make('excerpt')->rows(2)->columnSpanFull(),
                    RichEditor::make('body')->columnSpanFull(),
                ]),
            Section::make('Publishing')
                ->columns(2)
                ->schema([
                    Toggle::make('is_pinned')->default(false),
                    Toggle::make('is_published')->default(false),
                    DateTimePicker::make('published_at')->seconds(false),
                    DateTimePicker::make('expires_at')->seconds(false),
                    Select::make('author_id')->relationship('author', 'name')->searchable()->columnSpanFull(),
                ]),
        ]);
    }
}
