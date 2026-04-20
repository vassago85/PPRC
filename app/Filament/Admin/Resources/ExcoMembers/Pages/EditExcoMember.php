<?php

namespace App\Filament\Admin\Resources\ExcoMembers\Pages;

use App\Filament\Admin\Resources\ExcoMembers\ExcoMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditExcoMember extends EditRecord
{
    protected static string $resource = ExcoMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
