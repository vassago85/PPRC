<?php

namespace App\Filament\Admin\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Models\MatchFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Match details')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, $get) =>
                            $get('slug') ? null : $set('slug', Str::slug((string) $state))),

                    TextInput::make('slug')
                        ->required()
                        ->alphaDash()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),

                    Select::make('match_format_id')
                        ->label('Format')
                        ->options(fn () => MatchFormat::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                            ->all())
                        ->required()
                        ->helperText('PRS (centerfire) or PR22 match.'),

                    Select::make('status')
                        ->options(collect(EventStatus::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                            ->all())
                        ->default(EventStatus::Draft->value)
                        ->required(),

                    Textarea::make('summary')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('One-line summary shown on listings and cards.')
                        ->columnSpanFull(),

                    FileUpload::make('banner_path')
                        ->label('Banner image')
                        ->image()
                        ->imageEditor()
                        ->disk('s3')
                        ->directory('events/banners')
                        ->maxSize(5120)
                        ->helperText('Shown on the public match page. Landscape works best (≥ 1600×900).')
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Full description')
                        ->columnSpanFull(),
                ]),

            Section::make('When')
                ->columns(3)
                ->schema([
                    DatePicker::make('start_date')->required()->native(false),
                    TimePicker::make('start_time')->seconds(false),
                    DatePicker::make('end_date')->native(false),
                ]),

            Section::make('Where')
                ->columns(2)
                ->schema([
                    TextInput::make('location_name')->label('Venue name')->maxLength(150),
                    TextInput::make('location_address')->label('Address')->maxLength(250),
                    TextInput::make('location_lat')->label('Latitude')->numeric(),
                    TextInput::make('location_lng')->label('Longitude')->numeric(),
                ]),

            Section::make('Entry')
                ->columns(3)
                ->schema([
                    TextInput::make('member_price_cents')
                        ->label('Member price (ZAR)')
                        ->numeric()
                        ->prefix('R')
                        ->helperText('Charged to active PPRC members.')
                        ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                            ? null
                            : (int) round(((float) $state) * 100))
                        ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100),
                    TextInput::make('non_member_price_cents')
                        ->label('Non-member price (ZAR)')
                        ->numeric()
                        ->prefix('R')
                        ->helperText('Charged to guests, expired members, and non-PPRC shooters.')
                        ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                            ? null
                            : (int) round(((float) $state) * 100))
                        ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100),
                    TextInput::make('max_entries')->numeric()->label('Max entries'),
                    TextInput::make('round_count')->numeric()->label('Round count'),
                    Toggle::make('registrations_open')->inline(false),
                    DateTimePicker::make('registrations_close_at')
                        ->label('Registrations close at')
                        ->seconds(false),
                ]),

            Section::make('Ownership & publishing')
                ->columns(2)
                ->schema([
                    TextInput::make('match_director_name')
                        ->label('Match director')
                        ->maxLength(150)
                        ->helperText('Any name shown on listings and the public match page. Does not need to be a user in this system.')
                        ->columnSpanFull(),
                    DateTimePicker::make('published_at')
                        ->seconds(false)
                        ->helperText('Optional. If you leave this empty and set status to Published or Completed, a publish time is recorded automatically when you save.'),
                    DateTimePicker::make('results_published_at')->seconds(false),
                ]),
        ]);
    }
}
