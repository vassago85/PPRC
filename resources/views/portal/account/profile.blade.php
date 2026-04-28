<x-portal.layout title="Account profile">
    <div class="max-w-xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Account</h1>
            <p class="mt-1 text-sm text-slate-400">Update the name and email on your login.</p>
        </div>

        <form method="post" action="{{ route('user-profile-information.update') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-5">
            @csrf
            @method('put')
            <div>
                <label for="name" class="block text-sm text-slate-400">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required
                    class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                @error('name', 'updateProfileInformation')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="block text-sm text-slate-400">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required
                    class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                @error('email', 'updateProfileInformation')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200">
                Save
            </button>
        </form>
    </div>
</x-portal.layout>
