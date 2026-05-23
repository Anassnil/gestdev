@extends('layouts.dashboard')

@section('dashboard-content')
<style>
    /* Pulse Animation for active state */
    @keyframes pulse-border {
        0%, 100% { border-color: rgba(59, 130, 246, 0.2); }
        50% { border-color: rgba(59, 130, 246, 0.6); }
    }

    .board-card {
        background: rgba(13, 15, 70, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.05);
        transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
        position: relative;
        overflow: hidden;
    }

    .board-card:hover {
        background: rgba(20, 25, 100, 0.6);
        transform: scale(1.02) translateY(-5px);
        box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.5);
        border-color: rgba(99, 102, 241, 0.3);
    }

    /* Control Dock Animation */
    .control-dock {
        transform: translateY(10px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .board-card:hover .control-dock {
        transform: translateY(0);
        opacity: 1;
    }

    /* Action Button Styles */
    .btn-action {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        transition: all 0.2s ease;
        backdrop-filter: blur(8px);
    }

    .btn-edit {
        background: rgba(99, 102, 241, 0.15);
        border: 1px solid rgba(99, 102, 241, 0.2);
        color: #818cf8;
    }
    .btn-edit:hover {
        background: #6366f1;
        color: white;
        box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
    }

    .btn-delete {
        background: rgba(244, 63, 94, 0.1);
        border: 1px solid rgba(244, 63, 94, 0.2);
        color: #fb7185;
    }
    .btn-delete:hover {
        background: #f43f5e;
        color: white;
        box-shadow: 0 0 15px rgba(244, 63, 94, 0.4);
    }

    /* Animated background gradient shine */
    .board-card::after {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: linear-gradient(45deg, transparent, rgba(59, 130, 246, 0.05), transparent);
        transform: rotate(45deg) translateY(-100%);
        transition: transform 0.8s;
    }

    .board-card:hover::after {
        transform: rotate(45deg) translateY(100%);
    }

    /* ─── BOARD EDIT MODAL ─── */
    .board-modal-overlay {
        position: fixed; inset: 0;
        background: rgba(2, 6, 23, 0.52);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        display: flex; align-items: center; justify-content: center;
        padding: 1.5rem;
        z-index: 60;
    }
    .board-modal-panel {
        background: #10142a;
        border-radius: 1.5rem;
        border: 1px solid rgba(255,255,255,0.12);
        padding: 2rem;
        width: 100%; max-width: 480px;
        box-shadow: 0 22px 38px -16px rgba(0,0,0,0.6);
    }
    .board-modal-title {
        font-size: 13px; font-weight: 900; text-transform: uppercase;
        letter-spacing: 0.08em; color: rgba(255,255,255,0.86); margin-bottom: 1.5rem;
    }
    .board-modal-input {
        width: 100%; padding: 10px 14px; border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.14);
        background: rgba(255,255,255,0.04); color: #fff;
        font-size: 14px; outline: none; transition: border-color 0.2s;
        margin-bottom: 0.75rem;
    }
    .board-modal-input:focus { border-color: #6366F1; }
    .board-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 1rem; }
    .board-modal-cancel {
        border-radius: 10px; padding: 8px 14px; font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.06em;
        background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.78);
        transition: all 0.2s ease;
    }
    .board-modal-cancel:hover { background: rgba(255,255,255,0.14); color: #fff; }
    .board-modal-confirm {
        border-radius: 10px; padding: 8px 20px; font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.06em;
        background: #6366F1; color: #fff;
        box-shadow: 0 4px 14px rgba(99,102,241,0.25); transition: all 0.2s ease;
    }
    .board-modal-confirm:hover { background: #5558e8; }
    .board-modal-delete { background: #f43f5e; box-shadow: 0 4px 14px rgba(244,63,94,0.25); }
    .board-modal-delete:hover { background: #e11d48; }

    /* ─── LIGHT MODE ─── */
    [data-theme="light"] .board-card {
        background: #ffffff;
        border-color: rgba(31,41,55,0.10);
        box-shadow: 0 4px 16px -8px rgba(31,41,55,0.12);
    }
    [data-theme="light"] .board-card:hover {
        background: #F1F5F9;
        border-color: rgba(99,102,241,0.30);
        box-shadow: 0 20px 40px -12px rgba(31,41,55,0.16);
    }
    [data-theme="light"] .board-card::after { display: none; }
    [data-theme="light"] .btn-edit {
        background: rgba(99,102,241,0.10);
        border-color: rgba(99,102,241,0.22);
        color: #6366F1;
    }
    [data-theme="light"] .btn-delete {
        background: rgba(244,63,94,0.08);
        border-color: rgba(244,63,94,0.18);
        color: #f43f5e;
    }
    [data-theme="light"] .board-modal-overlay { background: rgba(31,41,55,0.28); }
    [data-theme="light"] .board-modal-panel {
        background: #F8FAFC;
        border-color: rgba(31,41,55,0.12);
        box-shadow: 0 22px 38px -20px rgba(31,41,55,0.22);
    }
    [data-theme="light"] .board-modal-title { color: #1F2937; }
    [data-theme="light"] .board-modal-input {
        background: #fff;
        border-color: rgba(31,41,55,0.14);
        color: #1F2937;
    }
    [data-theme="light"] .board-modal-input:focus { border-color: #6366F1; }
    [data-theme="light"] .board-modal-input::placeholder { color: rgba(31,41,55,0.35); }
    [data-theme="light"] .board-modal-cancel {
        background: rgba(31,41,55,0.06); color: rgba(31,41,55,0.60);
    }
    [data-theme="light"] .board-modal-cancel:hover { background: rgba(31,41,55,0.10); color: #1F2937; }
    /* text / border / bg utilities */
    [data-theme="light"] .text-white { color: #1F2937 !important; }
    [data-theme="light"] .text-white\/40 { color: rgba(31,41,55,0.45) !important; }
    [data-theme="light"] .text-white\/30 { color: rgba(31,41,55,0.40) !important; }
    [data-theme="light"] .text-white\/20 { color: rgba(31,41,55,0.28) !important; }
    [data-theme="light"] .border-white\/5 { border-color: rgba(31,41,55,0.08) !important; }
    [data-theme="light"] .border-dashed { border-color: rgba(31,41,55,0.14) !important; }
    [data-theme="light"] .text-indigo-400 { color: #6366F1 !important; }
    [data-theme="light"] .text-indigo-300 { color: #6366F1 !important; }
    [data-theme="light"] .text-violet-300 { color: #7C3AED !important; }
    /* filter tabs */
    [data-theme="light"] .filter-tab { border-color: rgba(31,41,55,0.12) !important; }
    /* create board input */
    [data-theme="light"] input[name="name"] {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.14) !important;
        color: #1F2937 !important;
    }
    [data-theme="light"] input[name="name"]::placeholder { color: rgba(31,41,55,0.35) !important; }
</style>

<div class="pt-12 px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10">
            <div class="animate-in fade-in slide-in-from-left-8 duration-700">
                <h1 class="text-5xl font-black italic uppercase tracking-tighter text-white">Project Matrix</h1>
                <p class="text-white/40 mt-3 italic tracking-widest uppercase text-[10px]">Select an active mission sector</p>
            </div>

            <form method="POST" action="{{ route('dashboard.planning.storeBoard') }}" class="flex gap-3 w-full md:w-auto mt-8 md:mt-0">
                @csrf
                <input name="name" placeholder="Board Identity..." class="w-full md:w-64 px-5 py-4 rounded-xl bg-[#02010A] border border-white/10 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all outline-none" required>
                <button class="px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-black uppercase tracking-widest text-[11px] transition-all hover:scale-105 active:scale-95 shadow-lg shadow-blue-600/20">Initialize</button>
            </form>
        </div>

        {{-- Filter tabs --}}
        @php
            $ownCount    = $boards->count();
            $sharedCount = ($sharedBoards ?? collect())->count();
        @endphp
        <div class="flex items-center gap-2 mb-8">
            <button id="filter-all"
                class="filter-tab active-tab px-5 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all border border-white/10 bg-blue-600 text-white">
                All <span class="ml-1 opacity-60">{{ $ownCount + $sharedCount }}</span>
            </button>
            <button id="filter-mine"
                class="filter-tab px-5 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all border border-white/10 bg-white/5 text-white/50 hover:bg-white/10 hover:text-white">
                Mine <span class="ml-1 opacity-60">{{ $ownCount }}</span>
            </button>
            @if($sharedCount > 0)
            <button id="filter-shared"
                class="filter-tab px-5 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all border border-white/10 bg-white/5 text-white/50 hover:bg-white/10 hover:text-white">
                Shared with me <span class="ml-1 opacity-60">{{ $sharedCount }}</span>
            </button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="boards-grid">
            @php $allBoards = $boards->concat($sharedBoards ?? collect()); @endphp
            @forelse($allBoards as $board)
                @php $isShared = !isset($board->user_id) || $board->user_id !== auth()->id(); @endphp
                <a href="{{ route('dashboard.planning.show', $board) }}"
                   class="board-card p-8 rounded-[2.5rem] block group relative"
                   data-type="{{ $isShared ? 'shared' : 'mine' }}"
                   @if(!$isShared)
                       data-board-id="{{ $board->id }}"
                       data-update-url="{{ route('dashboard.planning.update', $board) }}"
                       data-delete-url="{{ route('dashboard.planning.destroy', $board) }}"
                   @endif>

                    {{-- Shared badge (top-right corner) --}}
                    @if($isShared)
                    <div class="absolute top-5 right-5 flex items-center gap-1.5">
                        @if(($board->pivot_role ?? null) === 'editor')
                            <span class="text-[9px] font-black uppercase tracking-widest text-blue-300 bg-blue-600/20 border border-blue-500/30 px-2.5 py-0.5 rounded-full">Editor</span>
                        @else
                            <span class="text-[9px] font-black uppercase tracking-widest text-white/40 bg-white/5 border border-white/10 px-2.5 py-0.5 rounded-full">Viewer</span>
                        @endif
                    </div>
                    @endif

                    <div class="flex justify-between items-start mb-10">
                        <div class="p-4 {{ $isShared ? 'bg-violet-500/10 text-violet-400 group-hover:bg-violet-500' : 'bg-indigo-500/10 text-indigo-400 group-hover:bg-indigo-500' }} rounded-2xl group-hover:text-white transition-all duration-500">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0-2v2m0 6V12m0 0a2 2 0 100 4m0-4a2 2 0 110 4m12-8a2 2 0 100-4m0 4a2 2 0 110-4m0 0v2m0 6v-2m0 0a2 2 0 100 4m0-4a2 2 0 110 4m-8-2v-4m0 0V4m0 16v-4m0 0h4m-4 0H8"/></svg>
                        </div>

                        @if(!$isShared)
                        <div class="control-dock flex gap-2">
                            <button data-action="edit" title="Configure Sector" class="btn-action btn-edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                            </button>
                            <button data-action="delete" title="Terminate Sector" class="btn-action btn-delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        @if(!$isShared)
                        <div class="text-[9px] font-black uppercase tracking-[0.3em] text-white/20 mb-1">Sector Entry ID: {{ $board->id }}</div>
                        @endif
                        <h3 class="text-2xl font-bold text-white {{ $isShared ? 'group-hover:text-violet-300' : 'group-hover:text-indigo-300' }} transition-colors duration-300">{{ $board->name }}</h3>
                        <p class="text-xs text-white/40 italic leading-relaxed line-clamp-1">{{ $board->description ?? 'Status: Operational' }}</p>
                    </div>

                    <div class="mt-10 pt-6 border-t border-white/5">
                        @if($isShared && $board->user)
                        <div class="flex items-center gap-2 mb-3">
                            @if($board->user->avatar_url)
                                <img src="{{ $board->user->avatar_url }}" class="w-5 h-5 rounded-md object-cover border border-white/15">
                            @else
                                <div class="w-5 h-5 rounded-md bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-[8px] font-black text-white">{{ $board->user->initials }}</div>
                            @endif
                            <span class="text-[10px] text-white/35">by {{ $board->user->name }}</span>
                            <span class="ml-auto text-[10px] font-mono text-white/30">{{ $board->tasks_count ?? 0 }} tasks</span>
                        </div>
                        @else
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[9px] font-black uppercase tracking-widest text-white/30">Sync Status</span>
                            <span class="text-[10px] font-mono text-indigo-400">{{ $board->tasks_count ?? 0 }} Assets</span>
                        </div>
                        @endif
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r {{ $isShared ? 'from-violet-600 to-blue-400' : 'from-indigo-600 to-blue-400' }} w-1/3 transition-all duration-1000 group-hover:w-full"></div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-1 md:col-span-3 py-32 text-center border-2 border-dashed border-white/5 rounded-[3rem]">
                    <div class="inline-flex p-6 bg-white/5 rounded-full mb-6">
                        <svg class="w-10 h-10 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <p class="text-white/30 font-black uppercase tracking-[0.3em] italic">No active missions found in matrix.</p>
                </div>
            @endforelse
        </div>

    </div>
</div>

<!-- EDIT BOARD MODAL -->
<div id="board-edit-modal" class="board-modal-overlay" style="display:none">
    <div class="board-modal-panel">
        <div class="board-modal-title">Update Sector</div>
        <input id="board-edit-name" class="board-modal-input" type="text" placeholder="Board Name">
        <textarea id="board-edit-desc" class="board-modal-input" rows="3" placeholder="Description (optional)" style="resize:none;"></textarea>
        <div class="board-modal-actions">
            <button id="board-edit-cancel" type="button" class="board-modal-cancel">Cancel</button>
            <button id="board-edit-confirm" type="button" class="board-modal-confirm">Save Changes</button>
        </div>
    </div>
</div>

<!-- DELETE BOARD MODAL -->
<div id="board-delete-modal" class="board-modal-overlay" style="display:none">
    <div class="board-modal-panel">
        <div class="board-modal-title">Confirm Deletion</div>
        <p style="font-size:13px;color:rgba(255,255,255,0.55);margin-bottom:1.25rem;">This will permanently delete the board and all its data. This cannot be undone.</p>
        <div class="board-modal-actions">
            <button id="board-delete-cancel" type="button" class="board-modal-cancel">Cancel</button>
            <button id="board-delete-confirm" type="button" class="board-modal-confirm board-modal-delete">Delete Board</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const csrf = '{{ csrf_token() }}';
    let _activeCard = null;
    let _editUrl = null;
    let _deleteUrl = null;

    const editModal   = document.getElementById('board-edit-modal');
    const deleteModal = document.getElementById('board-delete-modal');

    // close on backdrop click
    editModal.addEventListener('click',   e => { if (e.target === editModal) editModal.style.display = 'none'; });
    deleteModal.addEventListener('click', e => { if (e.target === deleteModal) deleteModal.style.display = 'none'; });
    document.getElementById('board-edit-cancel').addEventListener('click',   () => editModal.style.display = 'none');
    document.getElementById('board-delete-cancel').addEventListener('click', () => deleteModal.style.display = 'none');

    document.getElementById('board-edit-confirm').addEventListener('click', function() {
        const name = document.getElementById('board-edit-name').value.trim();
        if (!name) return;
        const description = document.getElementById('board-edit-desc').value.trim();
        fetch(_editUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ name, description })
        }).then(r => r.json()).then(data => {
            if (data?.ok) {
                _activeCard.querySelector('h3').innerText = data.board.name;
                _activeCard.querySelector('p').innerText = data.board.description || 'Status: Operational';
                editModal.style.display = 'none';
            }
        }).catch(() => console.error('Update Failed'));
    });

    document.getElementById('board-delete-confirm').addEventListener('click', function() {
        fetch(_deleteUrl, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data?.ok) {
                _activeCard.classList.add('scale-95', 'opacity-0');
                setTimeout(() => _activeCard.remove(), 300);
                deleteModal.style.display = 'none';
            }
        }).catch(() => console.error('Delete Failed'));
    });

    document.querySelectorAll('[data-board-id]').forEach(function(card){
        const editBtn = card.querySelector('[data-action="edit"]');
        const deleteBtn = card.querySelector('[data-action="delete"]');

        editBtn?.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            _activeCard = card;
            _editUrl = card.getAttribute('data-update-url');
            document.getElementById('board-edit-name').value = card.querySelector('h3')?.innerText || '';
            document.getElementById('board-edit-desc').value = card.querySelector('p')?.innerText || '';
            editModal.style.display = 'flex';
        });

        deleteBtn?.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            _activeCard = card;
            _deleteUrl = card.getAttribute('data-delete-url');
            deleteModal.style.display = 'flex';
        });
    });

    // ── Filter tabs ──────────────────────────────────────────
    const tabs  = document.querySelectorAll('.filter-tab');
    const cards = document.querySelectorAll('#boards-grid [data-type]');
    const activeClasses   = ['bg-blue-600', 'text-white', 'border-blue-600/50'];
    const inactiveClasses = ['bg-white/5', 'text-white/50', 'border-white/10'];

    function setFilter(filter) {
        tabs.forEach(t => {
            const isActive = t.id === `filter-${filter}`;
            activeClasses.forEach(c   => t.classList.toggle(c, isActive));
            inactiveClasses.forEach(c => t.classList.toggle(c, !isActive));
            t.classList.toggle('hover:bg-white/10', !isActive);
            t.classList.toggle('hover:text-white',  !isActive);
        });
        cards.forEach(card => {
            const type = card.dataset.type;
            const show = filter === 'all' || type === filter;
            card.style.display = show ? '' : 'none';
        });
    }

    document.getElementById('filter-all')?.addEventListener('click',    () => setFilter('all'));
    document.getElementById('filter-mine')?.addEventListener('click',   () => setFilter('mine'));
    document.getElementById('filter-shared')?.addEventListener('click', () => setFilter('shared'));
});
</script>
@endsection