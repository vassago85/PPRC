<?php

namespace App\Filament\Admin\Resources\MatchCredits\Pages;

use App\Enums\MatchCreditStatus;
use App\Filament\Admin\Resources\MatchCredits\MatchCreditResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMatchCredit extends EditRecord
{
    protected static string $resource = MatchCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === MatchCreditStatus::Used->value && empty($data['used_at'])) {
            $data['used_at'] = now();
        }

        return $data;
    }
}
