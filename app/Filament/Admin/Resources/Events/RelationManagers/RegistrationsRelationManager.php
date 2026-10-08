<?php

namespace App\Filament\Admin\Resources\Events\RelationManagers;

use App\Enums\AttendanceResponse;
use App\Enums\EventRegistrationStatus;
use App\Enums\InvoiceType;
use App\Enums\MatchEntryAudience;
use App\Enums\MatchPaymentMethod;
use App\Filament\Admin\Actions\ApplyMatchCreditAction;
use App\Filament\Admin\Actions\TransferMatchEntryAction;
use App\Filament\Admin\Support\MemberSearch;
use App\Filament\Admin\Support\SearchTerm;
use App\Invoices\InvoiceFactory;
use App\Invoices\InvoiceUrl;
use App\Mail\MatchEntryRefundIssuedMail;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Services\Events\MatchEntrantBroadcastService;
use App\Services\Events\MatchEntryDeadCenterExporter;
use App\Services\Events\MatchEntryPaymentRequestService;
use App\Support\MailThrottle;
use App\Support\ProofDisk;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Entries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member_id')
                    ->label('Member')
                    ->options(fn () => Member::query()
                        ->with('user.roles')
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->limit(500)
                        ->get()
                        ->mapWithKeys(function ($m) {
                            $base = trim(($m->first_name ?? '').' '.($m->last_name ?? ''))
                                .($m->membership_number ? " ({$m->membership_number})" : '');
                            $label = $m->user?->hasFreeEventEntry()
                                ? $base.'  — ExCo · free entry'
                                : $base;

                            return [$m->id => $label];
                        })
                        ->all())
                    ->searchable()
                    ->preload()
                    ->helperText('Leave blank and fill guest details below for non-members. ExCo / committee members are auto-waived.'),

                TextInput::make('guest_name')->maxLength(150),
                TextInput::make('guest_email')->email()->maxLength(150),
                TextInput::make('guest_phone')->tel()->maxLength(32),

                Select::make('division')
                    ->label('Division')
                    ->options(fn () => collect($this->getOwnerRecord()->registrationDivisionChoices())
                        ->mapWithKeys(fn (string $v) => [$v => $v])
                        ->all())
                    ->searchable()
                    ->nullable(),
                Select::make('category')
                    ->label('Category')
                    ->options(fn () => collect($this->getOwnerRecord()->registrationCategoryChoices())
                        ->mapWithKeys(fn (string $v) => [$v => $v])
                        ->all())
                    ->searchable()
                    ->nullable(),

                Select::make('course')
                    ->label('Course')
                    ->options([
                        'full' => 'Full course (provincial / SAPRF)',
                        'club' => 'Club course (PPRC short)',
                    ])
                    ->visible(fn () => $this->getOwnerRecord()->offersBothCourses())
                    ->helperText('Which course of fire this shooter is doing — only relevant on combined matches.'),

                Select::make('status')
                    ->options(collect(EventRegistrationStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->default(EventRegistrationStatus::Registered->value)
                    ->required(),

                TextInput::make('squad_number')->numeric(),
                TextInput::make('firing_order')->numeric(),

                Toggle::make('is_saprf_entry')
                    ->label('SAPRF entry')
                    ->helperText('Shooter pays through the SAPRF website. PPRC doesn\'t charge them.')
                    ->inline(false),

                Toggle::make('is_junior')
                    ->label('Junior shooter (under 18)')
                    ->helperText('Applies the junior fee tier. Members under 18 are detected automatically — only flag this for guests or to override.')
                    ->inline(false),

                Toggle::make('free_entry')
                    ->label('Free entry (comped)')
                    ->helperText('Waive the fee — this shooter shoots for free. Use for guests, officials or sponsors the match director comps.')
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('fee_cents', $state ? 0 : null))
                    ->inline(false),

                TextInput::make('fee_cents')
                    ->label('Fee override (ZAR)')
                    ->numeric()
                    ->prefix('R')
                    ->helperText('Leave blank to use the match\'s member / non-member price. Set to 0 (or toggle "Free entry") to waive. ExCo members and SAPRF entries are handled automatically.')
                    ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                        ? null
                        : (int) round(((float) $state) * 100))
                    ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100),

                Toggle::make('attended')->inline(false),

                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy('squad_number')
                ->orderBy('firing_order'))
            ->columns([
                TextColumn::make('squad_number')->label('Squad')->sortable(),
                TextColumn::make('firing_order')->label('Order')->sortable(),
                TextColumn::make('shooter_display')
                    ->label('Shooter')
                    ->state(fn (EventRegistration $r) => $r->shooterName())
                    ->searchable(
                        query: function ($query, string $search) {
                            $term = SearchTerm::make($query, $search);

                            $query
                                ->where($term->column('guest_name'), 'like', $term->contains())
                                ->orWhereHas('member', fn ($q) => MemberSearch::apply($q, $search));
                        },
                    ),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?EventRegistrationStatus $state) => $state?->label())
                    ->color(fn (?EventRegistrationStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('fee_display')
                    ->label('Fee')
                    ->state(function (EventRegistration $r) {
                        if ($r->is_saprf_entry) {
                            return 'Paid via SAPRF';
                        }
                        if ($r->isWaived()) {
                            return 'Waived';
                        }
                        $cents = $r->effectiveFeeCents();
                        if ($cents === null) {
                            return '—';
                        }

                        return 'R '.number_format($cents / 100, 2);
                    })
                    ->badge()
                    ->color(fn (EventRegistration $r) => match (true) {
                        $r->is_saprf_entry => 'info',
                        $r->isWaived() => 'success',
                        default => 'gray',
                    })
                    // Where a transferred credit part-covered a dearer match, the
                    // fee alone would read as if the shooter owes the lot.
                    ->description(fn (EventRegistration $r) => $r->creditAppliedCents() > 0
                        ? 'R '.number_format($r->creditAppliedCents() / 100, 2).' from credit'
                            .($r->outstandingCents() > 0
                                ? ' · R '.number_format($r->outstandingCents() / 100, 2).' owing'
                                : '')
                        : null),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->state(fn (EventRegistration $r) => match (true) {
                        $r->paid_at !== null => 'Paid',
                        $r->hasCashIntent() => 'Cash on day',
                        $r->awaitingPayment() => 'Awaiting',
                        default => 'No fee',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Paid' => 'success',
                        'Cash on day' => 'info',
                        'Awaiting' => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (EventRegistration $r) => match (true) {
                        $r->paid_at !== null => trim($r->paid_at->format('d M Y').($r->payment_method ? ' · '.$r->payment_method->label() : '')),
                        $r->hasCashIntent() => 'Will pay cash on arrival',
                        $r->hasUnverifiedProof() => 'Proof uploaded',
                        default => null,
                    }),
                TextColumn::make('payment_reference')
                    ->label('Reference')
                    ->state(fn (EventRegistration $r) => (! $r->is_saprf_entry && (int) ($r->effectiveFeeCents() ?? 0) > 0)
                        ? $r->paymentReference()
                        : '—')
                    ->copyable()
                    ->copyMessage('Reference copied')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('payment_proof_path')
                    ->label('Proof')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus')
                    ->state(fn (EventRegistration $r) => filled($r->payment_proof_path)),
                TextColumn::make('division')->label('Div.')->toggleable(),
                TextColumn::make('category')->label('Cat.')->toggleable(),
                TextColumn::make('course')
                    ->label('Course')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'full' => 'Full',
                        'club' => 'Club',
                        default => null,
                    })
                    ->badge()
                    ->color(fn (?string $state) => $state === 'club' ? 'info' : 'primary')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_saprf_entry')->boolean()->label('SAPRF')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_junior')->boolean()->label('Junior')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('attended')->boolean()->label('Attended')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checked_in_at')->dateTime('d M H:i')->label('Checked in')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('whatsapp_link_sent_at')
                    ->label('WA link')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-minus')
                    ->state(fn (EventRegistration $r) => $r->whatsapp_link_sent_at !== null)
                    ->tooltip(fn (EventRegistration $r) => $r->whatsapp_link_sent_at?->format('d M H:i'))
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('attendance_response')
                    ->label('Attending?')
                    ->icon(fn (?AttendanceResponse $state) => $state?->icon() ?? 'heroicon-o-minus')
                    ->color(fn (?AttendanceResponse $state) => $state?->color() ?? 'gray')
                    ->tooltip(fn (EventRegistration $r) => $r->attendance_response === null
                        ? ($r->attendance_check_sent_at !== null ? 'Asked '.$r->attendance_check_sent_at->format('d M H:i').' — no response yet' : null)
                        : $r->attendance_response->label().' · '.$r->attendance_responded_at?->format('d M H:i'))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(EventRegistrationStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                TernaryFilter::make('paid')
                    ->label('Payment')
                    ->placeholder('All entries')
                    ->trueLabel('Marked paid')
                    ->falseLabel('Not marked paid')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('paid_at'),
                        false: fn (Builder $query) => $query->whereNull('paid_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                    ->mutateDataUsing(fn (array $data) => array_merge($data, [
                        'registered_at' => now(),
                    ])),
                Action::make('export_entries')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn () => auth()->user()?->can('events.view'))
                    ->schema([
                        Select::make('audience')
                            ->label('Who to include')
                            ->options(MatchEntryAudience::options())
                            ->default(MatchEntryAudience::Confirmed->value)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $audience = MatchEntryAudience::from($data['audience']);
                        $exporter = app(MatchEntryDeadCenterExporter::class);
                        $event = $this->getOwnerRecord();

                        if ($exporter->rows($event, $audience) === []) {
                            Notification::make()->warning()
                                ->title('Nothing to export')
                                ->body('No entries match that filter.')
                                ->send();

                            return null;
                        }

                        return $exporter->download($event, $audience);
                    }),
                Action::make('email_entrants')
                    ->label('Email entrants')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                    ->modalHeading('Email entrants')
                    ->modalSubmitActionLabel('Send emails')
                    ->schema([
                        Select::make('audience')
                            ->label('Who to email')
                            ->options(MatchEntryAudience::options())
                            ->default(MatchEntryAudience::All->value)
                            ->required(),
                        TextInput::make('subject')
                            ->label('Subject')
                            ->required()
                            ->maxLength(150),
                        Textarea::make('body')
                            ->label('Message')
                            ->required()
                            ->rows(8)
                            ->helperText('Plain text. Each recipient is greeted by first name automatically.'),
                    ])
                    ->action(function (array $data) {
                        $audience = MatchEntryAudience::from($data['audience']);

                        $result = app(MatchEntrantBroadcastService::class)->send(
                            $this->getOwnerRecord(),
                            $audience,
                            $data['subject'],
                            $data['body'],
                        );

                        Notification::make()->success()
                            ->title('Emails sent')
                            ->body("Sent {$result['sent']}, skipped {$result['skipped']} (no email address).")
                            ->send();
                    }),
                Action::make('send_whatsapp_link')
                    ->label('Send WhatsApp link')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                    ->modalHeading('Send WhatsApp group link')
                    ->modalDescription('Emails every entry in the chosen audience the WhatsApp group invite with a one-tap join button. We stamp each entry once it has had the link, so running this again a day or two later only tops up late signups.')
                    ->modalSubmitActionLabel('Send link')
                    ->fillForm(fn () => [
                        'whatsapp_group_url' => $this->getOwnerRecord()->whatsapp_group_url,
                        'audience' => MatchEntryAudience::Confirmed->value,
                        'skip_already_sent' => true,
                    ])
                    ->schema([
                        TextInput::make('whatsapp_group_url')
                            ->label('WhatsApp group invite link')
                            ->url()
                            ->required()
                            ->maxLength(500)
                            ->placeholder('https://chat.whatsapp.com/...')
                            ->regex('#^https://chat\.whatsapp\.com/[A-Za-z0-9]+$#')
                            ->validationMessages([
                                'regex' => 'Must be a WhatsApp group invite link (https://chat.whatsapp.com/...).',
                            ])
                            ->helperText('Prefilled from the match. If you paste a different one here it will also be saved on the match.'),
                        Select::make('audience')
                            ->label('Who to email')
                            ->options(MatchEntryAudience::options())
                            ->default(MatchEntryAudience::Confirmed->value)
                            ->required(),
                        Toggle::make('skip_already_sent')
                            ->label('Skip shooters who already received it')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Leave on when topping up late entries. Turn off to re-send to everyone (e.g. the group link was rotated).'),
                        Textarea::make('note')
                            ->label('Optional note')
                            ->rows(3)
                            ->helperText('Appended under the default "Join the WhatsApp group…" line.'),
                    ])
                    ->action(function (array $data) {
                        $event = $this->getOwnerRecord();
                        $audience = MatchEntryAudience::from($data['audience']);
                        $url = trim((string) ($data['whatsapp_group_url'] ?? ''));

                        // Persist the (possibly updated) link onto the match so
                        // the public page and future sends pick it up without
                        // the admin having to open the match form.
                        if ($url !== '' && $url !== (string) $event->whatsapp_group_url) {
                            $event->update(['whatsapp_group_url' => $url]);
                        }

                        try {
                            $result = app(MatchEntrantBroadcastService::class)->sendWhatsAppLink(
                                $event->fresh(),
                                $audience,
                                (bool) ($data['skip_already_sent'] ?? true),
                                $data['note'] ?? null,
                            );
                        } catch (ValidationException $e) {
                            Notification::make()->danger()
                                ->title('Could not send WhatsApp link')
                                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                                ->send();

                            return;
                        }

                        Notification::make()->success()
                            ->title('WhatsApp link sent')
                            ->body("Sent {$result['sent']}, already had it: {$result['already']}, skipped {$result['skipped']} (no email).")
                            ->send();
                    }),
                Action::make('send_attendance_check')
                    ->label('Send attendance check')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('info')
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                    ->modalHeading('Send "are you still shooting?" email')
                    ->modalDescription('Emails every entry in the chosen audience three big buttons — still shooting, unsure, withdraw. The response is recorded against their entry so you can plan squads without chasing anyone. We stamp each entry once it has had the email, so running this again a day or two later only tops up new signups.')
                    ->modalSubmitActionLabel('Send check')
                    ->fillForm(fn () => [
                        'audience' => MatchEntryAudience::Awaiting->value,
                        'skip_already_sent' => true,
                    ])
                    ->schema([
                        Select::make('audience')
                            ->label('Who to email')
                            ->options(MatchEntryAudience::options())
                            ->default(MatchEntryAudience::Awaiting->value)
                            ->required()
                            ->helperText('Awaiting payment is the usual pick — those are the shooters whose attendance is uncertain.'),
                        Toggle::make('skip_already_sent')
                            ->label('Skip shooters we have already asked')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Leave on when topping up late entries. Turn off to re-send to everyone (e.g. the match was rescheduled).'),
                        Textarea::make('note')
                            ->label('Optional note')
                            ->rows(3)
                            ->helperText('Appended under the default "we are finalising the squad list" line.'),
                    ])
                    ->action(function (array $data) {
                        $event = $this->getOwnerRecord();
                        $audience = MatchEntryAudience::from($data['audience']);

                        $result = app(MatchEntrantBroadcastService::class)->sendAttendanceCheck(
                            $event,
                            $audience,
                            (bool) ($data['skip_already_sent'] ?? true),
                            $data['note'] ?? null,
                        );

                        Notification::make()->success()
                            ->title('Attendance check sent')
                            ->body("Sent {$result['sent']}, already asked: {$result['already']}, skipped {$result['skipped']} (no email).")
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('view_invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(fn (EventRegistration $r) => app(InvoiceFactory::class)->canGenerate($r))
                    ->url(fn (EventRegistration $r) => InvoiceUrl::signed(InvoiceType::Match, $r->id), shouldOpenInNewTab: true),
                Action::make('send_payment_email')
                    ->label('Send payment email')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send payment email')
                    ->modalDescription(fn (EventRegistration $r) => 'Email '.$r->shooterName().' at '
                        .($r->payerEmail() ?? '—').' with the amount outstanding (R '
                        .number_format($r->outstandingCents() / 100, 2)
                        .'), banking details and a payment reference?')
                    ->visible(fn (EventRegistration $r) => $r->owesPayment()
                        && ! $r->hasCashIntent()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        try {
                            app(MatchEntryPaymentRequestService::class)->send($r);

                            Notification::make()->success()
                                ->title('Payment email sent')
                                ->body('Sent to '.$r->payerEmail().' with reference '.$r->paymentReference().'.')
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()
                                ->title('Could not send payment email')
                                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                                ->send();
                        }
                    }),
                Action::make('view_proof')
                    ->label('View proof')
                    ->icon('heroicon-o-paper-clip')
                    ->color('info')
                    ->visible(fn (EventRegistration $r) => filled($r->payment_proof_path)
                        && auth()->user()?->can('events.registrations.manage'))
                    ->url(fn (EventRegistration $r) => self::proofUrl($r), shouldOpenInNewTab: true),
                Action::make('mark_cash_intent')
                    ->label('Will pay cash')
                    ->icon('heroicon-o-wallet')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Flag as paying cash on the day')
                    ->modalDescription(fn (EventRegistration $r) => 'Flag '.$r->shooterName().'\'s entry as paying R '
                        .number_format($r->outstandingCents() / 100, 2)
                        .' in cash on arrival? They won\'t be chased for EFT. The match director will mark them paid on the day.')
                    ->visible(fn (EventRegistration $r) => $r->paid_at === null
                        && $r->awaitingPayment()
                        && ! $r->hasCashIntent()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        $r->update(['payment_method' => MatchPaymentMethod::Cash->value]);

                        Notification::make()->success()
                            ->title('Marked as paying cash')
                            ->body($r->shooterName().' is flagged to pay in cash on the day. No EFT reminder will be sent.')
                            ->send();
                    }),
                Action::make('clear_cash_intent')
                    ->label('Cancel cash plan')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel cash-on-day plan')
                    ->modalDescription(fn (EventRegistration $r) => 'Clear the cash-on-day flag for '.$r->shooterName()
                        .'? They\'ll go back to being chased for EFT like everyone else awaiting payment.')
                    ->visible(fn (EventRegistration $r) => $r->hasCashIntent()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        $r->update(['payment_method' => null]);

                        Notification::make()->success()
                            ->title('Cash plan cleared')
                            ->body($r->shooterName().' is back in the EFT queue.')
                            ->send();
                    }),
                Action::make('mark_paid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm payment received')
                    ->modalDescription(function (EventRegistration $r) {
                        $amount = 'R '.number_format((int) ($r->effectiveFeeCents() ?? 0) / 100, 2);
                        $who = $r->shooterName();

                        return $r->hasCashIntent()
                            ? "Confirm {$who} has handed over {$amount} in cash?"
                            : "Mark {$who}'s entry ({$amount}) as paid? Do this once the EFT reflects in the club account.";
                    })
                    ->visible(fn (EventRegistration $r) => $r->paid_at === null
                        && $r->awaitingPayment()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        $r->update($this->paidAttributes($r));

                        $emailed = app(MatchEntryPaymentRequestService::class)->sendConfirmation($r);

                        Notification::make()->success()
                            ->title('Marked as paid')
                            ->body($r->shooterName().'\'s entry fee is confirmed received.'
                                .($emailed ? ' A confirmation email was sent.' : ''))
                            ->send();
                    }),
                Action::make('send_confirmation')
                    ->label('Send confirmation')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send payment confirmation')
                    ->modalDescription(fn (EventRegistration $r) => 'Email '.$r->shooterName().' at '
                        .($r->payerEmail() ?? '—').' confirming their entry fee has been received?')
                    ->visible(fn (EventRegistration $r) => $r->paid_at !== null
                        && filled($r->payerEmail())
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        $sent = app(MatchEntryPaymentRequestService::class)->sendConfirmation($r);

                        if ($sent) {
                            Notification::make()->success()
                                ->title('Confirmation sent')
                                ->body('Emailed '.$r->payerEmail().' confirming payment received.')
                                ->send();

                            return;
                        }

                        Notification::make()->danger()
                            ->title('Could not send confirmation')
                            ->body('This entry has no email address on file.')
                            ->send();
                    }),
                Action::make('mark_unpaid')
                    ->label('Undo paid')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Undo payment confirmation')
                    ->visible(fn (EventRegistration $r) => $r->paid_at !== null
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        $r->update([
                            'paid_at' => null,
                            'marked_paid_by_user_id' => null,
                        ]);

                        Notification::make()->success()
                            ->title('Marked as unpaid')
                            ->send();
                    }),
                Action::make('withdraw_refund')
                    ->label('Withdraw & refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->modalHeading(fn (EventRegistration $r) => 'Withdraw '.$r->shooterName().' and refund')
                    ->modalDescription(fn (EventRegistration $r) => 'Cancel '.$r->shooterName().'\'s entry and record the refund you issued. The entry stays on the list marked Cancelled, and the refund shows on the match director\'s cash-up slip so the money going back out is accounted for.')
                    ->modalSubmitActionLabel('Record withdrawal and refund')
                    ->visible(fn (EventRegistration $r) => $r->paid_at !== null
                        && $r->status !== EventRegistrationStatus::Cancelled
                        && ! $r->wasRefunded()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->fillForm(fn (EventRegistration $r) => [
                        'amount_rands' => number_format(((int) ($r->effectiveFeeCents() ?? 0)) / 100, 2, '.', ''),
                        'method' => $r->payment_method?->value ?? MatchPaymentMethod::Eft->value,
                        'notify' => true,
                    ])
                    ->schema([
                        TextInput::make('amount_rands')
                            ->label('Refund amount (ZAR)')
                            ->numeric()
                            ->prefix('R')
                            ->minValue(0)
                            ->required()
                            ->helperText('Defaults to the full fee. Reduce for a partial refund.'),
                        Select::make('method')
                            ->label('Refund method')
                            ->options(MatchPaymentMethod::options())
                            ->default(MatchPaymentMethod::Eft->value)
                            ->required()
                            ->helperText('EFT came out of the club account. Cash came out of the match-day float.'),
                        Textarea::make('note')
                            ->label('Note (optional)')
                            ->rows(2)
                            ->helperText('Visible on the cash-up slip and included in the shooter\'s refund email.'),
                        Toggle::make('notify')
                            ->label('Email the shooter a refund notification')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->action(function (EventRegistration $r, array $data) {
                        $method = MatchPaymentMethod::tryFrom($data['method'] ?? '')
                            ?? MatchPaymentMethod::Eft;
                        $amountCents = (int) round(((float) ($data['amount_rands'] ?? 0)) * 100);
                        $note = $data['note'] ?? null;
                        $notify = (bool) ($data['notify'] ?? false);

                        DB::transaction(function () use ($r, $amountCents, $method, $note) {
                            $r->update([
                                'status' => EventRegistrationStatus::Cancelled,
                                'refunded_at' => now(),
                                'refunded_amount_cents' => $amountCents,
                                'refunded_method' => $method->value,
                                'refunded_note' => $note,
                                'refunded_by_user_id' => auth()->id(),
                            ]);
                        });

                        $emailed = false;
                        if ($notify && filled($r->payerEmail())) {
                            Mail::to($r->payerEmail(), $r->shooterName())
                                ->send(new MatchEntryRefundIssuedMail($r->fresh(['event'])));
                            $emailed = true;
                        }

                        Notification::make()->success()
                            ->title('Withdrawal and refund recorded')
                            ->body($r->shooterName().' is withdrawn, R '.number_format($amountCents / 100, 2)
                                .' refunded via '.$method->label().'.'
                                .($emailed ? ' A notification email was sent.' : ''))
                            ->send();
                    }),
                ApplyMatchCreditAction::make(),
                TransferMatchEntryAction::make(),
                Action::make('check_in')
                    ->label('Check in')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EventRegistration $r) => ! $r->attended
                        && auth()->user()?->can('events.attendance.manage'))
                    ->action(function (EventRegistration $r) {
                        $r->update([
                            'attended' => true,
                            'checked_in_at' => now(),
                            'checked_in_by_user_id' => auth()->id(),
                        ]);
                    }),
                EditAction::make()
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('send_payment_email')
                        ->label('Send payment email')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send payment emails')
                        ->modalDescription('Email the selected entries their banking details and payment reference. Entries that owe nothing (free / ExCo / SAPRF) or have no email are skipped automatically.')
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                        ->action(function (Collection $records) {
                            $result = app(MatchEntryPaymentRequestService::class)->sendBulk($records);

                            Notification::make()->success()
                                ->title('Payment emails sent')
                                ->body("Sent {$result['sent']}, skipped {$result['skipped']} (nothing owed or no email).")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('mark_paid')
                        ->label('Mark paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm payments received')
                        ->modalDescription('Mark the selected entries as paid. Entries that owe nothing (free / ExCo / SAPRF) or are already paid are skipped automatically.')
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                        ->action(function (Collection $records) {
                            $count = 0;

                            $emailed = 0;
                            $service = app(MatchEntryPaymentRequestService::class);

                            foreach ($records as $r) {
                                if ($r->paid_at !== null || ! $r->awaitingPayment()) {
                                    continue;
                                }

                                $r->update($this->paidAttributes($r));
                                $count++;

                                if ($service->sendConfirmation($r, MailThrottle::delayFor($emailed))) {
                                    $emailed++;
                                }
                            }

                            Notification::make()->success()
                                ->title('Marked as paid')
                                ->body($count.' '.str('entry')->plural($count).' updated'
                                    .($emailed > 0 ? ", {$emailed} confirmation ".str('email')->plural($emailed).' sent.' : '.'))
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('send_confirmation')
                        ->label('Send confirmation')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Send payment confirmations')
                        ->modalDescription('Email the selected paid entries confirming their fee was received. Entries not yet marked paid or with no email are skipped automatically.')
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                        ->action(function (Collection $records) {
                            $sent = 0;
                            $skipped = 0;
                            $service = app(MatchEntryPaymentRequestService::class);

                            foreach ($records as $r) {
                                if ($r->paid_at === null || ! $service->sendConfirmation($r, MailThrottle::delayFor($sent))) {
                                    $skipped++;

                                    continue;
                                }

                                $sent++;
                            }

                            Notification::make()->success()
                                ->title('Confirmations sent')
                                ->body("Sent {$sent}, skipped {$skipped} (not paid or no email).")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
                ]),
            ]);
    }

    /**
     * Attributes applied when confirming an entry's fee was received. Marking
     * paid also confirms the entry (the "approval"), but never overrides a
     * cancelled / no-show status.
     *
     * @return array<string, mixed>
     */
    protected function paidAttributes(EventRegistration $r): array
    {
        $data = [
            'paid_at' => now(),
            'marked_paid_by_user_id' => auth()->id(),
        ];

        if (in_array($r->status, [EventRegistrationStatus::Registered, EventRegistrationStatus::Waitlisted], true)) {
            $data['status'] = EventRegistrationStatus::Confirmed;
        }

        return $data;
    }

    protected static function proofUrl(EventRegistration $r): ?string
    {
        // Proofs live on the private disk; hand back a short-lived signed URL.
        return ProofDisk::url($r->payment_proof_path);
    }
}
