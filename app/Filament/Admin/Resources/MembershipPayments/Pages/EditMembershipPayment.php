<?php

namespace App\Filament\Admin\Resources\MembershipPayments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Models\MembershipPayment;
use App\Services\Membership\MemberService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMembershipPayment extends EditRecord
{
    protected static string $resource = MembershipPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmAndActivate')
                ->label('Confirm + activate membership')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(function () {
                    /** @var MembershipPayment $r */
                    $r = $this->record;

                    return auth()->user()?->can('payments.eft.confirm')
                        && in_array($r->status, [PaymentStatus::Pending, PaymentStatus::Submitted], true);
                })
                ->requiresConfirmation()
                ->action(function () {
                    /** @var MembershipPayment $r */
                    $r = $this->record;

                    if (! $r->membership) {
                        Notification::make()->danger()->title('No membership attached')->send();

                        return;
                    }

                    app(MemberService::class)->activate($r->membership, auth()->user());

                    Notification::make()->success()
                        ->title('Payment confirmed and membership activated')
                        ->send();

                    $this->refreshFormData(['status', 'confirmed_at', 'confirmed_by_user_id']);
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $note = trim((string) ($data['notes'] ?? ''));
        if ($note !== '') {
            /** @var MembershipPayment $r */
            $r = $this->record;
            $meta = $r->meta?->toArray() ?? [];
            $meta['notes'] = $meta['notes'] ?? [];
            $meta['notes'][] = [
                'note' => $note,
                'by_user_id' => auth()->id(),
                'at' => now()->toIso8601String(),
            ];
            $data['meta'] = $meta;
        }

        unset($data['notes']);

        return $data;
    }
}
