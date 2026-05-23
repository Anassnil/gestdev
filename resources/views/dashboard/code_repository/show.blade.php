@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">{{ $repository->name }}</h1>
                <p class="text-white/60 mt-2">{{ $repository->slug }} • owned by {{ $repository->owner->name }}</p>
            </div>
            <a href="{{ route('dashboard.code_repository.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm md:text-base">
                Back to Repositories
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl border border-green-500/30 bg-green-500/10 text-green-300 px-4 py-3">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 px-4 py-3">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs uppercase tracking-wide">Visibility</div>
                <div class="text-2xl font-black text-white mt-2">{{ ucfirst($repository->visibility) }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs uppercase tracking-wide">Default Branch</div>
                <div class="text-2xl font-black text-blue-400 mt-2">{{ $repository->defaultBranch?->name ?? '-' }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs uppercase tracking-wide">Branches</div>
                <div class="text-2xl font-black text-white mt-2">{{ $repository->branches->count() }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs uppercase tracking-wide">Collaborators</div>
                <div class="text-2xl font-black text-white mt-2">{{ $repository->collaborators->count() }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10 xl:col-span-1">
                <h2 class="text-white text-lg font-bold mb-4">Repository Settings</h2>
                @if($canManage)
                    <form method="POST" action="{{ route('dashboard.code_repository.update', $repository) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="text-white/60 text-xs uppercase tracking-wide">Name</label>
                            <input type="text" name="name" value="{{ old('name', $repository->name) }}" class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="text-white/60 text-xs uppercase tracking-wide">Visibility</label>
                            <select name="visibility" class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500">
                                <option value="private" class="bg-[#01020a]" @selected(old('visibility', $repository->visibility) === 'private')>Private</option>
                                <option value="public" class="bg-[#01020a]" @selected(old('visibility', $repository->visibility) === 'public')>Public</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-white/60 text-xs uppercase tracking-wide">Description</label>
                            <textarea name="description" rows="4" class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500">{{ old('description', $repository->description) }}</textarea>
                        </div>
                        <label class="flex items-center gap-2 text-white/70 text-sm">
                            <input type="checkbox" name="is_archived" value="1" @checked(old('is_archived', $repository->is_archived))>
                            Archived
                        </label>
                        <button type="submit" class="w-full px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-all">
                            Save Settings
                        </button>
                    </form>

                    <form method="POST" action="{{ route('dashboard.code_repository.destroy', $repository) }}" class="mt-4" onsubmit="return confirm('Delete this repository? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 rounded-lg bg-red-600/20 border border-red-500/30 text-red-300 hover:bg-red-600/30 transition-all font-semibold">
                            Delete Repository
                        </button>
                    </form>
                @else
                    <p class="text-white/60">You can view this repository but only admins/maintainers can update settings.</p>
                @endif
            </div>

            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10 xl:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-white text-lg font-bold">Branches</h2>
                </div>

                @if($canManage)
                    <form method="POST" action="{{ route('dashboard.code_repository.branches.store', $repository) }}" class="flex gap-2 mb-4">
                        @csrf
                        <input type="text" name="name" placeholder="feature/auth-refactor" class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500" required>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Create Branch</button>
                    </form>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[700px]">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="py-3 pr-3 text-left text-white/60 font-semibold">Name</th>
                                <th class="py-3 pr-3 text-left text-white/60 font-semibold">Default</th>
                                <th class="py-3 pr-3 text-left text-white/60 font-semibold">Protection</th>
                                <th class="py-3 text-left text-white/60 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($repository->branches as $branch)
                                <tr class="border-b border-white/5 align-top">
                                    <td class="py-3 pr-3 text-white font-semibold">{{ $branch->name }}</td>
                                    <td class="py-3 pr-3 text-white/80">{{ $branch->is_default ? 'Yes' : 'No' }}</td>
                                    <td class="py-3 pr-3 text-white/80">{{ $branch->is_protected ? 'Protected' : 'Open' }}</td>
                                    <td class="py-3">
                                        @if($canManage)
                                            @if(! $branch->is_default)
                                                <form method="POST" action="{{ route('dashboard.code_repository.branches.default', [$repository, $branch]) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-white text-xs">Set Default</button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('dashboard.code_repository.branches.protection', [$repository, $branch]) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="px-2 py-1 rounded bg-yellow-500/20 border border-yellow-500/30 text-yellow-300 hover:bg-yellow-500/30 text-xs">
                                                    {{ $branch->is_protected ? 'Unprotect' : 'Protect' }}
                                                </button>
                                            </form>

                                            @if(! $branch->is_default)
                                                <form method="POST" action="{{ route('dashboard.code_repository.branches.destroy', [$repository, $branch]) }}" class="inline" onsubmit="return confirm('Delete this branch?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-2 py-1 rounded bg-red-500/20 border border-red-500/30 text-red-300 hover:bg-red-500/30 text-xs">Delete</button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-white/40 text-xs">No write access</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
            <h2 class="text-white text-lg font-bold mb-4">Collaborators</h2>

            @if($canManage)
                <form method="POST" action="{{ route('dashboard.code_repository.collaborators.store', $repository) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-4">
                    @csrf
                    <select name="user_id" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500" required>
                        <option value="" class="bg-[#01020a]">Select user</option>
                        @foreach($availableUsers as $user)
                            <option value="{{ $user->id }}" class="bg-[#01020a]">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    <select name="role" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500" required>
                        <option value="developer" class="bg-[#01020a]">Developer</option>
                        <option value="maintainer" class="bg-[#01020a]">Maintainer</option>
                        <option value="admin" class="bg-[#01020a]">Admin</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Add Collaborator</button>
                </form>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="py-3 pr-3 text-left text-white/60 font-semibold">User</th>
                            <th class="py-3 pr-3 text-left text-white/60 font-semibold">Role</th>
                            <th class="py-3 pr-3 text-left text-white/60 font-semibold">Added</th>
                            <th class="py-3 text-left text-white/60 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($repository->collaborators as $collaborator)
                            <tr class="border-b border-white/5 align-top">
                                <td class="py-3 pr-3">
                                    <div class="text-white font-semibold">{{ $collaborator->user->name }}</div>
                                    <div class="text-white/50 text-xs">{{ $collaborator->user->email }}</div>
                                </td>
                                <td class="py-3 pr-3 text-white/80 capitalize">{{ $collaborator->role }}</td>
                                <td class="py-3 pr-3 text-white/60">{{ $collaborator->created_at->diffForHumans() }}</td>
                                <td class="py-3">
                                    @if($canManage)
                                        <form method="POST" action="{{ route('dashboard.code_repository.collaborators.destroy', [$repository, $collaborator]) }}" onsubmit="return confirm('Remove this collaborator?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 rounded bg-red-500/20 border border-red-500/30 text-red-300 hover:bg-red-500/30 text-xs">Remove</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-white/60">No collaborators yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
