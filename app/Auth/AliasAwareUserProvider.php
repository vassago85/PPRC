<?php

namespace App\Auth;

use App\Models\UserEmailAlias;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Drop-in replacement for Laravel's EloquentUserProvider that also resolves
 * email credentials against the user_email_aliases table.
 *
 * Both Fortify's login flow and the password reset broker go through
 * retrieveByCredentials(), so wiring this provider in once (via config/auth.php)
 * covers both without touching either call site.
 *
 * The primary address on users.email always wins the lookup. Only when nothing
 * matches there do we fall back to the aliases table — this means merged users
 * who have not yet picked their primary address keep working exactly like a
 * single-email user, and nobody can shadow somebody else's login by registering
 * an alias (the merge service enforces that an alias email is not already a
 * primary address on another account).
 */
class AliasAwareUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $user = parent::retrieveByCredentials($credentials);

        if ($user !== null) {
            return $user;
        }

        $email = $this->extractEmail($credentials);

        if ($email === null) {
            return null;
        }

        $alias = UserEmailAlias::query()
            ->where('email', $email)
            ->with('user')
            ->first();

        return $alias?->user;
    }

    /**
     * Pull the email out of the Fortify / Laravel credentials bag without
     * matching other keys (like `password`) that the parent lookup already
     * handled. Also tolerates the odd `username` key some auth flows use.
     */
    protected function extractEmail(array $credentials): ?string
    {
        $raw = $credentials['email']
            ?? $credentials['username']
            ?? null;

        if ($raw === null || ! is_string($raw)) {
            return null;
        }

        $email = strtolower(trim($raw));

        return $email === '' ? null : $email;
    }
}
