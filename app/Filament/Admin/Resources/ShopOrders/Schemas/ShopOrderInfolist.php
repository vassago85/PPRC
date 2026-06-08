<?php

namespace App\Filament\Admin\Resources\ShopOrders\Schemas;

use App\Models\ShopOrder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShopOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Order')
                ->columns(2)
                ->schema([
                    TextEntry::make('run.title')
                        ->label('Run')
                        ->placeholder('—'),
                    TextEntry::make('user.name')
                        ->label('Member account')
                        ->placeholder('—'),
                    TextEntry::make('subtotal_cents')
                        ->label('Totals')
                        ->formatStateUsing(fn (int $state, ShopOrder $record): string => 'Subtotal R '.number_format($state / 100, 2)
                            .' · Total R '.number_format($record->total_cents / 100, 2))
                        ->columnSpanFull(),
                    TextEntry::make('eft_reference')
                        ->label('EFT reference')
                        ->placeholder('—'),
                    TextEntry::make('proof_path')
                        ->label('Proof path (S3)')
                        ->placeholder('—')
                        ->columnSpanFull(),
                    TextEntry::make('lines')
                        ->label('Line items')
                        ->formatStateUsing(function ($state, ShopOrder $record): string {
                            $record->loadMissing('lines.product');

                            if ($record->lines->isEmpty()) {
                                return '—';
                            }

                            return $record->lines->map(function ($line): string {
                                $n = $line->product?->name ?? 'Item';

                                return "{$n} × {$line->quantity} @ R ".number_format($line->unit_price_cents / 100, 2)
                                    .' = R '.number_format($line->line_total_cents / 100, 2);
                            })->implode("\n");
                        })
                        ->listWithLineBreaks()
                        ->columnSpanFull(),
                ]),
            Section::make('Shipping')
                ->columns(2)
                ->schema([
                    TextEntry::make('ship_to_name')->label('Name')->placeholder('—'),
                    TextEntry::make('ship_phone')->label('Phone')->placeholder('—'),
                    TextEntry::make('ship_line1')->label('Line 1')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('ship_line2')->label('Line 2')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('ship_city')->label('City')->placeholder('—'),
                    TextEntry::make('ship_province')->label('Province')->placeholder('—'),
                    TextEntry::make('ship_postal_code')->label('Postal code')->placeholder('—'),
                    TextEntry::make('ship_country')->label('Country')->placeholder('—'),
                ]),
        ]);
    }
}
