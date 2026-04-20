<?php

namespace App\Filament\Admin\Resources\MembershipTypes\Pages;

use App\Filament\Admin\Resources\MembershipTypes\MembershipTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMembershipTypes extends ListRecords
{
    protected static string $resource = MembershipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
