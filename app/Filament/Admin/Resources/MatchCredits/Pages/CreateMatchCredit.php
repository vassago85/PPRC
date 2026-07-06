<?php

namespace App\Filament\Admin\Resources\MatchCredits\Pages;

use App\Enums\MatchCreditStatus;
use App\Filament\Admin\Resources\MatchCredits\MatchCreditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMatchCredit extends CreateRecord
{
    protected static string $resource = MatchCreditResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();

        if (($data['status'] ?? null) === MatchCreditStatus::Used->value && empty($data['used_at'])) {
            $data['used_at'] = now();
        }

        return $data;
    }
}
