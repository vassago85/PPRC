<?php

namespace App\Filament\Admin\Resources\Members\Tables;

use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Mail\MemberWelcomeInvite;
use App\Models\EmailLog;
use App\Models\Member;
use App\Models\MembershipPayment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('profile_photo_path')
                    ->label('')
                    ->disk(\App\Support\MediaDisk::name())
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=PPRC&background=64748b&color=fff'),
                TextColumn::make('membership_number')->label('Number')->badge()->sortable()->searchable(),
                TextColumn::make('first_name')->label('Name')
                    ->formatStateUsing(fn ($record) => $record->fullName())
                    ->sortable(['last_name', 'first_name'])
                    ->searchable(['first_name', 'last_name', 'known_as']),
                TextColumn::make('user.email')->searchable()->copyable(),
                TextColumn::make('phone_number')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?MemberStatus $state) => $state?->label())
                    ->color(fn (?MemberStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('join_date')->date('d M Y')->toggleable(),
                TextColumn::make('expiry_date')->date('d M Y')
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date->isPast() ? 'danger' : null)
                    ->sortable(),

                TextColumn::make('latest_payment_reference')
                    ->label('Latest payment ref')
                    ->state(fn (Member $r) => $r->latestPayment()?->reference)
                    ->copyable()
                    ->copyMessage('Reference copied')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('memberships.payments', fn ($q) => $q
                            ->where('reference', 'like', "%{$search}%"));
                    }),

                TextColumn::make('latest_payment_status')
                    ->label('Last payment')
                    ->state(fn (Member $r) => $r->latestPayment()?->status)
                    ->badge()
                    ->formatStateUsing(fn (?PaymentStatus $state) => $state?->label() ?? '—')
                    ->color(fn (?PaymentStatus $state) => $state?->color() ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('saprf_membership_number')->label('SAPRF #')->toggleable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(MemberStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                        ->all()),
            ])
            ->recordActions([
                Action::make('send_welcome')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send welcome email')
                    ->modalDescription(fn (Member $record) => "Send the account-claim invite to {$record->user?->email}?")
                    ->visible(fn (Member $record) => $record->user !== null)
                    ->action(function (Member $record) {
                        self::sendWelcomeTo($record);
                        Notification::make()->success()->title('Welcome email sent')->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('send_welcome_bulk')
                        ->label('Send welcome emails')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Send welcome emails')
                        ->modalDescription('Send the account-claim invite to all selected members who haven\'t received one yet?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $sent = 0;
                            $skipped = 0;
                            foreach ($records as $member) {
                                if (! $member->user) {
                                    $skipped++;
                                    continue;
                                }
                                if (self::hasAlreadyBeenWelcomed($member->user->email)) {
                                    $skipped++;
                                    continue;
                                }
                                self::sendWelcomeTo($member);
                                $sent++;
                            }
                            Notification::make()->success()
                                ->title("Sent {$sent} welcome email(s)" . ($skipped ? ", skipped {$skipped}" : ''))
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function sendWelcomeTo(Member $member): void
    {
        $user = $member->user;
        if (! $user) {
            return;
        }

        try {
            $token = Password::broker()->createToken($user);
            $setupUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], absolute: false));

            Mail::to($user->email, $user->name)->send(new MemberWelcomeInvite(
                user: $user,
                setupUrl: $setupUrl,
                firstName: $member->first_name ?: null,
            ));
        } catch (\Throwable $e) {
            EmailLog::create([
                'user_id' => $user->id,
                'to_email' => $user->email,
                'to_name' => $user->name,
                'subject' => 'Welcome to Pretoria Precision Rifle Club — claim your account',
                'mailable_class' => MemberWelcomeInvite::class,
                'status' => EmailLog::STATUS_FAILED,
                'error' => $e->getMessage(),
                'context' => ['source' => 'filament-admin'],
            ]);

            Notification::make()->danger()
                ->title("Failed to send to {$user->email}")
                ->body($e->getMessage())
                ->send();
        }
    }

    private static function hasAlreadyBeenWelcomed(string $email): bool
    {
        return EmailLog::query()
            ->where('to_email', $email)
            ->where('mailable_class', MemberWelcomeInvite::class)
            ->where('status', EmailLog::STATUS_SENT)
            ->exists();
    }
}
