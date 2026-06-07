@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-screen-2xl mx-auto">
        {{-- ── PAGE HEADER + BACK BUTTON (same row) ── --}}
        <div class="flex items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">{{ $repository->name }}</h1>
                <p class="text-white/60 mt-2">{{ $repository->slug }} • owned by {{ $repository->owner->name }}</p>
            </div>
            <a href="{{ route('dashboard.code_repository.index') }}"
                class="back-repo-btn group flex-shrink-0 inline-flex items-center gap-3 px-5 py-3 rounded-xl
                       bg-gradient-to-r from-indigo-600/20 to-violet-600/20
                       border border-indigo-500/30
                       hover:from-indigo-600/40 hover:to-violet-600/40
                       hover:border-indigo-400/60
                       hover:shadow-[0_0_18px_rgba(99,102,241,0.35)]
                       transition-all duration-300">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-400/30 group-hover:bg-indigo-500/40 transition-all duration-300">
                    <svg class="w-4 h-4 text-indigo-300 transform group-hover:-translate-x-0.5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </span>
                <div>
                    <div class="text-indigo-200 font-semibold text-sm group-hover:text-white transition-colors duration-300">Back to Repositories</div>
                    <div class="text-indigo-400/70 text-xs group-hover:text-indigo-300/80 transition-colors duration-300">Return to repository list</div>
                </div>
            </a>
        </div>

        @if(session('success'))
            <div class="flash-success mb-6 rounded-xl border border-green-500/30 bg-green-500/10 text-green-300 px-4 py-3">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="flash-error mb-6 rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 px-4 py-3">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="flash-error mb-6 rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">

            {{-- LEFT – 4 stat cards in 2×2 grid --}}
            <div class="lg:col-span-1">
                <div class="grid grid-cols-2 gap-4">
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
                        <div class="text-2xl font-black text-green-400 mt-2">{{ $repository->branches->count() }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4 border border-white/10">
                        <div class="text-white/60 text-xs uppercase tracking-wide">Collaborators</div>
                        <div class="text-2xl font-black text-white mt-2">{{ $repository->collaborators->count() }}</div>
                    </div>
                </div>

                {{-- Activity chart button --}}
                <button type="button" onclick="openActivityChart()"
                    class="mt-4 flex items-center gap-3 w-full px-5 py-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-left">
                    <svg class="w-5 h-5 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <div>
                        <div class="text-white font-semibold">Activity Over Time</div>
                        <div class="text-white/50 text-xs">Branches &amp; collaborators — last 6 months</div>
                    </div>
                </button>
            </div>

            {{-- RIGHT – 3 stacked action buttons --}}
            <div class="lg:col-span-2 glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h2 class="text-white text-xl font-bold mb-4">Quick Actions</h2>
                <div class="flex flex-col gap-3">
                    <button type="button" onclick="openModal('modalSettings')"
                        class="flex items-center gap-3 w-full px-5 py-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-left">
                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <div>
                            <div class="text-white font-semibold">Repository Settings</div>
                            <div class="text-white/50 text-xs">Edit name, visibility, description</div>
                        </div>
                    </button>

                    <button type="button" onclick="openModal('modalGithub')"
                        class="flex items-center gap-3 w-full px-5 py-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-left">
                        <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.63-5.37-12-12-12z"/></svg>
                        <div>
                            <div class="text-white font-semibold">Repository Changes (GitHub)</div>
                            <div class="text-white/50 text-xs">Link GitHub repo, view commits &amp; PRs</div>
                        </div>
                    </button>

                    <button type="button" onclick="openModal('modalCollaborators')"
                        class="flex items-center gap-3 w-full px-5 py-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-left">
                        <svg class="w-5 h-5 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <div>
                            <div class="text-white font-semibold">Collaborators</div>
                            <div class="text-white/50 text-xs">Manage team access and roles</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── BRANCHES SECTION (full width, below) ── --}}
        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
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
                                                <button type="submit" class="branch-btn-default px-2 py-1 rounded text-xs font-medium bg-white/10 hover:bg-white/20 text-white border border-white/20">Set Default</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('dashboard.code_repository.branches.protection', [$repository, $branch]) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="branch-btn-protect px-2 py-1 rounded text-xs font-medium bg-yellow-500/20 border border-yellow-500/30 text-yellow-300 hover:bg-yellow-500/30">
                                                {{ $branch->is_protected ? 'Unprotect' : 'Protect' }}
                                            </button>
                                        </form>
                                        @if(! $branch->is_default)
                                            <form method="POST" action="{{ route('dashboard.code_repository.branches.destroy', [$repository, $branch]) }}" class="inline" onsubmit="return confirm('Delete this branch?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="branch-btn-delete px-2 py-1 rounded text-xs font-medium bg-red-500/20 border border-red-500/30 text-red-300 hover:bg-red-500/30">Delete</button>
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
</div>

