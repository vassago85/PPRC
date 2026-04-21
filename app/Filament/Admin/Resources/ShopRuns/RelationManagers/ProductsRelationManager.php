<?php

namespace App\Filament\Admin\Resources\ShopRuns\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Products';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(180)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($set, $get, ?string $state): void {
                        if (filled($state) && blank($get('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(120)
                    ->helperText('Unique within this run.'),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('price_cents')
                    ->label('Price (cents)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Example: R450.00 → enter 45000'),
                TextInput::make('currency')
                    ->default('ZAR')
                    ->maxLength(8),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                TextInput::make('max_per_order')
                    ->label('Max per order')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),
                Toggle::make('is_active')
                    ->default(true)
                    ->inline(false),
                FileUpload::make('image_path')
                    ->label('Image')
                    ->image()
                    ->disk('s3')
                    ->directory('shop/products')
                    ->imageEditor()
                    ->maxSize(5120)
                    ->nullable(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('s3')
                    ->square()
                    ->toggleable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug')->toggleable(),
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state): string => 'R '.number_format($state / 100, 2)),
                TextColumn::make('max_per_order')->label('Max'),
                TextColumn::make('is_active')->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['shop_run_id'] = $this->getOwnerRecord()->getKey();

        return $data;
    }
}
