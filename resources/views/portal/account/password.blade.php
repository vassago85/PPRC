<x-portal.layout title="Change password">
    <div class="mx-auto max-w-xl px-4 py-10">
        <h1 class="text-2xl font-semibold text-slate-900">Password</h1>
        <p class="mt-1 text-sm text-slate-600">Use a strong password you do not reuse elsewhere.</p>

        <form method="post" action="{{ route('user-password.update') }}" class="portal-card mt-8 space-y-5 p-6">
            @csrf
            @method('put')
            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                @error('current_password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                @error('password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Update password
            </button>
        </form>
    </div>
</x-portal.layout>
