<?php

namespace App\Filament\Admin\Resources\Memberships\Pages;

use App\Enums\MembershipStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Models\MembershipPayment;
use App\Services\Membership\PaymentReferenceGenerator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMembership extends CreateRecord
{
    protected static string $resource = MembershipResource::class;

    /**
     * When an admin manually creates a membership in `pending_payment`,
     * generate the EFT reference up-front so the treasurer can see it
     * (and tell the member what to use) without waiting for the member
     * to log in to the portal first.
     */
    protected function afterCreate(): void
    {
        /** @var \App\Models\Membership $record */
        $record = $this->record;

        if ($record->status !== MembershipStatus::PendingPayment) {
            return;
        }

        if ($record->payments()->exists()) {
            return;
        }

        $reference = app(PaymentReferenceGenerator::class)->generate();

        MembershipPayment::create([
            'membership_id' => $record->id,
            'provider' => PaymentProvider::ManualEft->value,
            'status' => PaymentStatus::Pending->value,
            'amount_cents' => $record->price_cents_snapshot
                ?? $record->membershipType?->price_cents
                ?? 0,
            'currency' => 'ZAR',
            'reference' => $reference,
        ]);

        Notification::make()->info()
            ->title('EFT reference generated')
            ->body("Reference {$reference} is ready for the member to pay against.")
            ->send();
    }
}
