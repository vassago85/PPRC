<?php

namespace App\Filament\Admin\Resources\Memberships\Tables;

use App\Enums\MembershipStatus;
use App\Services\Membership\MemberService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MembershipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('period_end', 'desc')
            ->columns([
                TextColumn::make('member.membership_number')->label('#')->badge()->sortable()->searchable(),
                TextColumn::make('member.first_name')->label('Member')
                    ->formatStateUsing(fn ($record) => $record->member?->fullName())
                    ->searchable(['member.first_name', 'member.last_name']),
                TextColumn::make('membership_type_name_snapshot')->label('Type')->badge()->sortable(),
                TextColumn::make('period_start')->date('d M Y'),
                TextColumn::make('period_end')->date('d M Y')
                    ->color(fn ($record) => $record->period_end?->isPast() ? 'danger' : null)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?MembershipStatus $state) => $state?->label())
                    ->color(fn (?MembershipStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('membershipType.price_cents')->label('Price')
                    ->formatStateUsing(fn ($state, $record) => 'R '.number_format(($state ?? $record->price_cents_snapshot) / 100, 2))
                    ->tooltip(fn ($record) => $record->price_cents_snapshot !== ($record->membershipType?->price_cents ?? $record->price_cents_snapshot)
                        ? 'Snapshot at creation: R '.number_format($record->price_cents_snapshot / 100, 2)
                        : null)
                    ->alignEnd(),
                TextColumn::make('approved_at')->dateTime('d M Y')->toggleable()->label('Approved'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(MembershipStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, [MembershipStatus::PendingApproval, MembershipStatus::PendingPayment]))
                    ->requiresConfirmation()
                    ->action(fn ($record) => app(MemberService::class)->activate($record, auth()->user())),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
