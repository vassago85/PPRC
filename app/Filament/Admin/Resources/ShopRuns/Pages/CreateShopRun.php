<?php

namespace App\Filament\Admin\Resources\ShopRuns\Pages;

use App\Filament\Admin\Resources\ShopRuns\ShopRunResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateShopRun extends CreateRecord
{
    protected static string $resource = ShopRunResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        return $data;
    }
}
