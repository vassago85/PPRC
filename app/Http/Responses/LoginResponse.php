<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Role-aware post-login redirect.
     *
     * - Developers + any committee role (matches User::COMMITTEE_ROLES which
     *   gates Filament panel access) go to the admin panel.
     * - Everyone else (plain members) goes to the member portal.
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->hasAnyRole(User::COMMITTEE_ROLES)) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/portal');
    }
}
