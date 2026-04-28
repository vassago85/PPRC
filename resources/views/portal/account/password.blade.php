<x-portal.layout title="Change password">
    <div class="max-w-xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Password</h1>
            <p class="mt-1 text-sm text-slate-400">Use a strong password you don't reuse elsewhere.</p>
        </div>

        <form method="post" action="{{ route('user-password.update') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-5">
            @csrf
            @method('put')
            <div>
                <label for="current_password" class="block text-sm text-slate-400">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                    class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                @error('current_password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-sm text-slate-400">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                @error('password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm text-slate-400">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
            </div>
            <button type="submit" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200">
                Update password
            </button>
        </form>
    </div>
</x-portal.layout>