{{-- ═══════════════════════════════════════════════
     MODAL – Repository Settings
════════════════════════════════════════════════ --}}
<div id="modalSettings" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60" onclick="closeModal('modalSettings')"></div>
    <div class="relative max-w-xl w-full bg-[#01020a] rounded-2xl border border-white/10 p-6 glass max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <h3 class="text-white text-lg font-bold">Repository Settings</h3>
            <button onclick="closeModal('modalSettings')" class="text-white/60 hover:text-white text-xl leading-none">✕</button>
        </div>

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
                <div class="flex gap-2 justify-end pt-2">
                    <button type="button" onclick="closeModal('modalSettings')" class="px-4 py-2 rounded-lg bg-white/5 text-white/70 hover:bg-white/10">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Save Settings</button>
                </div>
            </form>

            <form method="POST" action="{{ route('dashboard.code_repository.destroy', $repository) }}" class="mt-4 border-t border-white/10 pt-4" onsubmit="return confirm('Delete this repository? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-red-600/20 border border-red-500/30 text-red-300 hover:bg-red-600/30 transition-all font-semibold">
                    Delete Repository
                </button>
            </form>
        @else
            <p class="text-white/60">Only admins/maintainers can update settings.</p>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════
     MODAL – Repository Changes (GitHub)
════════════════════════════════════════════════ --}}
<div id="modalGithub" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60" onclick="closeModal('modalGithub')"></div>
    <div class="relative max-w-2xl w-full bg-[#01020a] rounded-2xl border border-white/10 p-6 glass max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <h3 class="text-white text-lg font-bold">Repository Changes (GitHub)</h3>
            <button onclick="closeModal('modalGithub')" class="text-white/60 hover:text-white text-xl leading-none">✕</button>
        </div>

        @if($canManage)
            <form method="POST" action="{{ route('dashboard.code_repository.update', $repository) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                @csrf
                @method('PATCH')
                <input type="text" name="remote_full_name" value="{{ old('remote_full_name', $repository->remote_full_name) }}" placeholder="owner/repo" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500">
                <input type="text" name="remote_token" value="" placeholder="Personal Access Token (leave blank to keep)" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500">
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Save Link</button>
            </form>
            @if($repository->remote_full_name)
                <form method="POST" action="{{ route('dashboard.code_repository.update', $repository) }}" class="mb-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="remote_full_name" value="">
                    <input type="hidden" name="remote_token" value="">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-600/20 border border-red-500/30 text-red-300 hover:bg-red-600/30 transition-all">Unlink</button>
                </form>
            @endif
        @endif

        @if(!empty($repository->remote_full_name))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-sm text-white/70 mb-2">Recent Commits</h4>
                    @if(!empty($remoteChanges['error']))
                        <div class="text-xs text-red-400 mb-2">{{ $remoteChanges['error'] }}</div>
                    @endif
                    @if(count($remoteChanges['commits']) === 0)
                        <div class="text-xs text-white/50 italic">No commits found or unable to fetch.</div>
                    @else
                        <ul class="space-y-3">
                            @foreach($remoteChanges['commits'] as $c)
                                <li class="p-3 bg-white/5 rounded-lg">
                                    <a href="{{ $c['html_url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-white text-sm">{{ $c['commit']['message'] ?? 'Commit' }}</a>
                                    <div class="text-xs text-white/50 mt-1">{{ $c['commit']['author']['name'] ?? '' }} • {{ \Carbon\Carbon::parse($c['commit']['author']['date'] ?? now())->diffForHumans() }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div>
                    <h4 class="text-sm text-white/70 mb-2">Recent Pull Requests</h4>
                    @if(count($remoteChanges['pulls']) === 0)
                        <div class="text-xs text-white/50 italic">No pull requests found or unable to fetch.</div>
                    @else
                        <ul class="space-y-3">
                            @foreach($remoteChanges['pulls'] as $p)
                                <li class="p-3 bg-white/5 rounded-lg">
                                    <a href="{{ $p['html_url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-white text-sm">#{{ $p['number'] }} {{ $p['title'] }}</a>
                                    <div class="text-xs text-white/50 mt-1">{{ $p['user']['login'] ?? '' }} • {{ ucfirst($p['state'] ?? '') }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @else
            <div class="text-xs text-white/50 italic">No GitHub repository linked yet.</div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════
     MODAL – Collaborators
════════════════════════════════════════════════ --}}
<div id="modalCollaborators" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60" onclick="closeModal('modalCollaborators')"></div>
    <div class="relative max-w-2xl w-full bg-[#01020a] rounded-2xl border border-white/10 p-6 glass max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <h3 class="text-white text-lg font-bold">Collaborators</h3>
            <button onclick="closeModal('modalCollaborators')" class="text-white/60 hover:text-white text-xl leading-none">✕</button>
        </div>

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
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Add</button>
            </form>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[540px]">
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

