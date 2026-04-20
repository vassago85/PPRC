<?php

namespace App\Filament\Admin\Resources\SaprfShooters\Pages;

use App\Filament\Admin\Resources\SaprfShooters\SaprfShooterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSaprfShooter extends EditRecord
{
    protected static string $resource = SaprfShooterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
