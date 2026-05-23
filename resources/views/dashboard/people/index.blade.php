@extends('layouts.dashboard')

@section('dashboard-content')
<div class="space-y-6 p-2">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight">Community</h1>
            <p class="text-white/50 mt-1">Discover members, manage associates, and collaborate in project group chats.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.people.inbox') }}" class="flex items-center gap-2 px-4 py-2 glass border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 16V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h11l4 4v-4z"/>
                </svg>
                Inbox
            </a>
            <a href="{{ route('dashboard.people.inbox') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 transition-all text-sm font-semibold text-white">
                Create Group Chat
            </a>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('dashboard.people.index', ['scope' => 'all', 'q' => $q ?: null]) }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-all {{ $scope === 'all' ? 'bg-blue-600 text-white border-blue-500/60' : 'bg-white/5 text-white/70 border-white/10 hover:bg-white/10' }}">
            All Members ({{ $allMembersCount }})
        </a>
        <a href="{{ route('dashboard.people.index', ['scope' => 'associates', 'q' => $q ?: null]) }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-all {{ $scope === 'associates' ? 'bg-blue-600 text-white border-blue-500/60' : 'bg-white/5 text-white/70 border-white/10 hover:bg-white/10' }}">
            Associates ({{ $associatesCount }})
        </a>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('dashboard.people.index') }}" class="flex gap-2">
        <input type="hidden" name="scope" value="{{ $scope }}">
        <input
            type="text"
            name="q"
            value="{{ $q }}"
            placeholder="Search by name or email…"
            class="flex-1 px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-blue-500 text-sm"
        >
        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition-all">Search</button>
    </form>

    @if($associates->isNotEmpty())
        <div class="glass rounded-2xl border border-white/10 p-4 sm:p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-white/50">My Associates</h2>
                <span class="text-xs text-white/40">{{ $associates->count() }} total</span>
            </div>
            <div class="flex flex-wrap gap-2.5">
                @foreach($associates as $associate)
                    <a href="{{ route('dashboard.people.show', $associate) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        @if($associate->avatar_url)
                            <img src="{{ $associate->avatar_url }}" alt="{{ $associate->name }}" class="w-7 h-7 rounded-lg object-cover border border-white/15">
                        @else
                            <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-[10px] font-black text-white/90">
                                {{ $associate->initials }}
                            </span>
                        @endif
                        <span class="text-sm font-semibold">{{ $associate->name }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-600/20 text-blue-300 border border-blue-500/30 uppercase tracking-wider">
                            {{ $associate->relationship_type }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($people->isEmpty())
        <div class="text-center py-16 text-white/40">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8z"/>
            </svg>
            <p class="font-semibold">{{ $scope === 'associates' ? 'No associates found' : 'No users found' }}</p>
            @if($scope === 'associates')
                <p class="text-xs text-white/30 mt-1">Add people as associates from the All Members tab.</p>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($people as $person)
                <div class="glass p-4 rounded-2xl border border-white/10 hover:border-blue-500/40 transition-all flex items-center gap-4">
                    {{-- Avatar --}}
                    @if($person->avatar_url)
                        <img src="{{ $person->avatar_url }}" alt="{{ $person->name }}" class="w-12 h-12 rounded-xl object-cover border border-white/15 shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-sm font-black text-white/90 shrink-0">
                            {{ $person->initials }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="font-bold truncate">{{ $person->name }}</div>
                        @if($person->position)
                            <div class="text-xs text-blue-400 truncate font-medium">{{ $person->position }}</div>
                        @else
                            <div class="text-xs text-white/50 truncate">{{ $person->email }}</div>
                        @endif
                    </div>
                    <div class="flex flex-col gap-1 shrink-0">
                        <a href="{{ route('dashboard.people.show', $person) }}" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold transition-all text-center">View</a>
                        <a href="{{ route('dashboard.people.chat', $person) }}" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-xs font-semibold transition-all text-center">Message</a>
                        @if($associations->has($person->id))
                            <div class="px-2 py-1 rounded-lg bg-emerald-600/20 border border-emerald-500/30 text-[10px] text-emerald-300 text-center font-semibold uppercase tracking-wider">
                                {{ $associations[$person->id]->relationship_type }}
                            </div>
                            <form method="POST" action="{{ route('dashboard.people.associates.remove', $person) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold transition-all text-center text-white/70">Remove</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('dashboard.people.associates.add', $person) }}" class="flex gap-1">
                                @csrf
                                <select name="relationship_type" class="w-full px-2 py-1.5 rounded-lg bg-white/5 border border-white/10 text-[11px] text-white/80 focus:outline-none">
                                    <option value="associate">Associate</option>
                                    <option value="friend">Friend</option>
                                    <option value="partner">Partner</option>
                                </select>
                                <button type="submit" class="px-2 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-[11px] font-semibold transition-all">Add</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-2">{{ $people->links() }}</div>
    @endif
</div>
@endsection
