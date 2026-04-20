<?php

namespace App\Filament\Admin\Resources\MembershipTypes\Pages;

use App\Filament\Admin\Resources\MembershipTypes\MembershipTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMembershipType extends EditRecord
{
    protected static string $resource = MembershipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
