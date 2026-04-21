<?php

namespace App\Jobs;

use App\Mail\ShopRunOpened;
use App\Models\ShopRun;
use App\Models\ShopWaitlistSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendShopRunOpenedToWaitlist implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $shopRunId,
    ) {}

    public function handle(): void
    {
        $run = ShopRun::query()->find($this->shopRunId);

        if (! $run || ! $run->isAcceptingOrders()) {
            return;
        }

        ShopWaitlistSubscriber::query()
            ->subscribed()
            ->confirmed()
            ->orderBy('id')
            ->chunkById(100, function ($subscribers) use ($run): void {
                foreach ($subscribers as $subscriber) {
                    Mail::to($subscriber->email)->queue(new ShopRunOpened($run, $subscriber));
                }
            });

        $run->update(['waitlist_last_notified_at' => now()]);
    }
}
