<?php

namespace App\Filament\Admin\Resources\ShopOrders\Schemas;

use App\Enums\ShopOrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShopOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->columns(2)
                ->schema([
                    TextInput::make('run_display')
                        ->label('Run')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('user_display')
                        ->label('Member account')
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('status')
                        ->options(collect(ShopOrderStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                        ->required()
                        ->disabled(fn () => ! auth()->user()?->can('shop.orders.manage')),
                    TextInput::make('totals_display')
                        ->label('Totals')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    TextInput::make('eft_display')
                        ->label('EFT reference')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('proof_display')
                        ->label('Proof path (S3)')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Textarea::make('lines_display')
                        ->label('Line items')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(6)
                        ->columnSpanFull(),
                ]),
            Section::make('Shipping')
                ->columns(2)
                ->schema([
                    TextInput::make('ship_to_name')->label('Name')->disabled()->dehydrated(false),
                    TextInput::make('ship_phone')->label('Phone')->disabled()->dehydrated(false),
                    TextInput::make('ship_line1')->label('Line 1')->disabled()->dehydrated(false)->columnSpanFull(),
                    TextInput::make('ship_line2')->label('Line 2')->disabled()->dehydrated(false)->columnSpanFull(),
                    TextInput::make('ship_city')->label('City')->disabled()->dehydrated(false),
                    TextInput::make('ship_province')->label('Province')->disabled()->dehydrated(false),
                    TextInput::make('ship_postal_code')->label('Postal code')->disabled()->dehydrated(false),
                    TextInput::make('ship_country')->label('Country')->disabled()->dehydrated(false),
                ]),
        ]);
    }
}
