<?php

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Filament\Admin\Resources\Members\MemberResource;
use App\Models\Member;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * A live count sentence — never decoration. The subtitle is the same
     * numbers the segments below carry, so an admin can see at a glance
     * where the roster stands.
     */
    public function getSubheading(): string|Htmlable|null
    {
        $total = Member::query()->count();
        $current = Member::query()->current()->count();
        $onboarding = Member::query()->needsOnboarding()->count();

        return "{$total} people on the books · {$current} current · {$onboarding} mid-onboarding";
    }

    /**
     * Five mutually exclusive segments summing to All.
     *
     *   Current         — lifecycle=Active OR suspended (regardless of underlying)
     *   Onboarding      — pending + email verified + not abandoned + not suspended
     *   Awaiting email  — pending + email unverified + not abandoned + not suspended
     *   Lapsed          — expired/resigned + not suspended + not abandoned
     *   Abandoned       — abandoned_at set + not suspended
     *
     * Their union covers every member exactly once — asserted in
     * tests/Feature/MembersSegmentSumTest.php.
     */
    public function getTabs(): array
    {
        $current = Member::query()->current()->count();
        $onboarding = Member::query()->needsOnboarding()->count();
        $awaitingEmail = Member::query()->awaitingEmail()->count();
        $lapsed = Member::query()->lapsedRoster()->count();
        $abandoned = Member::query()->abandoned()->count();

        return [
            'all' => Tab::make('All')
                ->badge(number_format(Member::query()->count())),

            'current' => Tab::make('Current')
                ->modifyQueryUsing(fn (Builder $query) => $query->current())
                ->badge($current)
                ->badgeColor($current > 0 ? 'success' : 'gray'),

            'onboarding' => Tab::make('Onboarding')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsOnboarding())
                ->badge($onboarding)
                ->badgeColor($onboarding > 0 ? 'warning' : 'gray'),

            'awaiting_email' => Tab::make('Awaiting email')
                ->modifyQueryUsing(fn (Builder $query) => $query->awaitingEmail())
                ->badge($awaitingEmail)
                ->badgeColor('gray'),

            'lapsed' => Tab::make('Lapsed')
                ->modifyQueryUsing(fn (Builder $query) => $query->lapsedRoster())
                ->badge($lapsed)
                ->badgeColor($lapsed > 0 ? 'danger' : 'gray'),

            'abandoned' => Tab::make('Abandoned')
                ->modifyQueryUsing(fn (Builder $query) => $query->abandoned())
                ->badge($abandoned)
                ->badgeColor('gray'),
        ];
    }

    /**
     * Old deep-links from the dashboard / emails aimed at seven-tab keys
     * that no longer exist. Remap them so bookmarks still land on
     * something sensible instead of silently switching to "All".
     */
    public function mount(): void
    {
        parent::mount();

        $legacy = [
            'pending_onboard' => 'onboarding',
            'active' => 'current',
            'renewal_due' => 'current',
            'suspended' => 'current',
        ];

        if (isset($legacy[$this->activeTab])) {
            $this->activeTab = $legacy[$this->activeTab];
        }
    }
}
