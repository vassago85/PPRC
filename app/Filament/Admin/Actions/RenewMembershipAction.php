<?php

namespace App\Filament\Admin\Actions;

use App\Enums\MembershipStatus;
use App\Enums\RenewalSource;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Services\Membership\MemberService;
use App\Services\Membership\RenewalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Roll a member into a fresh period without retyping dates. RenewalService
 * works out the start date, stacking onto the previous period when the member
 * renews inside the renewal window so they don't lose paid time.
 */
class RenewMembershipAction
{
    public static function make(string $name = 'renew'): Action
    {
        return Action::make($name)
            ->label('Renew')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->modalWidth('md')
            ->modalHeading(fn (Membership $record) => 'Renew '
                .($record->member?->fullName() ?? 'membership'))
            ->modalSubmitActionLabel('Create renewal')
            ->modalDescription(fn (Membership $record) => static::describe($record))
            ->visible(fn (Membership $record) => static::isRenewable($record))
            ->fillForm(fn (Membership $record) => [
                'membership_type_id' => $record->membership_type_id,
            ])
            ->schema([
                Select::make('membership_type_id')
                    ->label('Renew into')
                    ->options(fn () => MembershipType::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->pluck('name', 'id')
                        ->all())
                    ->required()
                    ->native(false),
                Checkbox::make('activate')
                    ->label('Activate immediately')
                    ->helperText('Leave unticked to raise it as pending payment or approval, as a member-initiated renewal would.'),
            ])
            ->action(function (Membership $record, array $data): void {
                $member = $record->member;

                if (! $member) {
                    Notification::make()->danger()
                        ->title('No member attached to this membership')
                        ->send();

                    return;
                }

                $type = MembershipType::find($data['membership_type_id']);

                if (! $type) {
                    return;
                }

                try {
                    $renewal = app(RenewalService::class)
                        ->renew($member, $type, null, RenewalSource::Admin);
                } catch (ValidationException $e) {
                    Notification::make()->danger()
                        ->title('Could not renew')
                        ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                        ->send();

                    return;
                }

                if ($data['activate'] ?? false) {
                    app(MemberService::class)->activate($renewal, auth()->user());
                    $renewal->refresh();
                }

                Notification::make()->success()
                    ->title('Renewal created')
                    ->body($type->name.' — '.$renewal->period_start->format('d M Y').' to '
                        .($renewal->period_end?->format('d M Y') ?? 'no expiry')
                        .' ('.$renewal->status->label().')')
                    ->send();
            });
    }

    public static function isRenewable(Membership $membership): bool
    {
        if (! auth()->user()?->can('memberships.manage')) {
            return false;
        }

        // Only offered on the memberships a renewal would actually follow on
        // from; pending rows are still being resolved, and lifetime members
        // have nothing to renew.
        return in_array($membership->status, [MembershipStatus::Active, MembershipStatus::Expired], true)
            && ! $membership->isLifetime();
    }

    private static function describe(Membership $membership): string
    {
        $ends = $membership->period_end?->format('d M Y') ?? 'no expiry';

        return "Current period ends {$ends}. If that is still within the renewal window "
            .'the new period starts the day after, so no paid time is lost.';
    }
}
