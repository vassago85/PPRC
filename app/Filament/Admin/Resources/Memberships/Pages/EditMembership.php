<?php

namespace App\Filament\Admin\Resources\Memberships\Pages;

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMembership extends EditRecord
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $membership = $this->record;

        if ($membership->status === MembershipStatus::Active || $membership->price_cents_snapshot === 0) {
            $membership->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update([
                    'status' => $membership->status === MembershipStatus::Active
                        ? PaymentStatus::Confirmed->value
                        : PaymentStatus::Cancelled->value,
                    'confirmed_at' => $membership->status === MembershipStatus::Active ? now() : null,
                    'confirmed_by_user_id' => auth()->id(),
                ]);
        }
    }
}
