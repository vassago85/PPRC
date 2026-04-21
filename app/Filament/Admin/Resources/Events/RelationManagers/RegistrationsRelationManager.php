<?php

namespace App\Filament\Admin\Resources\Events\RelationManagers;

use App\Enums\EventRegistrationStatus;
use App\Models\EventRegistration;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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

                Select::make('status')
                    ->options(collect(EventRegistrationStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->default(EventRegistrationStatus::Registered->value)
                    ->required(),

                TextInput::make('squad_number')->numeric(),
                TextInput::make('firing_order')->numeric(),

                TextInput::make('fee_cents')
                    ->label('Fee (ZAR)')
                    ->numeric()
                    ->prefix('R')
                    ->helperText('Leave blank for the default event price. Set to 0 to waive (ExCo / committee members are auto-waived on creation).')
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
            ->defaultSort('squad_number')
            ->columns([
                TextColumn::make('squad_number')->label('Squad')->sortable(),
                TextColumn::make('firing_order')->label('Order')->sortable(),
                TextColumn::make('shooter_display')
                    ->label('Shooter')
                    ->state(fn (EventRegistration $r) => $r->shooterName())
                    ->searchable(
                        query: fn ($query, string $search) => $query
                            ->where('guest_name', 'like', "%{$search}%")
                            ->orWhereHas('member', fn ($q) => $q
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")),
                    ),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?EventRegistrationStatus $s) => $s?->label())
                    ->color(fn (?EventRegistrationStatus $s) => $s?->color() ?? 'gray'),
                TextColumn::make('fee_display')
                    ->label('Fee')
                    ->state(function (EventRegistration $r) {
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
                    ->color(fn (EventRegistration $r) => $r->isWaived() ? 'success' : 'gray'),
                IconColumn::make('attended')->boolean()->label('Attended'),
                TextColumn::make('checked_in_at')->dateTime('d M H:i')->label('Checked in')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(EventRegistrationStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                    ->mutateDataUsing(fn (array $data) => array_merge($data, [
                        'registered_at' => now(),
                    ])),
            ])
            ->recordActions([
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
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
                ]),
            ]);
    }
}
