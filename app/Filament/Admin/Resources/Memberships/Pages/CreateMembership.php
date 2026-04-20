<?php

namespace App\Filament\Admin\Resources\Memberships\Pages;

use App\Filament\Admin\Resources\Memberships\MembershipResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMembership extends CreateRecord
{
    protected static string $resource = MembershipResource::class;
}
