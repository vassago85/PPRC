<?php

namespace App\Filament\Admin\Actions;

use App\Enums\MembershipStatus;
use App\Filament\Admin\Support\LapsedActivationWarning;
use App\Models\Membership;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/**
 * Status and period changes are the overwhelming majority of membership edits,
 * so they get a small modal straight off the list instead of a round trip
 * through the full edit form.
 */
class QuickEditMembershipAction
{
    public static function make(string $name = 'quick_edit'): Action
    {
        return Action::make($name)
            ->label('Quick edit')
            ->icon('heroicon-o-bolt')
            ->color('gray')
            ->modalWidth('md')
            ->modalHeading(fn (Membership $record) => 'Quick edit — '
                .($record->member?->fullName() ?? 'membership'))
            ->modalSubmitActionLabel('Save')
            ->visible(fn () => auth()->user()?->can('memberships.manage'))
            ->fillForm(fn (Membership $record) => [
                'status' => $record->status?->value,
                'period_start' => $record->period_start,
                'period_end' => $record->period_end,
            ])
            ->schema([
                Select::make('status')
                    ->options(collect(MembershipStatus::cases())
                        ->mapWithKeys(fn (MembershipStatus $c) => [$c->value => $c->label()])->all())
                    ->required()
                    ->native(false),
                DatePicker::make('period_start')->required()->native(false),
                DatePicker::make('period_end')
                    ->native(false)
                    ->helperText('Leave empty for a membership that never expires.'),
            ])
            ->action(function (Membership $record, array $data): void {
                $record->update([
                    'status' => $data['status'],
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                ]);

                Notification::make()->success()
                    ->title('Membership updated')
                    ->body($record->effectiveStatus()?->label().' until '
                        .($record->isLifetime()
                            ? 'further notice'
                            : $record->period_end?->format('d M Y') ?? 'further notice'))
                    ->send();

                LapsedActivationWarning::notify($record);
            });
    }
}
