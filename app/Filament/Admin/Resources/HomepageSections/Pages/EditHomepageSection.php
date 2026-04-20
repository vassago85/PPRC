<?php

namespace App\Filament\Admin\Resources\HomepageSections\Pages;

use App\Filament\Admin\Resources\HomepageSections\HomepageSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomepageSection extends EditRecord
{
    protected static string $resource = HomepageSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
