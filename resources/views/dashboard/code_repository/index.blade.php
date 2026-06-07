@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Code Repository</h1>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        const openBtns = document.querySelectorAll('.openCreateRepo');
                        const modal = document.getElementById('createRepoModal');
                        const overlay = document.getElementById('createRepoOverlay');
                        const closeBtn = document.getElementById('closeCreateRepo');
                        const cancelBtn = document.getElementById('cancelCreateRepo');

                        function show() { if (modal) { modal.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); } }
                        function hide() { if (modal) { modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); } }

                        if (openBtns && openBtns.length) openBtns.forEach(b => b.addEventListener('click', show));
                        if (closeBtn) closeBtn.addEventListener('click', hide);
                        if (cancelBtn) cancelBtn.addEventListener('click', hide);
                        if (overlay) overlay.addEventListener('click', hide);
                        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') hide(); });
                    });
                </script>
                <p class="text-white/60 mt-2">Phase 1 foundation: repositories, branches, collaborators, and PR linking.</p>
            </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">
            <div class="lg:col-span-1">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="glass rounded-2xl p-4 border border-white/10">
                        <div class="text-white/60 text-xs uppercase tracking-wide">Repositories</div>
                        <div class="text-3xl font-black text-white mt-2">{{ $stats['repositories_total'] }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4 border border-white/10">
                        <div class="text-white/60 text-xs uppercase tracking-wide">Public / Private</div>
                        <div class="text-3xl font-black text-blue-400 mt-2">{{ $stats['repositories_public'] }} / {{ $stats['repositories_private'] }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4 border border-white/10">
                        <div class="text-white/60 text-xs uppercase tracking-wide">Branches Total</div>
                        <div class="text-3xl font-black text-green-400 mt-2">{{ $stats['branches_total'] }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4 border border-white/10">
                        <div class="text-white/60 text-xs uppercase tracking-wide">Tasks With PR</div>
                        <div class="text-3xl font-black text-white mt-2">{{ $stats['with_pr'] }}</div>
                    </div>
                </div>

            </div>

            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-white text-xl font-bold">Repositories</h2>
                    <div>
                        <button type="button" class="openCreateRepo px-3 py-2 bg-blue-600 border border-blue-700 rounded-xl hover:bg-blue-700 transition-all text-sm md:text-base text-white/90">
                            New Repository
                        </button>
                    </div>
                </div>

                @if($repositories->count() === 0)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6 text-white/60">
                        No repositories yet. Create your first one from the left panel.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[760px]">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="py-3 pr-3 text-left text-white/60 font-semibold">Repository</th>
                                    <th class="py-3 pr-3 text-left text-white/60 font-semibold">Visibility</th>
                                    <th class="py-3 pr-3 text-left text-white/60 font-semibold">Default Branch</th>
                                    <th class="py-3 pr-3 text-left text-white/60 font-semibold">Branches</th>
                                    <th class="py-3 pr-3 text-left text-white/60 font-semibold">Collaborators</th>
                                    <th class="py-3 text-left text-white/60 font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($repositories as $repository)
                                    <tr class="border-b border-white/5 align-top">
                                        <td class="py-3 pr-3">
                                            <div class="text-white font-semibold">{{ $repository->name }}</div>
                                            <div class="text-white/50 text-xs">{{ $repository->slug }}</div>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $repository->visibility === 'public' ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300' }}">
                                                {{ ucfirst($repository->visibility) }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-3 text-white/80">{{ $repository->defaultBranch?->name ?? 'main' }}</td>
                                        <td class="py-3 pr-3 text-white/80">{{ $repository->branches_count }}</td>
                                        <td class="py-3 pr-3 text-white/80">{{ $repository->collaborators_count }}</td>
                                        <td class="py-3">
                                            <a href="{{ route('dashboard.code_repository.show', $repository) }}" class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-all font-semibold">
                                                Open
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $repositories->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Create Repository Modal -->
        <div id="createRepoModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
            <div id="createRepoOverlay" class="absolute inset-0 bg-black/60"></div>
            <div class="relative max-w-2xl w-full bg-[#01020a] rounded-2xl border border-white/10 p-6 glass">
                <div class="flex items-start justify-between">
                    <h3 class="text-white text-lg font-bold">Create Repository</h3>
                    <button id="closeCreateRepo" class="text-white/60 hover:text-white">✕</button>
                </div>

                <form method="POST" action="{{ route('dashboard.code_repository.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-white/60 text-xs uppercase tracking-wide">Name</label>
                        <input type="text" name="name" required class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500" placeholder="Core API">
                    </div>
                    <div>
                        <label class="text-white/60 text-xs uppercase tracking-wide">Slug (optional)</label>
                        <input type="text" name="slug" class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500" placeholder="core-api">
                    </div>
                    <div>
                        <label class="text-white/60 text-xs uppercase tracking-wide">Visibility</label>
                        <select name="visibility" class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500">
                            <option value="private" class="bg-[#01020a]">Private</option>
                            <option value="public" class="bg-[#01020a]">Public</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-white/60 text-xs uppercase tracking-wide">Description</label>
                        <textarea name="description" rows="3" class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500" placeholder="Repository purpose..."></textarea>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button type="button" id="cancelCreateRepo" class="px-4 py-2 rounded-lg bg-white/5 text-white/70 hover:bg-white/10">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Create Repository</button>
                    </div>
                </form>
            </div>
        </div>


        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
            <h2 class="text-white text-xl font-bold mb-4">Task PR Links (Compatibility Panel)</h2>

            @if($tasks->count() === 0)
                <div class="rounded-xl border border-white/10 bg-white/5 p-6 text-white/60">
                    No tasks found yet. Create tasks from Planning to start linking PRs.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[900px]">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="py-3 pr-3 text-left text-white/60 font-semibold">Task</th>
                                <th class="py-3 pr-3 text-left text-white/60 font-semibold">Board</th>
                                <th class="py-3 pr-3 text-left text-white/60 font-semibold">Status</th>
                                <th class="py-3 pr-3 text-left text-white/60 font-semibold">Assignee</th>
                                <th class="py-3 pr-3 text-left text-white/60 font-semibold">PR URL</th>
                                <th class="py-3 text-left text-white/60 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                <tr class="border-b border-white/5 align-top">
                                    <td class="py-3 pr-3">
                                        <div class="text-white font-semibold">{{ $task->title }}</div>
                                        <div class="text-white/50 text-xs">#{{ $task->id }}</div>
                                    </td>
                                    <td class="py-3 pr-3 text-white/80">{{ $task->board->name ?? '-' }}</td>
                                    <td class="py-3 pr-3">
                                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $task->status === 'done' ? 'bg-green-500/20 text-green-300' : ($task->status === 'in_progress' ? 'bg-yellow-500/20 text-yellow-300' : 'bg-blue-500/20 text-blue-300') }}">
                                            {{ str_replace('_', ' ', $task->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-3 text-white/80">{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                                    <td class="py-3 pr-3">
                                        <form method="POST" action="{{ route('dashboard.code_repository.tasks.updatePr', $task) }}" class="flex gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input
                                                type="url"
                                                name="pr_url"
                                                value="{{ old('pr_url', $task->pr_url) }}"
                                                placeholder="https://github.com/org/repo/pull/123"
                                                class="w-48 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder:text-white/40 focus:outline-none focus:border-blue-500"
                                            >
                                    </td>
                                    <td class="py-3">
                                            <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-all font-semibold">
                                                Save
                                            </button>
                                            @if($task->pr_url)
                                                <a href="{{ $task->pr_url }}" target="_blank" rel="noopener noreferrer" class="ml-2 px-3 py-2 rounded-lg bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 hover:bg-emerald-600/30 transition-all font-semibold">
                                                    Open
                                                </a>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
