<?php

namespace App\Filament\Admin\Resources\MatchCredits\Schemas;

use App\Enums\MatchCreditStatus;
use App\Models\Event;
use App\Models\Member;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MatchCreditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Who the credit is for')
                ->columns(2)
                ->schema([
                    Select::make('member_id')
                        ->label('Member')
                        ->options(fn () => Member::query()
                            ->orderBy('last_name')
                            ->orderBy('first_name')
                            ->limit(1000)
                            ->get()
                            ->mapWithKeys(fn (Member $m) => [
                                $m->id => trim(($m->first_name ?? '').' '.($m->last_name ?? ''))
                                    .($m->membership_number ? " ({$m->membership_number})" : ''),
                            ])
                            ->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) {
                                return;
                            }
                            $member = Member::with('user')->find($state);
                            if ($member) {
                                $set('payee_name', $member->fullName());
                                if ($member->user?->email) {
                                    $set('payee_email', $member->user->email);
                                }
                            }
                        })
                        ->helperText('Leave blank for a guest / non-member and fill in the name below.'),

                    TextInput::make('payee_name')
                        ->label('Name')
                        ->maxLength(150)
                        ->required(fn (Get $get) => blank($get('member_id')))
                        ->helperText('Auto-filled from the member; required for guests.'),

                    TextInput::make('payee_email')
                        ->label('Email')
                        ->email()
                        ->maxLength(190),
                ]),

            Section::make('Credit')
                ->columns(2)
                ->schema([
                    TextInput::make('amount_cents')
                        ->label('Amount (R)')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->required()
                        ->formatStateUsing(fn (?int $state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100))
                        ->helperText('The credit value in Rands.'),

                    Select::make('status')
                        ->options(MatchCreditStatus::options())
                        ->default(MatchCreditStatus::Available->value)
                        ->required(),

                    Select::make('source_event_id')
                        ->label('From match')
                        ->options(fn () => Event::query()
                            ->orderByDesc('start_date')
                            ->limit(200)
                            ->pluck('title', 'id')
                            ->all())
                        ->searchable()
                        ->helperText('The match this credit came from (e.g. a paid no-show).'),

                    TextInput::make('reason')
                        ->label('Reason')
                        ->maxLength(190)
                        ->placeholder('e.g. Paid but couldn\'t shoot — held for next match')
                        ->columnSpanFull(),
                ]),

            Section::make('Redemption')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Select::make('used_event_id')
                        ->label('Used on match')
                        ->options(fn () => Event::query()
                            ->orderByDesc('start_date')
                            ->limit(200)
                            ->pluck('title', 'id')
                            ->all())
                        ->searchable(),
                    DateTimePicker::make('used_at')
                        ->label('Used at'),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ])->columns(1);
    }
}
