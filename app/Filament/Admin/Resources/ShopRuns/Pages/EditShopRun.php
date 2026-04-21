<?php

namespace App\Filament\Admin\Resources\ShopRuns\Pages;

use App\Enums\ShopRunStatus;
use App\Filament\Admin\Resources\ShopRuns\ShopRunResource;
use App\Jobs\SendShopRunOpenedToWaitlist;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditShopRun extends EditRecord
{
    protected static string $resource = ShopRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('notifyWaitlist')
                ->label('Email waitlist')
                ->icon('heroicon-o-envelope')
                ->visible(fn (): bool => $this->record->status === ShopRunStatus::Open
                    && $this->record->isAcceptingOrders())
                ->authorize('notifyWaitlist', $this->record)
                ->requiresConfirmation()
                ->modalHeading('Email confirmed waitlist subscribers?')
                ->modalDescription('One email per subscriber is queued. Large lists may take several minutes to finish sending.')
                ->action(function (): void {
                    SendShopRunOpenedToWaitlist::dispatch($this->record->id);
                    Notification::make()
                        ->title('Waitlist emails queued')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
