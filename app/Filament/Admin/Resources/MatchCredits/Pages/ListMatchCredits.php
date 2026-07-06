<?php

namespace App\Filament\Admin\Resources\MatchCredits\Pages;

use App\Enums\MatchCreditStatus;
use App\Filament\Admin\Resources\MatchCredits\MatchCreditResource;
use App\Models\MatchCredit;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMatchCredits extends ListRecords
{
    protected static string $resource = MatchCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add credit'),
        ];
    }

    public function getTabs(): array
    {
        $available = MatchCredit::query()->available()->count();

        return [
            'available' => Tab::make('Owed (available)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MatchCreditStatus::Available->value))
                ->badge($available)
                ->badgeColor($available > 0 ? 'success' : 'gray'),

            'used' => Tab::make('Used')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MatchCreditStatus::Used->value)),

            'all' => Tab::make('All'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'available';
    }
}
