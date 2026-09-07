@php
    $user = auth()->user();
    $name = trim((string) ($user?->name ?? ''));
    $initials = collect(explode(' ', $name))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $initials = $initials !== '' ? $initials : '·';

    // Role hint — defer to the first admin-side role Filament knows about,
    // fall back to a static label so the block never sits empty. Kept
    // deliberately loose because PPRC's role stack is still evolving.
    $role = null;
    if ($user) {
        if (method_exists($user, 'getRoleNames')) {
            try {
                $role = $user->getRoleNames()->first();
            } catch (\Throwable $e) {
                $role = null;
            }
        }
        $role ??= $user->is_admin ?? null ? 'Admin' : null;
    }
    $role ??= 'Signed in';
@endphp

<div class="pp-rail-foot" role="group" aria-label="Signed-in user">
    <div class="pp-rail-foot__av" aria-hidden="true">{{ $initials }}</div>
    <div class="min-w-0">
        <div class="pp-rail-foot__who">{{ $name !== '' ? $name : 'Guest' }}</div>
        <div class="pp-rail-foot__role">{{ ucfirst($role) }}</div>
    </div>
</div>
