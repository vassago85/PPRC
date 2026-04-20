<?php

namespace App\Filament\Admin\Resources\ExcoMembers\Pages;

use App\Filament\Admin\Resources\ExcoMembers\ExcoMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExcoMembers extends ListRecords
{
    protected static string $resource = ExcoMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
