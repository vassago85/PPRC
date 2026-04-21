<?php

namespace App\Filament\Admin\Resources\ShopRuns\Pages;

use App\Filament\Admin\Resources\ShopRuns\ShopRunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShopRuns extends ListRecords
{
    protected static string $resource = ShopRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
