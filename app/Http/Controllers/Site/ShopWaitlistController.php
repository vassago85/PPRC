<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\ShopWaitlistConfirm;
use App\Models\ShopWaitlistSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ShopWaitlistController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return redirect()->route('shop')->with('flash', 'Thanks — you are on the list.');
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $email = mb_strtolower($validated['email']);

        $existing = ShopWaitlistSubscriber::query()->where('email', $email)->first();
        if ($existing && $existing->unsubscribed_at === null && $existing->confirmed_at !== null) {
            return redirect()
                ->route('shop')
                ->with('flash', 'You are already on the waitlist with this email.');
        }

        $tokens = ShopWaitlistSubscriber::generateTokens();

        $subscriber = ShopWaitlistSubscriber::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $validated['name'] ?? null,
                'user_id' => $request->user()?->id,
                'confirm_token' => $tokens['confirm_token'],
                'unsubscribe_token' => $tokens['unsubscribe_token'],
                'confirmed_at' => null,
                'unsubscribed_at' => null,
            ],
        );

        Mail::to($subscriber->email)->queue(new ShopWaitlistConfirm($subscriber));

        return redirect()
            ->route('shop')
            ->with('flash', 'Check your inbox to confirm your email — then we will notify you when the next apparel run opens.');
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = ShopWaitlistSubscriber::query()
            ->where('confirm_token', $token)
            ->firstOrFail();

        if ($subscriber->confirmed_at) {
            return redirect()
                ->route('shop')
                ->with('flash', 'This email was already confirmed on the shop waitlist.');
        }

        $subscriber->update(['confirmed_at' => now()]);

        return redirect()
            ->route('shop')
            ->with('flash', 'You are confirmed on the PPRC shop waitlist. We will email you when orders open.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = ShopWaitlistSubscriber::query()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        if ($subscriber->unsubscribed_at) {
            return redirect()
                ->route('shop')
                ->with('flash', 'You are already unsubscribed from the shop waitlist.');
        }

        $subscriber->update(['unsubscribed_at' => now()]);

        return redirect()
            ->route('shop')
            ->with('flash', 'You have been removed from the shop waitlist.');
    }
}
