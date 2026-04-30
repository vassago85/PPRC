<?php

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Filament\Admin\Resources\Members\MemberResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Pre-populate the email field with the linked user's email so admins
     * can edit it inline alongside the rest of the member's details.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var \App\Models\Member $member */
        $member = $this->record;
        $data['email'] = $member->user?->email;

        return $data;
    }

    /**
     * Apply email and display-name changes to the linked user record before
     * saving the member. The User model lowercases on save, so we just hand
     * it whatever the admin typed.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var \App\Models\Member $member */
        $member = $this->record;
        $user = $member->user;

        if (! $user) {
            return $data;
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email !== '' && $email !== $user->email) {
            $taken = User::where('email', $email)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages([
                    'data.email' => 'Another user already uses that email.',
                ]);
            }

            $user->email = $email;
        }

        $newName = trim(($data['first_name'] ?? $member->first_name).' '.($data['last_name'] ?? $member->last_name));
        if ($newName !== '' && $newName !== $user->name) {
            $user->name = $newName;
        }

        if ($user->isDirty()) {
            $user->save();
        }

        unset($data['email']);

        return $data;
    }
}