{{-- ═══════════════════════════════════════════════
     MODAL – Activity Over Time
════════════════════════════════════════════════ --}}
<div id="modalActivity" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60" onclick="closeActivityChart()"></div>
    <div class="relative w-full max-w-3xl bg-[#01020a] rounded-2xl border border-white/10 p-6 glass">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-white text-lg font-bold">Activity Over Time</h3>
                <p class="text-white/50 text-xs mt-0.5">Branches &amp; collaborators — last 6 months</p>
            </div>
            <button onclick="closeActivityChart()" class="text-white/60 hover:text-white text-xl leading-none">✕</button>
        </div>
        <div style="position:relative;height:300px;">
            <canvas id="repoActivityChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function(){
    var labels   = @json($chartData['labels']);
    var branches = @json($chartData['branches']);
    var collabs  = @json($chartData['collaborators']);
    var chartInstance = null;

    function getCSSVar(v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); }
    function isLight()    { return document.documentElement.getAttribute('data-theme') === 'light'; }

    function buildChart() {
        var light         = isLight();
        var textPrimary   = getCSSVar('--text-primary')  || (light ? '#1F2937'              : '#ffffff');
        var textMuted     = getCSSVar('--text-muted')    || (light ? 'rgba(31,41,55,0.64)'  : 'rgba(255,255,255,0.5)');
        var gridColor     = light ? 'rgba(31,41,55,0.08)'    : 'rgba(255,255,255,0.05)';
        var tooltipBg     = light ? 'rgba(255,255,255,0.97)' : 'rgba(1,2,10,0.92)';
        var tooltipBody   = light ? 'rgba(31,41,55,0.75)'    : 'rgba(255,255,255,0.7)';
        var tooltipBorder = light ? 'rgba(31,41,55,0.12)'    : 'rgba(255,255,255,0.1)';

        var canvas = document.getElementById('repoActivityChart');
        if (!canvas) return;
        if (chartInstance) { chartInstance.destroy(); }

        chartInstance = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'New Branches',
                        data: branches,
                        backgroundColor: 'rgba(59,130,246,0.55)',
                        borderColor: 'rgba(59,130,246,1)',
                        borderWidth: 2,
                        borderRadius: 6,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Collaborators Added',
                        data: collabs,
                        type: 'line',
                        backgroundColor: 'rgba(168,85,247,0.15)',
                        borderColor: 'rgba(168,85,247,1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(168,85,247,1)',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend:  { labels: { color: textPrimary, font: { size: 12 } } },
                    tooltip: { backgroundColor: tooltipBg, titleColor: textPrimary, bodyColor: tooltipBody, borderColor: tooltipBorder, borderWidth: 1 },
                },
                scales: {
                    x: { ticks: { color: textMuted }, grid: { color: gridColor } },
                    y: { beginAtZero: true, ticks: { color: textMuted, stepSize: 1 }, grid: { color: gridColor } },
                },
            },
        });
    }

    window.openActivityChart = function() {
        var m = document.getElementById('modalActivity');
        if (m) { m.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
        // Build/rebuild after the modal is visible so canvas has dimensions
        setTimeout(buildChart, 60);
    };

    window.closeActivityChart = function() {
        var m = document.getElementById('modalActivity');
        if (m) { m.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
    };

    // Rebuild when theme changes while modal is open
    new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            if (m.attributeName === 'data-theme') {
                var modal = document.getElementById('modalActivity');
                if (modal && !modal.classList.contains('hidden')) { buildChart(); }
            }
        });
    }).observe(document.documentElement, { attributes: true });
})();
</script>

<script>
    function openModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
    }
    function closeModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['modalSettings','modalGithub','modalCollaborators','modalActivity'].forEach(closeModal);
            if (window.closeActivityChart) closeActivityChart();
        }
    });
</script>
@endsection
