<?php

namespace App\Filament\Admin\Resources\Members\RelationManagers;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Services\Membership\MembershipTypeService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    protected static ?string $title = 'Memberships';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('membership_type_id')
                    ->label('Type')
                    ->options(fn () => MembershipType::query()->where('is_active', true)
                        ->orderBy('sort_order')->pluck('name', 'id')->all())
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
                        $end = app(MembershipTypeService::class)->calculateExpiryDate($type, $start);
                        $set('period_start', $start->toDateString());
                        $set('period_end', $end?->toDateString());
                        $set('price_cents_snapshot', $type->price_cents);
                        $set('membership_type_slug_snapshot', $type->slug);
                        $set('membership_type_name_snapshot', $type->name);
                    }),

                DatePicker::make('period_start')->required()->native(false),
                DatePicker::make('period_end')->native(false)
                    ->helperText('Null for life / honorary memberships (duration_months = 0).'),

                Select::make('status')
                    ->options(collect(MembershipStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->required()
                    ->default(MembershipStatus::PendingPayment->value),

                TextInput::make('price_cents_snapshot')
                    ->label('Price (ZAR)')
                    ->numeric()
                    ->required()
                    ->prefix('R')
                    ->suffix('.00')
                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100))
                    ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100),

                TextInput::make('membership_type_slug_snapshot')->required()->readOnly(),
                TextInput::make('membership_type_name_snapshot')->required()->readOnly(),

                Textarea::make('admin_notes')->columnSpanFull()->rows(2),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('period_end', 'desc')
            ->columns([
                TextColumn::make('membership_type_name_snapshot')->label('Type')->badge(),
                TextColumn::make('period_start')->date('d M Y')->label('Start'),
                TextColumn::make('period_end')->date('d M Y')->label('End')
                    ->color(fn ($record) => $record->period_end?->isPast() ? 'danger' : null),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?MembershipStatus $state) => $state?->label())
                    ->color(fn (?MembershipStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('price_cents_snapshot')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => 'R '.number_format($state / 100, 2)),
                TextColumn::make('approved_at')->dateTime('d M Y H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(MembershipStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Membership $record) => $record->status === MembershipStatus::PendingApproval)
                    ->requiresConfirmation()
                    ->action(function (Membership $record) {
                        $record->update([
                            'status' => MembershipStatus::Active,
                            'approved_at' => now(),
                            'approved_by_user_id' => auth()->id(),
                        ]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
