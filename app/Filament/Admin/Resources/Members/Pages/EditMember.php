<?php

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Filament\Admin\Resources\Members\MemberResource;
use App\Models\Member;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected static ?string $title = 'Edit details';

    /**
     * The record page owns the primary breadcrumb; edit adds one more crumb
     * so an admin knows which tab of the record page they are on.
     */
    public function getBreadcrumbs(): array
    {
        /** @var Member $member */
        $member = $this->record;

        return [
            MemberResource::getUrl('index') => 'Members',
            MemberResource::getUrl('view', ['record' => $member]) => $member->fullName(),
            'Edit details',
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Change name, contact, membership dates, disciplines and admin notes.';
    }

    /**
     * Header carries only the safe actions. Delete is deliberately absent and
     * lives in the "Danger zone" at the bottom of the page, behind a
     * type-the-member-name confirm.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_record')
                ->label('Back to record')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => MemberResource::getUrl('view', ['record' => $this->record])),
            ForceDeleteAction::make()
                ->modalHeading('Permanently delete this member?')
                ->visible(fn () => $this->record->trashed()),
            RestoreAction::make(),
        ];
    }

    /**
     * The one place delete is allowed to live. Sits at the bottom of the
     * page next to Save / Cancel; requires the admin to type the member's
     * full name so misclicks cannot cost a record.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            $this->deleteMemberAction(),
        ];
    }

    protected function deleteMemberAction(): Action
    {
        return Action::make('delete_member')
            ->label('Delete this member')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete this member?')
            ->modalDescription(function () {
                /** @var Member $m */
                $m = $this->record;

                return 'This soft-deletes the record. Type "'.$m->fullName().'" to confirm.';
            })
            ->schema([
                TextInput::make('confirm_name')
                    ->label('Type the member\'s full name')
                    ->required()
                    ->rule(function () {
                        /** @var Member $m */
                        $m = $this->record;
                        $expected = $m->fullName();

                        return function (string $attribute, $value, \Closure $fail) use ($expected) {
                            if (trim((string) $value) !== $expected) {
                                $fail('The name you typed does not match. This is on purpose — misclicks should not delete a member.');
                            }
                        };
                    }),
            ])
            ->action(function () {
                /** @var Member $m */
                $m = $this->record;
                $m->delete();

                Notification::make()->success()->title('Member deleted')->send();

                $this->redirect(MemberResource::getUrl('index'));
            });
    }

    /**
     * Pre-populate the email field with the linked user's email so admins
     * can edit it inline alongside the rest of the member's details.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Member $member */
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
        /** @var Member $member */
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
