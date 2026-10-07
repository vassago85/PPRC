<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserEmailAlias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * One-time primary-email picker shown to a user whose account picked up a
 * secondary login address through an admin merge. The banner in the portal
 * layout links here; posting back promotes the chosen address to the primary
 * (swapping users.email with the picked alias) and clears the flag.
 */
class PrimaryEmailController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Nothing to do if they have no aliases, or they've already picked.
        if (! $user->mustPickPrimaryEmail()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.account.primary-email', [
            'user' => $user,
            'addresses' => $user->allLoginEmails(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validEmails = $user->allLoginEmails()->all();

        $data = $request->validate([
            'primary_email' => ['required', 'string', 'email', 'in:'.implode(',', $validEmails)],
        ]);

        $chosen = strtolower(trim($data['primary_email']));
        $current = strtolower((string) $user->email);

        if ($chosen !== $current) {
            // Swap users.email ↔ the alias row so neither primary nor aliases
            // ever double-up. Done in a transaction because the user.email
            // unique index would briefly collide if we touched them in order.
            DB::transaction(function () use ($user, $chosen, $current) {
                $alias = UserEmailAlias::where('user_id', $user->id)
                    ->where('email', $chosen)
                    ->firstOrFail();

                // Park the alias on a scratch value to free the unique index
                // on user_email_aliases.email, so moving the old primary into
                // its place can't collide.
                $alias->forceFill(['email' => 'swap-'.$user->id.'-'.now()->timestamp.'@tmp.local'])->save();

                $oldVerified = $user->email_verified_at;
                $wasAliasVerified = $alias->verified_at;

                $user->forceFill([
                    'email' => $chosen,
                    // A verified alias stays verified when it becomes the
                    // primary; an unverified one resets the primary's
                    // verification so Fortify's "please confirm" flow kicks in.
                    'email_verified_at' => $wasAliasVerified,
                ])->save();

                $alias->forceFill([
                    'email' => $current,
                    'verified_at' => $oldVerified,
                ])->save();
            });
        }

        $user->forceFill(['must_pick_primary_email_at' => null])->save();

        return redirect()
            ->route('portal.dashboard')
            ->with('status', 'Primary email set to '.$chosen.'. Club mail will go there from now on.');
    }
}
