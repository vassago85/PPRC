<?php

namespace App\Filament\Admin\Resources\Memberships\Schemas;

use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\MembershipType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class MembershipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Membership')
                    ->columns(2)
                    ->schema([
                        Select::make('member_id')
                            ->relationship('member', 'id')
                            ->getOptionLabelFromRecordUsing(fn (Member $r) => $r->fullName().' ('.($r->membership_number ?? '—').')')
                            ->searchable(['first_name', 'last_name', 'membership_number'])
                            ->preload()
                            ->required(),
                        Select::make('membership_type_id')
                            ->label('Type')
                            ->options(fn () => MembershipType::orderBy('sort_order')->pluck('name', 'id')->all())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    return;
                                }
                                $type = MembershipType::find($state);
                                if (! $type) {
                                    return;
                                }
                                $start = Carbon::now();
                                $set('period_start', $start->toDateString());
                                $set('period_end', $start->copy()->addMonths($type->duration_months)->subDay()->toDateString());
                                $set('price_cents_snapshot', $type->price_cents / 100);
                                $set('membership_type_slug_snapshot', $type->slug);
                                $set('membership_type_name_snapshot', $type->name);
                            }),
                        DatePicker::make('period_start')->required()->native(false),
                        DatePicker::make('period_end')->required()->native(false),
                        Select::make('status')
                            ->options(collect(MembershipStatus::cases())
                                ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                            ->required()
                            ->default(MembershipStatus::PendingPayment->value),
                        TextInput::make('price_cents_snapshot')
                            ->label('Price (ZAR)')
                            ->required()
                            ->numeric()
                            ->prefix('R')
                            ->suffix('.00')
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100))
                            ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100),
                    ]),
                Section::make('Snapshot (locked at sale)')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('membership_type_slug_snapshot')->required()->readOnly(),
                        TextInput::make('membership_type_name_snapshot')->required()->readOnly(),
                    ]),
                Section::make('Approval')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        DateTimePicker::make('approved_at')->native(false),
                        Textarea::make('admin_notes')->columnSpanFull()->rows(2),
                    ]),
            ]);
    }
}
