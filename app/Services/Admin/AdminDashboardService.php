<?php

namespace App\Services\Admin;

use App\Enums\EndorsementStatus;
use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Enums\RenewalSource;
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
                'description' => 'Submitted proofs needing confirmation',
                'url' => MembershipPaymentResource::getUrl('index', ['activeTab' => 'awaiting']),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
            ],
            [
                'label' => 'Memberships awaiting approval',
                'value' => Membership::where('status', MembershipStatus::PendingApproval)->count(),
                'description' => $this->renewalSourceBreakdown(MembershipStatus::PendingApproval),
                'url' => MembershipResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MembershipStatus::PendingApproval->value]],
                ]),
                'icon' => 'heroicon-o-identification',
                'color' => 'warning',
            ],
            [
                'label' => 'Pending renewal payments',
                'value' => Membership::where('status', MembershipStatus::PendingPayment)
                    ->whereHas('member', fn ($q) => $q->whereIn('status', [
                        MemberStatus::Active->value,
                        MemberStatus::Expired->value,
                    ]))
                    ->count(),
                'description' => 'Members started renewal but haven\'t paid yet',
                'url' => MembershipResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MembershipStatus::PendingPayment->value]],
                ]),
                'icon' => 'heroicon-o-clock',
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
                'label' => 'Renewals due (no action yet)',
                'value' => $this->renewalsDueNoAction(),
                'description' => 'Expiring within 30 days, member hasn\'t started renewal',
                'url' => MemberResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MemberStatus::Active->value]],
                ]),
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'info',
            ],
            [
                'label' => 'Recently lapsed',
                'value' => Member::where('status', MemberStatus::Expired)
                    ->where('expiry_date', '>=', now()->subDays(60)->toDateString())
                    ->whereDoesntHave('memberships', fn ($q) => $q->whereIn('status', [
                        MembershipStatus::PendingPayment->value,
                        MembershipStatus::PendingApproval->value,
                    ]))
                    ->count(),
                'description' => 'Expired in last 60 days with no pending renewal',
                'url' => MemberResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MemberStatus::Expired->value]],
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

    /**
     * Members expiring within 30 days who have NOT yet started a renewal
     * (no pending_payment/pending_approval membership row exists).
     */
    protected function renewalsDueNoAction(): int
    {
        return Member::where('status', MemberStatus::Active)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->whereDoesntHave('memberships', fn ($q) => $q->whereIn('status', [
                MembershipStatus::PendingPayment->value,
                MembershipStatus::PendingApproval->value,
            ]))
            ->count();
    }

    /**
     * Build a human-readable breakdown of renewal sources for a given set of
     * membership statuses, e.g. "2 via reminder, 1 self-initiated".
     */
    protected function renewalSourceBreakdown(MembershipStatus|array $statuses): string
    {
        $statuses = is_array($statuses) ? $statuses : [$statuses];
        $statusValues = array_map(fn ($s) => $s->value, $statuses);

        $counts = Membership::whereIn('status', $statusValues)
            ->selectRaw('renewal_source, count(*) as total')
            ->groupBy('renewal_source')
            ->pluck('total', 'renewal_source');

        if ($counts->isEmpty()) {
            return 'None pending';
        }

        $parts = [];
        $reminder = $counts->get(RenewalSource::Reminder->value, 0);
        $self = $counts->get(RenewalSource::MemberInitiated->value, 0);
        $admin = $counts->get(RenewalSource::Admin->value, 0);
        $unknown = $counts->get(null, 0) + $counts->get('', 0);

        if ($reminder > 0) {
            $parts[] = "{$reminder} via reminder";
        }
        if ($self > 0) {
            $parts[] = "{$self} self-initiated";
        }
        if ($admin > 0) {
            $parts[] = "{$admin} by admin";
        }
        if ($unknown > 0) {
            $parts[] = "{$unknown} untagged";
        }

        return implode(', ', $parts) ?: 'None pending';
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
        $renewalInProgress = Membership::whereIn('status', [
            MembershipStatus::PendingPayment->value,
            MembershipStatus::PendingApproval->value,
        ])->count();

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
                'label' => 'Renewal in progress',
                'value' => $renewalInProgress,
                'description' => $this->renewalSourceBreakdown([MembershipStatus::PendingPayment, MembershipStatus::PendingApproval]),
                'url' => MembershipResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MembershipStatus::PendingPayment->value]],
                ]),
                'icon' => 'heroicon-o-arrow-path',
                'color' => 'warning',
            ],
            [
                'label' => 'Renewals due',
                'value' => $this->renewalsDueNoAction(),
                'description' => 'Expiring within 30 days, no action yet',
                'url' => MemberResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MemberStatus::Active->value]],
                ]),
                'icon' => 'heroicon-o-clock',
                'color' => 'info',
            ],
            [
                'label' => 'Lapsed',
                'value' => Member::where('status', MemberStatus::Expired)->count(),
                'description' => 'Expired members',
                'url' => MemberResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => MemberStatus::Expired->value]],
                ]),
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => 'danger',
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
