<?php

namespace App\Filament\Admin\Resources\Members\Tables;

use App\Enums\MemberStanding;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Actions\ResendMembershipPaymentRequestAction;
use App\Filament\Admin\Support\SearchTerm;
use App\Mail\MemberWelcomeInvite;
use App\Models\EmailLog;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50)
            // Row click opens the record page; the legacy Edit URL is one
            // tab away.
            ->recordUrl(fn (Member $record) => route('filament.admin.resources.members.view', ['record' => $record]))
            // The derived status badge needs to know about the user account and
            // whether an application was ever started; without these it would
            // be two queries per row.
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user', 'linkedAdult')->withExists('memberships'))
            ->searchable([
                'membership_number',
                'first_name',
                'last_name',
                'known_as',
                'user.email',
            ])
            ->searchPlaceholder('Search name, email, or member #')
            // The default Filament "No records found" is fine when a filter
            // hides everyone, but a truly empty club — or a fresh install —
            // reads better with a first-run prompt than an X.
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('No members in this segment')
            ->emptyStateDescription('Once someone signs up or a segment applies, they will appear here.')
            ->columns([
                // Membership number: mono, aligned.
                TextColumn::make('membership_number')
                    ->label('No.')
                    ->fontFamily('mono')
                    ->state(fn (Member $r) => $r->formattedMembershipNumber() ?? '—')
                    ->sortable()
                    ->searchable(),

                // Name column with the email underneath. Placeholder addresses
                // (junior-{uuid}@members.pretoriaprc.co.za) are hidden — the
                // linked-adult line takes their place.
                ViewColumn::make('name')
                    ->label('Name')
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->orderBy('last_name', $direction)
                        ->orderBy('first_name', $direction))
                    ->searchable(['first_name', 'last_name', 'known_as'])
                    ->view('filament.admin.resources.members.columns.name'),

                TextColumn::make('current_membership_type')
                    ->label('Membership')
                    ->state(fn (Member $r) => $r->currentMembership()?->membershipType?->name ?? '—')
                    ->color('gray'),

                // Derived rather than the stored lifecycle, so the badge says
                // what the member is actually waiting on instead of just
                // "Pending".
                TextColumn::make('standing')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Member $record) => $record->standing())
                    ->formatStateUsing(fn (MemberStanding $state) => $state->label())
                    ->color(fn (MemberStanding $state) => $state->color())
                    ->tooltip(fn (MemberStanding $state) => $state->description()),

                TextColumn::make('shooting_disciplines')
                    ->label('Discipline')
                    ->badge()
                    ->separator(',')
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('join_date')
                    ->label('Joined')
                    ->date('d M Y')
                    ->sortable()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->date('d M Y')
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date->isPast() ? 'danger' : null)
                    ->placeholder('—')
                    ->sortable(),

                // The following two are hidden by default — they inflate the
                // roster width unless a treasurer needs them.
                TextColumn::make('latest_payment_reference')
                    ->label('Latest payment ref')
                    ->state(fn (Member $r) => $r->latestPayment()?->reference)
                    ->copyable()
                    ->copyMessage('Reference copied')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(query: function ($query, string $search) {
                        $term = SearchTerm::make($query, $search);

                        $query->whereHas('memberships.payments', fn ($q) => $q
                            ->where($term->column('reference'), 'like', $term->contains()));
                    }),

                TextColumn::make('latest_payment_status')
                    ->label('Last payment')
                    ->state(fn (Member $r) => $r->latestPayment()?->status)
                    ->badge()
                    ->formatStateUsing(fn (?PaymentStatus $state) => $state?->label() ?? '—')
                    ->color(fn (?PaymentStatus $state) => $state?->color() ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('saprf_membership_number')
                    ->label('SAPRF #')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->recordActions([
                // One primary action per row, chosen from the member's state.
                // Everything else lives behind the ⋯ group.
                self::primaryAction(),
                ActionGroup::make([
                    ResendMembershipPaymentRequestAction::forMember(),
                    Action::make('send_welcome')
                        ->icon('heroicon-o-envelope')
                        ->requiresConfirmation()
                        ->modalHeading('Send welcome email')
                        ->modalDescription(fn (Member $record) => "Send the account-claim invite to {$record->user?->email}?")
                        ->visible(fn (Member $record) => $record->user !== null && ! $record->hasPlaceholderEmail())
                        ->action(function (Member $record) {
                            self::sendWelcomeTo($record);
                            Notification::make()->success()->title('Welcome email sent')->send();
                        }),
                    EditAction::make(),
                ])
                    ->label('More')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->size(Size::Small)
                    ->color('gray'),
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
                                if (! $member->user || $member->hasPlaceholderEmail()) {
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
                                ->title("Sent {$sent} welcome email(s)".($skipped ? ", skipped {$skipped}" : ''))
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * The single primary action per row, chosen from the member's derived
     * standing. Everything else moves into the ⋯ group above.
     */
    protected static function primaryAction(): Action
    {
        return Action::make('member_primary_action')
            ->label(fn (Member $record) => match ($record->standing()) {
                MemberStanding::Active => 'Open',
                MemberStanding::AwaitingEmail => 'Resend welcome',
                MemberStanding::AwaitingChoice => 'Open',
                MemberStanding::AwaitingPayment => 'Resend payment',
                MemberStanding::Abandoned, MemberStanding::LongLapsed, MemberStanding::Resigned => 'Open',
                MemberStanding::Expired => 'Renew',
                MemberStanding::Suspended => 'Open',
            })
            ->icon('heroicon-o-arrow-right')
            ->size(Size::Small)
            ->color('gray')
            ->url(fn (Member $record) => route('filament.admin.resources.members.view', ['record' => $record]));
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
