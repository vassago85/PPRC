<x-portal.layout title="Account profile">
    <div class="mx-auto max-w-xl px-4 py-10">
        <h1 class="text-2xl font-semibold text-slate-900">Profile</h1>
        <p class="mt-1 text-sm text-slate-600">Update the name and email on your login.</p>

        <form method="post" action="{{ route('user-profile-information.update') }}" class="portal-card mt-8 space-y-5 p-6">
            @csrf
            @method('put')
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                @error('name', 'updateProfileInformation')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                @error('email', 'updateProfileInformation')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Save profile
            </button>
        </form>
    </div>
</x-portal.layout>
