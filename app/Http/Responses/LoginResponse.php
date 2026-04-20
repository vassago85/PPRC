<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Role-aware post-login redirect.
     *
     * - Developers + any admin-style role go to the Filament panel.
     * - Everyone else goes to the member portal.
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        $adminRoles = ['developer', 'admin', 'chairperson', 'secretary', 'treasurer', 'editor'];

        if ($user && $user->hasAnyRole($adminRoles)) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/portal');
    }
}
