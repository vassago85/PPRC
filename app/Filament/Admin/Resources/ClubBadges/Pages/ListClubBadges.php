<?php

namespace App\Filament\Admin\Resources\ClubBadges\Pages;

use App\Filament\Admin\Resources\ClubBadges\ClubBadgeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClubBadges extends ListRecords
{
    protected static string $resource = ClubBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
