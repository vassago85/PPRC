<?php

namespace App\Console\Commands;

use App\Models\EventRegistration;
use App\Models\MembershipPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Answer "where on earth is this reference" for one reference string.
 *
 * Read-only. Looks in every place a reference can live, deliberately including
 * soft-deleted rows, because a deleted payment is exactly the case where the
 * money arrived but every normal screen shows nothing. Also searches the email
 * log, so you can see whether we ever actually sent the reference out, to whom,
 * and when.
 */
class TracePaymentReference extends Command
{
    protected $signature = 'payments:trace {reference : The reference as it appears on the bank statement}';

    protected $description = 'Find everything we know about one payment reference, including deleted records. Read-only.';

    public function handle(): int
    {
        $reference = trim((string) $this->argument('reference'));

        if ($reference === '') {
            $this->error('Give me a reference to look for.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("Tracing <options=bold>{$reference}</>");

        $found = $this->membershipPayments($reference)
            + $this->matchEntries($reference)
            + $this->shopOrders($reference)
            + $this->membershipNumbers($reference);

        $this->emails($reference);

        $this->newLine();

        if ($found === 0) {
            $this->warn('No record anywhere holds this reference.');
            $this->line('If the email log above shows it was sent, the record it belonged to was hard-deleted.');

            return self::FAILURE;
        }

        $this->info("Found in {$found} record(s).");

        return self::SUCCESS;
    }

    /**
     * The usual home for a PREFIX-YYYYMMDD-NNNN reference. Trashed rows are
     * included and flagged, since those are invisible to every other screen.
     */
    protected function membershipPayments(string $reference): int
    {
        $payments = MembershipPayment::withTrashed()
            ->with(['membership.memberWithTrashed'])
            ->where('reference', $reference)
            ->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $this->newLine();
        $this->line('<options=bold>Membership payments</>');
        $this->table(
            ['id', 'status', 'amount', 'member', 'paid', 'deleted'],
            $payments->map(function (MembershipPayment $payment) {
                $member = $payment->membership?->memberWithTrashed;

                return [
                    $payment->id,
                    $payment->status?->value ?? '—',
                    number_format(($payment->amount_cents ?? 0) / 100, 2),
                    $member ? trim("{$member->first_name} {$member->last_name}") : '(no member linked)',
                    $payment->paid_at?->toDateString() ?? '—',
                    $payment->deleted_at ? 'DELETED '.$payment->deleted_at->toDateString() : 'no',
                ];
            })->all(),
        );

        $trashed = $payments->whereNotNull('deleted_at');

        if ($trashed->isNotEmpty()) {
            $this->warn('This payment is soft-deleted, which is why no admin screen can see it.');
            $this->line('The row is intact, so it can be restored — nothing has been lost.');
        }

        return $payments->count();
    }

    protected function matchEntries(string $reference): int
    {
        if (! Schema::hasColumn('event_registrations', 'payment_reference')) {
            return 0;
        }

        $entries = EventRegistration::query()
            ->with(['member', 'event'])
            ->where('payment_reference', $reference)
            ->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        $this->newLine();
        $this->line('<options=bold>Match entries</>');
        $this->table(
            ['id', 'match', 'shooter', 'owes', 'paid'],
            $entries->map(fn (EventRegistration $entry) => [
                $entry->id,
                $entry->event?->title ?? '—',
                $entry->shooterName(),
                number_format($entry->outstandingCents() / 100, 2),
                $entry->paid_at?->toDateString() ?? '—',
            ])->all(),
        );

        return $entries->count();
    }

    protected function shopOrders(string $reference): int
    {
        $orders = DB::table('shop_orders')
            ->where('eft_reference', $reference)
            ->orWhere('paystack_reference', $reference)
            ->get(['id', 'status', 'total_cents']);

        if ($orders->isEmpty()) {
            return 0;
        }

        $this->newLine();
        $this->line('<options=bold>Shop orders</>');
        $this->table(
            ['id', 'status', 'total'],
            $orders->map(fn ($order) => [
                $order->id,
                $order->status,
                number_format(($order->total_cents ?? 0) / 100, 2),
            ])->all(),
        );

        return $orders->count();
    }

    /**
     * References share a number space with membership numbers, so a reference
     * that looks lost is occasionally somebody's membership number instead.
     */
    protected function membershipNumbers(string $reference): int
    {
        $members = DB::table('members')
            ->where('membership_number', $reference)
            ->get(['id', 'first_name', 'last_name', 'lifecycle', 'deleted_at']);

        if ($members->isEmpty()) {
            return 0;
        }

        $this->newLine();
        $this->line('<options=bold>Membership numbers</>');
        $this->table(
            ['member id', 'name', 'lifecycle', 'deleted'],
            $members->map(fn ($member) => [
                $member->id,
                trim("{$member->first_name} {$member->last_name}"),
                $member->lifecycle ?? '—',
                $member->deleted_at ? 'DELETED' : 'no',
            ])->all(),
        );

        return $members->count();
    }

    /**
     * Whether we ever sent this reference to anybody. The reference sits in the
     * subject on newer mails and in the body on older ones, so search both.
     */
    protected function emails(string $reference): void
    {
        $logs = DB::table('email_logs')
            ->where(function ($query) use ($reference) {
                $query->where('subject', 'like', "%{$reference}%");

                if (Schema::hasColumn('email_logs', 'body_html')) {
                    $query->orWhere('body_html', 'like', "%{$reference}%");
                }
            })
            ->orderBy('sent_at')
            ->limit(25)
            ->get(['to_email', 'subject', 'status', 'sent_at', 'mailable_class']);

        $this->newLine();
        $this->line('<options=bold>Emails quoting this reference</>');

        if ($logs->isEmpty()) {
            $this->line('  None. We have no record of sending this reference to anybody.');

            return;
        }

        $this->table(
            ['sent', 'to', 'status', 'mail'],
            $logs->map(fn ($log) => [
                $log->sent_at ? substr((string) $log->sent_at, 0, 16) : '—',
                $log->to_email,
                $log->status,
                class_basename((string) $log->mailable_class),
            ])->all(),
        );
    }
}
