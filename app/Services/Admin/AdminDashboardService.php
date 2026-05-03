<?php

namespace App\Services\Admin;

use App\Enums\EndorsementStatus;
use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\EndorsementRequests\EndorsementRequestResource;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Models\EndorsementRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;

class AdminDashboardService
{
    public function needsAttention(): array
    {
        return [
            [
                'label' => 'Payments awaiting review',
                'value' => MembershipPayment::where('status', PaymentStatus::Submitted)->count(),
                'description' => 'Submitted payments needing confirmation',
                'url' => MembershipPaymentResource::getUrl('index', ['activeTab' => 'awaiting']),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
            ],
            [
                'label' => 'Memberships awaiting approval',
                'value' => Membership::where('status', MembershipStatus::PendingApproval)->count(),
                'description' => 'Membership applications to review',
                'url' => MembershipResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MembershipStatus::PendingApproval->value]],
                ]),
                'icon' => 'heroicon-o-identification',
                'color' => 'warning',
            ],
            [
                'label' => 'Members to onboard',
                'value' => Member::where('status', MemberStatus::Pending)->count(),
                'description' => 'New members awaiting onboarding',
                'url' => MemberResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MemberStatus::Pending->value]],
                ]),
                'icon' => 'heroicon-o-user-plus',
                'color' => 'warning',
            ],
            [
                'label' => 'Endorsements to review',
                'value' => EndorsementRequest::where('status', EndorsementStatus::Pending)->count(),
                'description' => 'Pending endorsement requests',
                'url' => EndorsementRequestResource::getUrl('index'),
                'icon' => 'heroicon-o-shield-check',
                'color' => 'warning',
            ],
            [
                'label' => 'Expiring in 30 days',
                'value' => Membership::where('status', MembershipStatus::Active)
                    ->whereBetween('period_end', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->count(),
                'description' => 'Active memberships expiring soon',
                'url' => MembershipResource::getUrl('index'),
                'icon' => 'heroicon-o-clock',
                'color' => 'info',
            ],
            [
                'label' => 'Recently lapsed',
                'value' => Membership::where('status', MembershipStatus::Expired)
                    ->where('period_end', '>=', now()->subDays(60)->toDateString())
                    ->count(),
                'description' => 'Expired in the last 60 days',
                'url' => MembershipResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MembershipStatus::Expired->value]],
                ]),
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => 'danger',
            ],
            [
                'label' => 'Upcoming matches',
                'value' => Event::query()->upcoming()->count(),
                'description' => 'Scheduled upcoming events',
                'url' => EventResource::getUrl('index'),
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'info',
            ],
            [
                'label' => 'Results to publish',
                'value' => Event::where('status', EventStatus::Completed)
                    ->whereNull('results_published_at')
                    ->count(),
                'description' => 'Completed events awaiting results',
                'url' => EventResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => EventStatus::Completed->value]],
                ]),
                'icon' => 'heroicon-o-megaphone',
                'color' => 'warning',
            ],
        ];
    }

    public function matchesOverview(): array
    {
        $nextEvent = Event::query()
            ->upcoming()
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->first();

        $drafts = Event::where('status', EventStatus::Draft)->count();
        $awaitingResults = Event::where('status', EventStatus::Completed)
            ->whereNull('results_published_at')
            ->count();

        return [
            'next' => $nextEvent ? [
                'title' => $nextEvent->title,
                'date' => $nextEvent->start_date?->format('D j M Y'),
                'registrationCount' => $nextEvent->registrations()->count(),
                'url' => EventResource::getUrl('edit', ['record' => $nextEvent]),
            ] : null,
            'drafts' => $drafts,
            'draftsUrl' => EventResource::getUrl('index', [
                'tableFilters' => ['status' => ['value' => EventStatus::Draft->value]],
            ]),
            'awaitingResults' => $awaitingResults,
            'awaitingResultsUrl' => EventResource::getUrl('index', [
                'tableFilters' => ['status' => ['value' => EventStatus::Completed->value]],
            ]),
            'createUrl' => EventResource::getUrl('create'),
            'listUrl' => EventResource::getUrl('index'),
        ];
    }

    public function membershipOverview(): array
    {
        return [
            [
                'label' => 'Active members',
                'value' => Member::where('status', MemberStatus::Active)->count(),
                'description' => Member::count() . ' total members',
                'url' => MemberResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MemberStatus::Active->value]],
                ]),
                'icon' => 'heroicon-o-users',
                'color' => 'success',
            ],
            [
                'label' => 'Pending onboard',
                'value' => Member::where('status', MemberStatus::Pending)->count(),
                'description' => 'Awaiting onboarding',
                'url' => MemberResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MemberStatus::Pending->value]],
                ]),
                'icon' => 'heroicon-o-user-plus',
                'color' => 'warning',
            ],
            [
                'label' => 'Lapsed',
                'value' => Membership::where('status', MembershipStatus::Expired)
                    ->where('period_end', '>=', now()->subDays(60))
                    ->count(),
                'description' => 'Expired in the last 60 days',
                'url' => MembershipResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MembershipStatus::Expired->value]],
                ]),
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => 'danger',
            ],
            [
                'label' => 'Renewals due',
                'value' => Membership::where('status', MembershipStatus::Active)
                    ->whereBetween('period_end', [now(), now()->addDays(30)])
                    ->count(),
                'description' => 'Expiring within 30 days',
                'url' => MembershipResource::getUrl('index'),
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
            ],
            [
                'label' => 'New this month',
                'value' => Member::where('created_at', '>=', now()->startOfMonth())->count(),
                'description' => 'Members joined this month',
                'url' => MemberResource::getUrl('index'),
                'icon' => 'heroicon-o-sparkles',
                'color' => 'info',
            ],
        ];
    }

    public function paymentsOverview(): array
    {
        $revenueMtdCents = MembershipPayment::where('status', PaymentStatus::Confirmed)
            ->where('confirmed_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');

        $revenueYtdCents = MembershipPayment::where('status', PaymentStatus::Confirmed)
            ->where('confirmed_at', '>=', now()->startOfYear())
            ->sum('amount_cents');

        $hasAnyConfirmed = MembershipPayment::where('status', PaymentStatus::Confirmed)->exists();

        return [
            [
                'label' => 'Pending review',
                'value' => MembershipPayment::where('status', PaymentStatus::Submitted)->count(),
                'description' => 'Submitted payments awaiting review',
                'url' => MembershipPaymentResource::getUrl('index', ['activeTab' => 'awaiting']),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
            ],
            [
                'label' => 'Confirmed this week',
                'value' => MembershipPayment::where('status', PaymentStatus::Confirmed)
                    ->where('confirmed_at', '>=', now()->startOfWeek())
                    ->count(),
                'description' => 'Payments confirmed since Monday',
                'url' => MembershipPaymentResource::getUrl('index'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ],
            [
                'label' => 'Failed/cancelled this month',
                'value' => MembershipPayment::whereIn('status', [PaymentStatus::Failed, PaymentStatus::Cancelled])
                    ->where('updated_at', '>=', now()->startOfMonth())
                    ->count(),
                'description' => 'Failed or cancelled this month',
                'url' => MembershipPaymentResource::getUrl('index'),
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
            ],
            [
                'label' => 'Revenue MTD',
                'value' => $revenueMtdCents,
                'formatted' => 'R '.number_format($revenueMtdCents / 100, 2),
                'description' => $hasAnyConfirmed ? 'Confirmed since '.now()->startOfMonth()->format('j M') : 'No confirmed payment data yet',
                'url' => MembershipPaymentResource::getUrl('index', ['activeTab' => 'confirmed']),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'success',
            ],
            [
                'label' => 'Revenue YTD',
                'value' => $revenueYtdCents,
                'formatted' => 'R '.number_format($revenueYtdCents / 100, 2),
                'description' => $hasAnyConfirmed ? 'Confirmed since '.now()->startOfYear()->format('j M Y') : 'No confirmed payment data yet',
                'url' => MembershipPaymentResource::getUrl('index', ['activeTab' => 'confirmed']),
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'success',
            ],
        ];
    }

    public function recentActivity(int $limit = 15): array
    {
        $activities = collect();

        $newMembers = Member::latest()->take(5)->get();
        foreach ($newMembers as $member) {
            $activities->push([
                'icon' => 'heroicon-o-user-plus',
                'description' => $member->fullName().' joined',
                'timestamp' => $member->created_at,
                'url' => MemberResource::getUrl('edit', ['record' => $member]),
            ]);
        }

        $confirmedPayments = MembershipPayment::where('status', PaymentStatus::Confirmed)
            ->with('membership.member')
            ->latest('confirmed_at')
            ->take(5)
            ->get();
        foreach ($confirmedPayments as $payment) {
            $name = $payment->membership?->member?->fullName() ?? 'Unknown';
            $activities->push([
                'icon' => 'heroicon-o-banknotes',
                'description' => "Payment confirmed for {$name}",
                'timestamp' => $payment->confirmed_at,
                'url' => MembershipPaymentResource::getUrl('edit', ['record' => $payment]),
            ]);
        }

        $newMemberships = Membership::where('status', MembershipStatus::Active)
            ->with('member')
            ->latest()
            ->take(5)
            ->get();
        foreach ($newMemberships as $membership) {
            $name = $membership->member?->fullName() ?? 'Unknown';
            $activities->push([
                'icon' => 'heroicon-o-identification',
                'description' => "{$name} membership activated",
                'timestamp' => $membership->created_at,
                'url' => MembershipResource::getUrl('edit', ['record' => $membership]),
            ]);
        }

        $registrations = EventRegistration::with('event')
            ->latest()
            ->take(5)
            ->get();
        foreach ($registrations as $registration) {
            $title = $registration->event?->title ?? 'Unknown event';
            $activities->push([
                'icon' => 'heroicon-o-clipboard-document-list',
                'description' => "Registration for {$title}",
                'timestamp' => $registration->created_at,
                'url' => EventResource::getUrl('edit', ['record' => $registration->event_id]),
            ]);
        }

        $publishedResults = Event::whereNotNull('results_published_at')
            ->latest('results_published_at')
            ->take(3)
            ->get();
        foreach ($publishedResults as $event) {
            $activities->push([
                'icon' => 'heroicon-o-trophy',
                'description' => "Results published for {$event->title}",
                'timestamp' => $event->results_published_at,
                'url' => EventResource::getUrl('edit', ['record' => $event]),
            ]);
        }

        return $activities
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->toArray();
    }
}
