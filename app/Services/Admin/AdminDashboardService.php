<?php

namespace App\Services\Admin;

use App\Enums\EndorsementStatus;
use App\Enums\EventStatus;
use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Enums\RenewalSource;
use App\Filament\Admin\Pages\OnboardingPipeline;
use App\Filament\Admin\Resources\EndorsementRequests\EndorsementRequestResource;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Filament\Admin\Resources\Memberships\MembershipResource;
use App\Models\EndorsementRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
                    ->whereHas('member', fn ($q) => $q->whereIn('lifecycle', [
                        MemberLifecycle::Active->value,
                        MemberLifecycle::Expired->value,
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
                'value' => Member::query()->needsOnboarding()->count(),
                'description' => 'Confirmed their email and waiting on the club',
                'url' => MemberResource::getUrl('index', ['activeTab' => 'pending_onboard']),
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
                'description' => 'Expiring within '.config('membership.renewal_due_days').' days, member hasn\'t started renewal',
                'url' => MemberResource::getUrl('index', ['activeTab' => 'renewal_due']),
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'info',
            ],
            [
                'label' => 'Recently lapsed',
                'value' => Member::query()->recentlyLapsed()->count(),
                'description' => 'Expired in last '.config('membership.recently_lapsed_days').' days with no pending renewal',
                'url' => MemberResource::getUrl('index', ['activeTab' => 'lapsed']),
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => 'danger',
            ],
            [
                'label' => 'New match entries',
                'value' => EventRegistration::query()->newSignups()->count(),
                'description' => 'Signed up in the last '.EventRegistration::NEW_SIGNUP_WINDOW_DAYS.' days',
                'url' => EventResource::getUrl('index'),
                'icon' => 'heroicon-o-clipboard-document-check',
                'color' => 'success',
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
     * Active members inside the renewal window who have NOT yet started a
     * renewal. Same scope the Members list "Renewal due" tab uses, so the card
     * and the tab can never disagree.
     */
    protected function renewalsDueNoAction(): int
    {
        return Member::query()->renewalDue()->count();
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
        // All upcoming matches (soonest first), already scoped to published
        // events with a future start date by the upcoming() scope.
        $upcoming = Event::query()
            ->upcoming()
            ->withCount('registrations')
            ->get()
            ->map(fn (Event $event) => [
                'title' => $event->title,
                'date' => $event->start_date?->format('D j M Y'),
                'registrationCount' => $event->registrations_count,
                'url' => EventResource::getUrl('edit', ['record' => $event]),
            ])
            ->all();

        $drafts = Event::where('status', EventStatus::Draft)->count();
        $awaitingResults = Event::where('status', EventStatus::Completed)
            ->whereNull('results_published_at')
            ->count();

        return [
            // The soonest match keeps the prominent "hero" treatment; the full
            // list (including this one) is exposed as `upcoming`.
            'next' => $upcoming[0] ?? null,
            'upcoming' => $upcoming,
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
                'value' => Member::query()->active()->count(),
                'description' => Member::count().' total members',
                'url' => MemberResource::getUrl('index', ['activeTab' => 'active']),
                'icon' => 'heroicon-o-users',
                'color' => 'success',
            ],
            [
                'label' => 'Pending onboard',
                'value' => Member::query()->needsOnboarding()->count(),
                'description' => 'Awaiting onboarding',
                'url' => MemberResource::getUrl('index', ['activeTab' => 'pending_onboard']),
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
                'description' => 'Expiring within '.config('membership.renewal_due_days').' days, no action yet',
                'url' => MemberResource::getUrl('index', ['activeTab' => 'renewal_due']),
                'icon' => 'heroicon-o-clock',
                'color' => 'info',
            ],
            [
                'label' => 'Lapsed',
                'value' => Member::query()->recentlyLapsed()->count(),
                'description' => 'Expired in last '.config('membership.recently_lapsed_days').' days with no pending renewal',
                'url' => MemberResource::getUrl('index', ['activeTab' => 'lapsed']),
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

    /**
     * Live subtitle for the /admin overview: weekday date + days until the
     * next scheduled match. Empty tail when there is no upcoming match.
     */
    public function overviewSubtitle(): string
    {
        $today = now()->locale(app()->getLocale());
        $prefix = $today->isoFormat('dddd D MMMM Y');

        /** @var Event|null $next */
        $next = Event::query()->upcoming()->first();

        if (! $next?->start_date) {
            return $prefix;
        }

        $days = (int) $today->startOfDay()->diffInDays($next->start_date->copy()->startOfDay(), false);

        if ($days < 0) {
            return $prefix;
        }

        $tail = match ($days) {
            0 => "today &middot; {$next->title}",
            1 => "one day to {$next->title}",
            default => "{$days} days to {$next->title}",
        };

        return $prefix.' · '.$tail;
    }

    /**
     * The **Needs you** rows for the rebuilt dashboard.
     *
     * Each row is a sentence with a count, meta line, optional chips, and
     * one or two verb buttons. Rows with a value of 0 are omitted; the
     * view falls back to a muted "all clear" note when everything is done.
     *
     * @return array<int, array{
     *     urgency: 'crit'|'warn'|'info'|'ok',
     *     count: int,
     *     label: string,
     *     meta: ?string,
     *     chips?: array<int, array{label: string, url: string, count: int}>,
     *     actions: array<int, array{label: string, url: string, style?: 'pri'|'ghost'|'default'}>,
     * }>
     */
    public function needsYou(): array
    {
        $rows = [];

        // ------------------------------------------------------------------
        // 1. Payments the club must chase — pending, no proof yet.
        //    The prototype's meta is "R X outstanding · oldest N days ago ·
        //    K past the 14-day cut-off". Missing pieces get elided.
        // ------------------------------------------------------------------
        $pendingPayments = MembershipPayment::query()
            ->where('status', PaymentStatus::Pending->value)
            ->get(['id', 'amount_cents', 'created_at']);

        if ($pendingPayments->isNotEmpty()) {
            $totalCents = (int) $pendingPayments->sum('amount_cents');
            $oldest = $pendingPayments->min('created_at');
            $oldestDays = $oldest ? (int) Carbon::parse($oldest)->diffInDays(now()) : 0;
            $pastCutoff = $pendingPayments
                ->filter(fn ($p) => $p->created_at && Carbon::parse($p->created_at)->diffInDays(now()) >= 14)
                ->count();

            $metaParts = ['R '.number_format($totalCents / 100, 0).' outstanding'];
            if ($oldestDays > 0) {
                $metaParts[] = "oldest {$oldestDays} days ago";
            }
            if ($pastCutoff > 0) {
                $metaParts[] = "{$pastCutoff} past the 14-day cut-off";
            }

            $rows[] = [
                'urgency' => 'crit',
                'count' => $pendingPayments->count(),
                'label' => Str::plural('Payment', $pendingPayments->count()).' with no proof uploaded',
                'meta' => implode(' · ', $metaParts),
                'actions' => [
                    ['label' => 'Review', 'url' => MembershipPaymentResource::getUrl('index', ['activeTab' => 'pending'])],
                    ['label' => 'Chase all', 'url' => MembershipPaymentResource::getUrl('index', ['activeTab' => 'pending']), 'style' => 'pri'],
                ],
            ];
        }

        // ------------------------------------------------------------------
        // 2. Members mid-onboarding — chips break the count out by stage
        //    so the queue row matches the funnel below at a glance.
        // ------------------------------------------------------------------
        $stageCounts = app(OnboardingPipeline::class)->stageCounts();
        $onboardCount = (int) ($stageCounts['choosing_plan'] ?? 0)
            + (int) ($stageCounts['awaiting_payment'] ?? 0)
            + (int) ($stageCounts['ready_to_activate'] ?? 0)
            + (int) ($stageCounts['email_unconfirmed'] ?? 0);

        if ($onboardCount > 0) {
            $stuckCount = Member::query()
                ->where('lifecycle', MemberLifecycle::Pending->value)
                ->whereNull('suspended_at')
                ->whereNull('abandoned_at')
                ->where(function ($q) {
                    $q->where('stage_entered_at', '<=', now()->subDays(7))
                        ->orWhere(function ($qq) {
                            $qq->whereNull('stage_entered_at')
                                ->where('created_at', '<=', now()->subDays(7));
                        });
                })
                ->count();

            $meta = 'Sitting in three different stages';
            if ($stuckCount > 0) {
                $meta .= " — {$stuckCount} have not moved in over a week";
            }

            $chips = [];
            $chipMap = [
                'choosing_plan' => 'Choosing plan',
                'awaiting_payment' => 'Awaiting payment',
                'ready_to_activate' => 'Ready to activate',
            ];
            foreach ($chipMap as $stage => $label) {
                $c = (int) ($stageCounts[$stage] ?? 0);
                if ($c > 0) {
                    $chips[] = [
                        'label' => $label,
                        'count' => $c,
                        'url' => OnboardingPipeline::getUrl(['stage' => $stage]),
                    ];
                }
            }

            $rows[] = [
                'urgency' => 'warn',
                'count' => $onboardCount,
                'label' => 'Members part-way through onboarding',
                'meta' => $meta,
                'chips' => $chips,
                'actions' => [
                    ['label' => 'Open pipeline', 'url' => OnboardingPipeline::getUrl()],
                ],
            ];
        }

        // ------------------------------------------------------------------
        // 3. Payments awaiting review — the moment the member has done their
        //    bit and it's on the club to confirm.
        // ------------------------------------------------------------------
        $reviewCount = MembershipPayment::query()
            ->where('status', PaymentStatus::Submitted->value)
            ->count();
        if ($reviewCount > 0) {
            $rows[] = [
                'urgency' => 'warn',
                'count' => $reviewCount,
                'label' => Str::plural('Proof', $reviewCount).' awaiting your review',
                'meta' => Str::plural('member', $reviewCount).' uploaded proof and is waiting on the club to confirm',
                'actions' => [
                    ['label' => 'Review', 'url' => MembershipPaymentResource::getUrl('index', ['activeTab' => 'awaiting']), 'style' => 'pri'],
                ],
            ];
        }

        // ------------------------------------------------------------------
        // 4. Renewals started but never paid — old members who clicked
        //    renew and never followed through.
        // ------------------------------------------------------------------
        $renewalsPending = Membership::query()
            ->where('status', MembershipStatus::PendingPayment->value)
            ->with('member')
            ->latest()
            ->limit(20)
            ->get();

        if ($renewalsPending->count() > 0) {
            $named = $renewalsPending
                ->take(2)
                ->map(fn ($m) => $m->member?->fullName())
                ->filter()
                ->all();
            $remainder = $renewalsPending->count() - count($named);
            $meta = 'renewal '.Str::plural('request', $renewalsPending->count()).' outstanding';
            if (! empty($named)) {
                $meta .= ' · '.implode(', ', $named);
                if ($remainder > 0) {
                    $meta .= " · +{$remainder} more";
                }
            }

            $rows[] = [
                'urgency' => 'warn',
                'count' => $renewalsPending->count(),
                'label' => 'Renewals started but never paid',
                'meta' => $meta,
                'actions' => [
                    ['label' => 'Send reminder', 'url' => MembershipResource::getUrl('index', [
                        'tableFilters' => ['status' => ['value' => MembershipStatus::PendingPayment->value]],
                    ])],
                ],
            ];
        }

        // ------------------------------------------------------------------
        // 5. Matches closed without results — the club owes members a scoresheet.
        // ------------------------------------------------------------------
        $unpublished = Event::query()
            ->where('status', EventStatus::Completed->value)
            ->whereNull('results_published_at')
            ->orderBy('start_date', 'desc')
            ->get();

        if ($unpublished->count() > 0) {
            $newest = $unpublished->first();
            $daysAgo = $newest?->start_date ? (int) $newest->start_date->diffInDays(now()) : null;
            $meta = $newest?->title;
            if ($meta && $daysAgo !== null) {
                $meta .= " — closed {$daysAgo} days ago";
            }
            if ($unpublished->count() > 1) {
                $meta = ($meta ? $meta.' · ' : '')
                    .'+'.($unpublished->count() - 1).' more';
            }

            $rows[] = [
                'urgency' => 'warn',
                'count' => $unpublished->count(),
                'label' => Str::plural('Match', $unpublished->count()).' closed without published results',
                'meta' => $meta,
                'actions' => [
                    ['label' => 'Publish results', 'url' => EventResource::getUrl('edit', ['record' => $newest]), 'style' => 'pri'],
                ],
            ];
        }

        // ------------------------------------------------------------------
        // 6. New match entries — informational, ordered last.
        // ------------------------------------------------------------------
        $newEntries = EventRegistration::query()->newSignups()->count();
        if ($newEntries > 0) {
            $eventTitles = EventRegistration::query()
                ->newSignups()
                ->with('event:id,title')
                ->get()
                ->groupBy(fn ($r) => $r->event?->title ?: 'Unknown')
                ->map->count();
            $topTitle = $eventTitles->keys()->first();

            $meta = 'signed up in the last '.EventRegistration::NEW_SIGNUP_WINDOW_DAYS.' days';
            if ($topTitle) {
                $meta .= " · mostly {$topTitle}";
            }

            $rows[] = [
                'urgency' => 'info',
                'count' => $newEntries,
                'label' => 'New match entries needing squadding',
                'meta' => $meta,
                'actions' => [
                    ['label' => 'Squad', 'url' => EventResource::getUrl('index')],
                ],
            ];
        }

        // ------------------------------------------------------------------
        // 7. Endorsements — quick-pass informational row.
        // ------------------------------------------------------------------
        $endorsements = EndorsementRequest::where('status', EndorsementStatus::Pending)->count();
        if ($endorsements > 0) {
            $rows[] = [
                'urgency' => 'info',
                'count' => $endorsements,
                'label' => Str::plural('Endorsement', $endorsements).' awaiting review',
                'meta' => 'members waiting on a signed letter',
                'actions' => [
                    ['label' => 'Review', 'url' => EndorsementRequestResource::getUrl('index')],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Money strip: three cells with comparison / cause copy.
     *
     * @return array<int, array{
     *     label: string,
     *     amount: string,
     *     context: ?string,
     *     delta: ?string,
     *     delta_dir: ?'up'|'down',
     *     crit?: bool,
     * }>
     */
    public function moneyStrip(): array
    {
        $mtdCents = (int) MembershipPayment::where('status', PaymentStatus::Confirmed)
            ->where('confirmed_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');

        $lastMonthCents = (int) MembershipPayment::where('status', PaymentStatus::Confirmed)
            ->where('confirmed_at', '>=', now()->subMonthNoOverflow()->startOfMonth())
            ->where('confirmed_at', '<', now()->startOfMonth())
            ->sum('amount_cents');

        $ytdCents = (int) MembershipPayment::where('status', PaymentStatus::Confirmed)
            ->where('confirmed_at', '>=', now()->startOfYear())
            ->sum('amount_cents');

        $lastYearYtdCents = (int) MembershipPayment::where('status', PaymentStatus::Confirmed)
            ->where('confirmed_at', '>=', now()->subYearNoOverflow()->startOfYear())
            ->where('confirmed_at', '<=', now()->subYearNoOverflow())
            ->sum('amount_cents');

        $outstandingCount = MembershipPayment::where('status', PaymentStatus::Pending)->count();
        $outstandingCents = (int) MembershipPayment::where('status', PaymentStatus::Pending)->sum('amount_cents');

        $mtdDelta = $mtdCents - $lastMonthCents;
        $ytdDelta = $ytdCents - $lastYearYtdCents;

        $formatCurrency = static fn (int $cents) => 'R '.number_format($cents / 100, 0);
        $formatDelta = static function (int $cents): string {
            $sign = $cents >= 0 ? '+' : '−';

            return $sign.'R '.number_format(abs($cents) / 100, 0);
        };

        return [
            [
                'label' => 'Confirmed this month',
                'amount' => $formatCurrency($mtdCents),
                'context' => $lastMonthCents > 0
                    ? 'vs '.now()->subMonthNoOverflow()->isoFormat('MMM YYYY')
                    : 'first tracked month',
                'delta' => $lastMonthCents > 0 ? $formatDelta($mtdDelta) : null,
                'delta_dir' => $lastMonthCents > 0 ? ($mtdDelta >= 0 ? 'up' : 'down') : null,
            ],
            [
                'label' => 'Confirmed YTD',
                'amount' => $formatCurrency($ytdCents),
                'context' => $lastYearYtdCents > 0
                    ? 'vs '.now()->subYearNoOverflow()->isoFormat('YYYY').' to date'
                    : 'since '.now()->startOfYear()->isoFormat('D MMM YYYY'),
                'delta' => $lastYearYtdCents > 0 ? $formatDelta($ytdDelta) : null,
                'delta_dir' => $lastYearYtdCents > 0 ? ($ytdDelta >= 0 ? 'up' : 'down') : null,
            ],
            [
                'label' => 'Outstanding',
                'amount' => $formatCurrency($outstandingCents),
                'context' => $outstandingCount > 0
                    ? $outstandingCount.' '.Str::plural('payment', $outstandingCount).' awaiting proof'
                    : 'nothing outstanding',
                'delta' => null,
                'delta_dir' => null,
                'crit' => $outstandingCents > 0,
            ],
        ];
    }

    /**
     * The next match as a real object, not a tile.
     */
    public function nextMatch(): ?array
    {
        /** @var Event|null $event */
        $event = Event::query()
            ->upcoming()
            ->withCount([
                'registrations',
                'registrations as new_entries_count' => fn ($q) => $q
                    ->where('created_at', '>=', now()->subDays(EventRegistration::NEW_SIGNUP_WINDOW_DAYS)),
            ])
            ->first();

        if (! $event) {
            return null;
        }

        // "Unpaid" = an entry that owes money and hasn't cleared yet.
        // paid_at is set the moment a registration is settled by any means
        // (EFT match, credit, admin waiver), so nulls plus a non-zero fee
        // are the ones outstanding.
        $unpaidEntries = $event->registrations()
            ->whereIn('status', ['registered', 'confirmed'])
            ->whereNull('paid_at')
            ->where(function ($q) {
                $q->whereNull('fee_cents')->orWhere('fee_cents', '>', 0);
            })
            ->count();

        return [
            'event' => $event,
            'title' => $event->title,
            'date' => $event->start_date?->format('D j M Y'),
            'venue' => $event->location_name,
            'entries' => (int) $event->registrations_count,
            'max_entries' => $event->max_entries,
            'new_this_week' => (int) $event->new_entries_count,
            'unpaid_entries' => (int) $unpaidEntries,
            'entries_close_at' => $event->registrations_close_at,
            'squad_url' => EventResource::getUrl('squads', ['record' => $event]),
            'report_url' => EventResource::getUrl('report', ['record' => $event]),
            'edit_url' => EventResource::getUrl('edit', ['record' => $event]),
            'match_book_url' => $event->matchBookUrl(),
        ];
    }

    /**
     * Recent activity grouped by Today / This week / Earlier, capped at 10
     * with money and results weighted above account-creation noise.
     *
     * @return array{today: array, this_week: array, earlier: array}
     */
    public function groupedActivity(int $limit = 10): array
    {
        $items = collect($this->recentActivity(25));

        $today = $items->filter(fn ($i) => \Illuminate\Support\Carbon::parse($i['timestamp'])->isToday());
        $week = $items->filter(fn ($i) => ! \Illuminate\Support\Carbon::parse($i['timestamp'])->isToday()
            && \Illuminate\Support\Carbon::parse($i['timestamp'])->isCurrentWeek());
        $earlier = $items->filter(fn ($i) => ! \Illuminate\Support\Carbon::parse($i['timestamp'])->isToday()
            && ! \Illuminate\Support\Carbon::parse($i['timestamp'])->isCurrentWeek());

        return [
            'today' => $today->take(4)->all(),
            'this_week' => $week->take(4)->all(),
            'earlier' => $earlier->take(2)->all(),
        ];
    }

    public function recentActivity(int $limit = 15): array
    {
        $activities = collect();

        $newMembers = Member::latest()->take(5)->get();
        foreach ($newMembers as $member) {
            $activities->push([
                'bucket' => 'mem',
                'description' => $member->fullName().' joined',
                'timestamp' => $member->created_at,
                'url' => MemberResource::getUrl('view', ['record' => $member]),
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
                'bucket' => 'pay',
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
                'bucket' => 'mem',
                'description' => "{$name} membership activated",
                'timestamp' => $membership->created_at,
                'url' => MembershipResource::getUrl('edit', ['record' => $membership]),
            ]);
        }

        $registrations = EventRegistration::with(['event', 'member'])
            ->latest()
            ->take(5)
            ->get();
        foreach ($registrations as $registration) {
            $title = $registration->event?->title ?? 'Unknown event';
            $name = $registration->shooterName() ?: 'Someone';
            $activities->push([
                'bucket' => 'mat',
                'description' => "{$name} registered for {$title}",
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
                'bucket' => 'mat',
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
