<?php

namespace App\Filament\Admin\Resources\MembershipTypes\Pages;

use App\Filament\Admin\Resources\MembershipTypes\MembershipTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMembershipType extends CreateRecord
{
    protected static string $resource = MembershipTypeResource::class;
}
