<?php

namespace App\Filament\Admin\Resources\HomepageSections\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomepageSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Section')
                ->columns(2)
                ->schema([
                    TextInput::make('key')
                        ->required()
                        ->alphaDash()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true)
                        ->helperText('Stable identifier used by the site renderer: hero, stats, features, etc.'),
                    Select::make('type')
                        ->required()
                        ->options([
                            'hero' => 'Hero',
                            'stats' => 'Stats strip',
                            'feature_grid' => 'Feature grid',
                            'events_teaser' => 'Upcoming events teaser',
                            'announcements' => 'Announcements feed',
                            'cta' => 'Call to action',
                            'rich_text' => 'Rich text block',
                        ]),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Toggle::make('is_active')->default(true),
                ]),

            Section::make('Content')
                ->columns(2)
                ->schema([
                    TextInput::make('eyebrow')->maxLength(160),
                    TextInput::make('title')->maxLength(200),
                    TextInput::make('subtitle')->maxLength(255)->columnSpanFull(),
                    Textarea::make('body')->rows(4)->columnSpanFull(),
                    FileUpload::make('image_path')
                        ->label('Image')
                        ->image()
                        ->disk('s3')
                        ->directory('homepage')
                        ->columnSpanFull(),
                    TextInput::make('cta_label')->label('CTA label'),
                    TextInput::make('cta_url')->label('CTA url'),
                    KeyValue::make('meta')
                        ->label('Extra data')
                        ->helperText('Used for feature grid items, stats items, etc.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
