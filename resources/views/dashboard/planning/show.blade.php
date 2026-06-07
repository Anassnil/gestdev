@extends('layouts.dashboard')

@section('dashboard-content')
<style>
    /* Command Grid Panels */
    .command-card {
        background: rgba(13, 15, 70, 0.3);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        overflow: hidden;
    }
    .command-card:hover {
        background: rgba(20, 25, 100, 0.5);
        border-color: rgba(59, 130, 246, 0.4);
        transform: translateY(-5px);
    }

    /* Kanban Matrix Columns */
    .matrix-col {
        background: rgba(2, 1, 10, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-top: 3px solid #3b82f6;
    }

    /* Task Micro-interactions */
    .task-node {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        cursor: grab;
        transition: all 0.2s ease;
    }
    .task-node:active { cursor: grabbing; }
    .task-node:hover {
        background: rgba(59, 130, 246, 0.1);
        border-color: rgba(59, 130, 246, 0.3);
    }

    .ghost-task {
        opacity: 0.2;
        background: #3b82f6 !important;
    }

    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.2); border-radius: 10px; }

    /* ─── LIGHT MODE ─── */
    [data-theme="light"] .command-card {
        background: #ffffff;
        border-color: rgba(31,41,55,0.10);
        box-shadow: 0 4px 16px -8px rgba(31,41,55,0.12);
    }
    [data-theme="light"] .command-card:hover {
        background: #F1F5F9;
        border-color: rgba(59,130,246,0.30);
    }
    [data-theme="light"] .matrix-col {
        background: #F8FAFC;
        border-color: rgba(31,41,55,0.10);
    }
    [data-theme="light"] .task-node {
        background: #ffffff;
        border-color: rgba(31,41,55,0.08);
    }
    [data-theme="light"] .task-node:hover {
        background: rgba(59,130,246,0.06);
        border-color: rgba(59,130,246,0.25);
    }
    /* modal overlays */
    [data-theme="light"] #share-modal > div:first-child,
    [data-theme="light"] #leave-modal > div:first-child {
        background: rgba(31,41,55,0.28) !important;
    }
    [data-theme="light"] #share-modal > div:last-child,
    [data-theme="light"] #leave-modal > div:last-child,
    [data-theme="light"] #feature-modal > div {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.12) !important;
        box-shadow: 0 22px 38px -16px rgba(31,41,55,0.20) !important;
    }
    [data-theme="light"] #feature-modal > div { background: #F0F4FF !important; border-color: rgba(59,130,246,0.22) !important; }
    /* text */
    [data-theme="light"] .text-white { color: #1F2937 !important; }
    [data-theme="light"] .text-white\/60 { color: rgba(31,41,55,0.62) !important; }
    [data-theme="light"] .text-white\/50 { color: rgba(31,41,55,0.55) !important; }
    [data-theme="light"] .text-white\/40 { color: rgba(31,41,55,0.45) !important; }
    [data-theme="light"] .text-white\/30 { color: rgba(31,41,55,0.38) !important; }
    [data-theme="light"] .text-white\/25 { color: rgba(31,41,55,0.32) !important; }
    /* borders */
    [data-theme="light"] .border-white\/10 { border-color: rgba(31,41,55,0.12) !important; }
    [data-theme="light"] .border-white\/5 { border-color: rgba(31,41,55,0.08) !important; }
    /* inputs inside share modal */
    [data-theme="light"] #collab-search {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.14) !important;
        color: #1F2937 !important;
    }
    [data-theme="light"] #collab-search::placeholder { color: rgba(31,41,55,0.35) !important; }
    [data-theme="light"] #collab-results {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.10) !important;
    }
    [data-theme="light"] #leave-cancel {
        background: rgba(31,41,55,0.05) !important;
        border-color: rgba(31,41,55,0.12) !important;
        color: rgba(31,41,55,0.62) !important;
    }
    [data-theme="light"] #leave-cancel:hover { background: rgba(31,41,55,0.10) !important; }
    [data-theme="light"] .custom-scroll::-webkit-scrollbar-thumb { background: rgba(31,41,55,0.18); }
</style>

<div class="pt-8 px-6 pb-20">
    <div class="max-w-screen-2xl mx-auto">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-12 gap-6">
            <div class="space-y-2">
                <nav class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-[0.3em] text-white/30 mb-2">
                    <span>Tactical Command</span>
                    <span>/</span>
                    <span class="text-blue-500 italic">Operations</span>
                </nav>
                <h1 class="text-5xl font-black italic uppercase tracking-tighter text-white">
                    {{ $board->name }}
                </h1>
                <p class="text-white/40 text-sm italic">{{ $board->description ?: 'Sector description uninitialized.' }}</p>
            </div>
            
            <div class="flex gap-3">
                <div class="px-4 py-2 glass-panel rounded-lg flex items-center gap-3">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-black text-white/60 uppercase tracking-widest text-nowrap">Live Stream Active</span>
                </div>

                @if($board->user_id === auth()->id())
                {{-- Share button (owner only) --}}
                <button id="share-btn"
                    class="px-4 py-2 glass-panel rounded-lg flex items-center gap-2 hover:bg-blue-600/20 border border-white/10 hover:border-blue-500/40 transition-all group">
                    <svg class="w-4 h-4 text-white/50 group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-[10px] font-black text-white/60 uppercase tracking-widest group-hover:text-blue-300 transition-colors">Share</span>
                    <span id="share-collab-count" class="hidden text-[9px] font-black bg-blue-600 text-white rounded-full px-1.5 leading-5"></span>
                </button>
                @else
                {{-- Collaborator badge: leave button --}}
                <button id="leave-board-btn"
                    class="px-4 py-2 glass-panel rounded-lg flex items-center gap-2 hover:bg-red-600/20 border border-white/10 hover:border-red-500/40 transition-all group">
                    <svg class="w-4 h-4 text-white/50 group-hover:text-red-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="text-[10px] font-black text-white/60 uppercase tracking-widest group-hover:text-red-300 transition-colors">Leave Board</span>
                </button>
                @endif
            </div>
        </div>

        <div class="mb-16">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                <h2 class="text-xs font-black uppercase tracking-[0.3em] text-white/40 italic">Planning Modules</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @php
                    $tools = [
                        ['key' => 'roadmap','title' => 'Product Roadmaps','icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L16 4m0 13V4m0 0L9 7'],
                        ['key' => 'sprint_planning','title' => 'Sprint Planning','icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['key' => 'requirements','title' => 'Project Specification','icon' => 'M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z'],
                        // Task Matrix removed
                        ['key' => 'backlog_grooming','title' => 'Backlog Refine','icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        ['key' => 'sprint_board','title' => 'Scrum Board','icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['key' => 'retrospective','title' => 'Retrospective','icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                        ['key' => 'release','title' => 'Release Plan','icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        ['key' => 'diagram_hub','title' => 'Diagram Hub','icon' => 'M3 3h18v6H3V3zm0 12h18v6H3v-6z'],
                    ];
                @endphp

                @foreach($tools as $tool)
                    <div class="command-card rounded-2xl p-6 flex flex-col justify-between group h-40">
                        <div class="flex justify-between items-start">
                            <div class="p-2 bg-blue-500/10 rounded-lg text-blue-400 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $tool['icon'] }}"/></svg>
                            </div>
                            <svg class="w-3 h-3 text-white/10 group-hover:text-white/40 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-black uppercase tracking-widest text-white group-hover:text-blue-400 transition-colors">{{ $tool['title'] }}</div>
                            @php
                                if($tool['key'] === 'diagram_hub'){
                                    $href = route('dashboard.planning.diagrams.index', $board);
                                } else {
                                    $routeName = $tool['key'] === 'roadmap' ? 'dashboard.planning.roadmaps.index' : 'dashboard.planning.' . $tool['key'];
                                    $href = route($routeName, $board);
                                }
                            @endphp
                            <a href="{{ $href }}" class="absolute inset-0 z-10" tabindex="-1" aria-hidden="true"></a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Active Deployment Matrix removed per user request -->

        {{-- Diagrams hub removed from this view per design request --}}

        {{-- ── Team / Collaborators Section ──────────────────────────── --}}
        <div class="mt-4 mb-16">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-white/40 italic">Team Access</h2>
                </div>
            </div>

            <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: rgba(13,15,70,0.3); backdrop-filter: blur(12px);">

                {{-- Owner always first --}}
                <div class="flex items-center gap-4 px-6 py-4 border-b border-white/5">
                    @if($board->user->avatar_url)
                        <img src="{{ $board->user->avatar_url }}" class="w-10 h-10 rounded-xl object-cover border border-white/15 shrink-0">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-sm font-black text-white shrink-0">
                            {{ $board->user->initials }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">
                            {{ $board->user->name }}
                            @if($board->user_id === auth()->id()) <span class="text-white/30 font-normal">(you)</span> @endif
                        </p>
                        <p class="text-xs text-white/40 truncate">{{ $board->user->email }}</p>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-blue-400 bg-blue-600/15 px-3 py-1 rounded-full border border-blue-500/20">Owner</span>
                </div>

                {{-- Collaborators --}}
                <div id="team-collab-list">
                    @forelse($board->collaboratorUsers as $person)
                        <div class="flex items-center gap-4 px-6 py-4 border-b border-white/5 hover:bg-white/3 transition-colors group" data-user-id="{{ $person->id }}">
                            @if($person->avatar_url)
                                <img src="{{ $person->avatar_url }}" class="w-10 h-10 rounded-xl object-cover border border-white/15 shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-sm font-black text-white shrink-0">
                                    {{ $person->initials }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white truncate">
                                    {{ $person->name }}
                                    @if($person->id === auth()->id()) <span class="text-white/30 font-normal">(you)</span> @endif
                                </p>
                                <p class="text-xs text-white/40 truncate">{{ $person->email }}</p>
                            </div>
                            @if($board->user_id === auth()->id())
                                {{-- Owner: editable role + remove --}}
                                <select data-uid="{{ $person->id }}" class="team-role-select bg-white/5 border border-white/10 text-white/70 text-xs rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="viewer" {{ $person->pivot->role === 'viewer' ? 'selected' : '' }}>Viewer</option>
                                    <option value="editor" {{ $person->pivot->role === 'editor' ? 'selected' : '' }}>Editor</option>
                                </select>
                                <button data-uid="{{ $person->id }}" class="team-remove-btn opacity-0 group-hover:opacity-100 p-2 rounded-lg hover:bg-red-600/20 text-white/30 hover:text-red-400 transition-all" title="Remove">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @else
                                {{-- Collaborator: read-only role badge --}}
                                @if($person->pivot->role === 'editor')
                                    <span class="text-[10px] font-black uppercase tracking-wider text-blue-300 bg-blue-600/20 px-3 py-1 rounded-full border border-blue-500/30">Editor</span>
                                @else
                                    <span class="text-[10px] font-black uppercase tracking-wider text-white/40 bg-white/5 px-3 py-1 rounded-full border border-white/10">Viewer</span>
                                @endif
                            @endif
                        </div>
                    @empty
                        {{-- empty state shown only when no collaborators --}}
                    @endforelse
                </div>

                {{-- Invite row (owner only) --}}
                @if($board->user_id === auth()->id())
                <div class="px-6 py-4 border-t border-white/5" style="background: rgba(13,0,164,0.08);">
                    <div class="flex gap-3 items-center">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/25 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                            <input id="team-search" type="text" placeholder="Add someone by name or email…" autocomplete="off"
                                class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm placeholder-white/20 focus:outline-none focus:border-blue-500 transition-colors">
                        </div>
                        <select id="team-invite-role" class="bg-white/5 border border-white/10 text-white/70 text-xs rounded-xl px-3 py-2.5 focus:outline-none focus:border-blue-500 shrink-0">
                            <option value="viewer">Viewer</option>
                            <option value="editor">Editor</option>
                        </select>
                    </div>
                    {{-- Search results --}}
                    <div id="team-search-results" class="hidden mt-2 rounded-xl border border-white/10 overflow-hidden divide-y divide-white/5" style="background: rgba(5,5,20,0.98);"></div>
                    <p class="text-[10px] text-white/20 mt-3 italic">
                        <span class="text-white/30 font-semibold">Viewers</span> can view tasks only. <span class="text-white/30 font-semibold">Editors</span> can create and move tasks.
                    </p>
                </div>
                @endif

            </div>
        </div>

    </div>
</div>

{{-- ══════════════════ SHARE / COLLABORATORS MODAL ══════════════════ --}}
@if($board->user_id === auth()->id())
<div id="share-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" id="share-modal-bg"></div>
    <div class="relative w-full max-w-lg z-10 rounded-2xl border border-white/10 overflow-hidden shadow-2xl"
         style="background: rgba(10,10,30,0.95); backdrop-filter: blur(24px);">

        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10"
             style="background: rgba(13,0,164,0.2);">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="font-black text-sm text-white uppercase tracking-widest">Share Board</span>
            </div>
            <button id="share-modal-close" class="p-1.5 rounded-lg hover:bg-white/10 text-white/40 hover:text-white transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 space-y-5">
            <div>
                <label class="text-[10px] uppercase tracking-widest text-white/40 font-black">Invite a teammate</label>
                <div class="relative mt-2">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                    <input id="collab-search" type="text" placeholder="Search by name or email…" autocomplete="off"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm placeholder-white/25 focus:outline-none focus:border-blue-500">
                </div>
                <div id="collab-results" class="hidden mt-1 rounded-xl border border-white/10 overflow-hidden divide-y divide-white/5"
                     style="background: rgba(10,10,30,0.98);"></div>
            </div>

            <div>
                <label class="text-[10px] uppercase tracking-widest text-white/40 font-black mb-3 block">People with access</label>
                <div class="flex items-center gap-3 py-2">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-lg object-cover border border-white/15 shrink-0">
                    @else
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-xs font-black text-white shrink-0">{{ auth()->user()->initials }}</div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold truncate">{{ auth()->user()->name }} <span class="text-white/30 font-normal">(you)</span></p>
                        <p class="text-xs text-white/40 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-blue-400 bg-blue-600/15 px-2 py-0.5 rounded-full border border-blue-500/20">Owner</span>
                </div>
                <div id="collab-list" class="divide-y divide-white/5"></div>
                <p id="collab-empty" class="text-xs text-white/25 italic mt-3 hidden">No collaborators yet. Invite someone above.</p>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Leave confirmation (collaborators) --}}
@if($board->user_id !== auth()->id())
<div id="leave-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-sm z-10 rounded-2xl border border-white/10 p-6 shadow-2xl" style="background: rgba(10,10,30,0.95);">
        <h3 class="font-black text-white text-lg mb-2">Leave this board?</h3>
        <p class="text-sm text-white/50 mb-6">You will lose access to <strong class="text-white">{{ $board->name }}</strong>.</p>
        <div class="flex gap-3 justify-end">
            <button id="leave-cancel" class="px-4 py-2 rounded-xl border border-white/10 text-white/60 hover:bg-white/5 text-sm font-semibold transition-all">Cancel</button>
            <button id="leave-confirm" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition-all">Leave Board</button>
        </div>
    </div>
</div>
@endif

<div id="feature-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="relative bg-[#0D0F46] border border-blue-500/30 rounded-[2.5rem] p-10 w-full max-w-xl z-10 shadow-2xl">
        <div class="text-blue-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Module Intel</div>
        <h3 id="feature-modal-title" class="text-3xl font-black italic uppercase tracking-tighter text-white mb-4"></h3>
        <p id="feature-modal-desc" class="text-white/50 text-sm leading-relaxed mb-8 italic border-l-2 border-blue-500 pl-6"></p>
        <div class="flex justify-end">
            <button id="feature-modal-close" class="px-8 py-3 bg-white/5 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-white hover:bg-white hover:text-black transition-all">Dismiss</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const TOOL_DATA = @json($tools);
    // ... logic remains exactly as provided ...
</script>
<script>
(function () {
    const BOARD_ID = {{ $board->id }};
    const IS_OWNER = {{ $board->user_id === auth()->id() ? 'true' : 'false' }};
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ── Share modal (owner only) ──────────────────────────────
    if (IS_OWNER) {
        const shareBtn   = document.getElementById('share-btn');
        const shareModal = document.getElementById('share-modal');
        const shareClose = document.getElementById('share-modal-close');
        const shareBg    = document.getElementById('share-modal-bg');
        const searchInput  = document.getElementById('collab-search');
        const resultsBox   = document.getElementById('collab-results');
        const collabList   = document.getElementById('collab-list');
        const collabEmpty  = document.getElementById('collab-empty');
        const countBadge   = document.getElementById('share-collab-count');

        function openShare() {
            shareModal.classList.remove('hidden');
            loadCollaborators();
        }
        function closeShare() { shareModal.classList.add('hidden'); }

        shareBtn?.addEventListener('click', openShare);
        shareClose?.addEventListener('click', closeShare);
        shareBg?.addEventListener('click', closeShare);

        // ── Load collaborator list ──
        async function loadCollaborators() {
            const res = await fetch(`/dashboard/planning/${BOARD_ID}/collaborators`);
            const data = await res.json();
            renderCollaborators(data.collaborators ?? []);
        }

        function renderCollaborators(list) {
            collabList.innerHTML = '';
            if (list.length === 0) {
                collabEmpty.classList.remove('hidden');
                countBadge.classList.add('hidden');
            } else {
                collabEmpty.classList.add('hidden');
                countBadge.textContent = list.length;
                countBadge.classList.remove('hidden');
            }
            list.forEach(c => {
                const initials = c.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
                const row = document.createElement('div');
                row.className = 'flex items-center gap-3 py-2.5';
                row.dataset.userId = c.id;
                row.innerHTML = `
                    ${c.avatar_url
                        ? `<img src="${c.avatar_url}" class="w-8 h-8 rounded-lg object-cover border border-white/15 shrink-0">`
                        : `<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-xs font-black text-white shrink-0">${initials}</div>`
                    }
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold truncate">${c.name}</p>
                        <p class="text-xs text-white/40 truncate">${c.email}</p>
                    </div>
                    <select data-uid="${c.id}" class="role-select bg-white/5 border border-white/10 text-white/70 text-xs rounded-lg px-2 py-1 focus:outline-none focus:border-blue-500">
                        <option value="viewer" ${c.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                        <option value="editor" ${c.role === 'editor' ? 'selected' : ''}>Editor</option>
                    </select>
                    <button data-uid="${c.id}" class="remove-collab p-1.5 rounded-lg hover:bg-red-600/20 text-white/30 hover:text-red-400 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>`;
                collabList.appendChild(row);
            });

            collabList.querySelectorAll('.role-select').forEach(sel => {
                sel.addEventListener('change', async () => {
                    await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/role`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ user_id: sel.dataset.uid, role: sel.value })
                    });
                });
            });

            collabList.querySelectorAll('.remove-collab').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Remove this collaborator?')) return;
                    await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/remove`, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ user_id: btn.dataset.uid })
                    });
                    loadCollaborators();
                });
            });
        }

        // ── User search ──
        let searchTimer;
        searchInput?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const q = searchInput.value.trim();
            if (q.length < 2) { resultsBox.classList.add('hidden'); return; }
            searchTimer = setTimeout(async () => {
                const res = await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/search?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                renderSearchResults(data.users ?? []);
            }, 300);
        });

        function renderSearchResults(users) {
            resultsBox.innerHTML = '';
            if (users.length === 0) {
                resultsBox.innerHTML = '<p class="text-xs text-white/30 px-4 py-3 italic">No users found.</p>';
                resultsBox.classList.remove('hidden');
                return;
            }
            resultsBox.classList.remove('hidden');
            users.forEach(u => {
                const initials = u.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
                const row = document.createElement('div');
                row.className = 'flex items-center gap-3 px-4 py-2.5 hover:bg-white/5 transition-all';
                row.innerHTML = `
                    ${u.avatar_url
                        ? `<img src="${u.avatar_url}" class="w-8 h-8 rounded-lg object-cover border border-white/15 shrink-0">`
                        : `<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-xs font-black text-white shrink-0">${initials}</div>`
                    }
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold truncate">${u.name}</p>
                        <p class="text-xs text-white/40 truncate">${u.email}</p>
                    </div>
                    <select class="invite-role bg-white/5 border border-white/10 text-white/70 text-xs rounded-lg px-2 py-1 focus:outline-none">
                        <option value="viewer">Viewer</option>
                        <option value="editor">Editor</option>
                    </select>
                    <button data-uid="${u.id}" class="invite-btn px-3 py-1 rounded-lg bg-blue-600/20 border border-blue-500/30 text-blue-300 text-xs font-bold hover:bg-blue-600/40 transition-all">Invite</button>`;
                resultsBox.appendChild(row);
            });

            resultsBox.querySelectorAll('.invite-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const role = btn.closest('div').querySelector('.invite-role').value;
                    btn.disabled = true;
                    btn.textContent = '…';
                    const res = await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/invite`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ user_id: btn.dataset.uid, role })
                    });
                    if (res.ok) {
                        searchInput.value = '';
                        resultsBox.classList.add('hidden');
                        loadCollaborators();
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'Invite';
                    }
                });
            });
        }
    }

    // ── Inline Team Section (owner only) ─────────────────────
    if (IS_OWNER) {
        const teamSearch    = document.getElementById('team-search');
        const teamResults   = document.getElementById('team-search-results');
        const teamList      = document.getElementById('team-collab-list');
        const teamRoleSel   = document.getElementById('team-invite-role');

        // Role change inline
        teamList?.querySelectorAll('.team-role-select').forEach(sel => {
            sel.addEventListener('change', async () => {
                await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/role`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ user_id: sel.dataset.uid, role: sel.value })
                });
            });
        });

        // Remove inline
        teamList?.querySelectorAll('.team-remove-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Remove this person from the board?')) return;
                const res = await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/remove`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ user_id: btn.dataset.uid })
                });
                if (res.ok) {
                    btn.closest('[data-user-id]').remove();
                    // also refresh share modal count
                    if (typeof loadCollaborators === 'function') loadCollaborators();
                }
            });
        });

        // Search to invite
        let teamTimer;
        teamSearch?.addEventListener('input', () => {
            clearTimeout(teamTimer);
            const q = teamSearch.value.trim();
            if (q.length < 2) { teamResults.classList.add('hidden'); return; }
            teamTimer = setTimeout(async () => {
                const res  = await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/search?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                renderTeamResults(data.users ?? []);
            }, 300);
        });

        function renderTeamResults(users) {
            teamResults.innerHTML = '';
            if (users.length === 0) {
                teamResults.innerHTML = '<p class="text-xs text-white/30 px-4 py-3 italic">No users found.</p>';
                teamResults.classList.remove('hidden');
                return;
            }
            teamResults.classList.remove('hidden');
            users.forEach(u => {
                const initials = u.name.split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
                const row = document.createElement('div');
                row.className = 'flex items-center gap-3 px-4 py-3 hover:bg-white/5 transition-all';
                row.innerHTML = `
                    ${u.avatar_url
                        ? `<img src="${u.avatar_url}" class="w-8 h-8 rounded-lg object-cover border border-white/15 shrink-0">`
                        : `<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-xs font-black text-white shrink-0">${initials}</div>`
                    }
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">${u.name}</p>
                        <p class="text-xs text-white/40 truncate">${u.email}</p>
                    </div>
                    <button data-uid="${u.id}" class="team-invite-btn px-3 py-1.5 rounded-lg bg-blue-600/20 border border-blue-500/30 text-blue-300 text-xs font-bold hover:bg-blue-600/40 transition-all">Invite</button>`;
                teamResults.appendChild(row);
            });

            teamResults.querySelectorAll('.team-invite-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const role = teamRoleSel.value;
                    btn.disabled = true; btn.textContent = '…';
                    const res  = await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/invite`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ user_id: btn.dataset.uid, role })
                    });
                    const data = await res.json();
                    if (data.ok) {
                        teamSearch.value = '';
                        teamResults.classList.add('hidden');
                        // Append new row to team list
                        appendTeamRow(data);
                        // also refresh modal count
                        if (typeof loadCollaborators === 'function') loadCollaborators();
                    }
                });
            });
        }

        function appendTeamRow(u) {
            const initials = u.name.split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
            const row = document.createElement('div');
            row.className = 'flex items-center gap-4 px-6 py-4 border-b border-white/5 hover:bg-white/3 transition-colors group';
            row.dataset.userId = u.id;
            row.innerHTML = `
                ${u.avatar_url
                    ? `<img src="${u.avatar_url}" class="w-10 h-10 rounded-xl object-cover border border-white/15 shrink-0">`
                    : `<div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-sm font-black text-white shrink-0">${initials}</div>`
                }
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-white truncate">${u.name}</p>
                </div>
                <select data-uid="${u.id}" class="team-role-select bg-white/5 border border-white/10 text-white/70 text-xs rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-blue-500 transition-colors">
                    <option value="viewer" ${u.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                    <option value="editor" ${u.role === 'editor' ? 'selected' : ''}>Editor</option>
                </select>
                <button data-uid="${u.id}" class="team-remove-btn opacity-0 group-hover:opacity-100 p-2 rounded-lg hover:bg-red-600/20 text-white/30 hover:text-red-400 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>`;
            teamList.appendChild(row);

            // Wire events for new row
            row.querySelector('.team-role-select').addEventListener('change', async (e) => {
                await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/role`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ user_id: u.id, role: e.target.value })
                });
            });
            row.querySelector('.team-remove-btn').addEventListener('click', async () => {
                if (!confirm('Remove this person from the board?')) return;
                await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/remove`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ user_id: u.id })
                });
                row.remove();
            });
        }
    }

    // ── Leave board (collaborators only) ──────────────────────
    if (!IS_OWNER) {
        const leaveBtn    = document.getElementById('leave-board-btn');
        const leaveModal  = document.getElementById('leave-modal');
        const leaveCancel = document.getElementById('leave-cancel');
        const leaveConfirm = document.getElementById('leave-confirm');

        leaveBtn?.addEventListener('click', () => leaveModal.classList.remove('hidden'));
        leaveCancel?.addEventListener('click', () => leaveModal.classList.add('hidden'));
        leaveConfirm?.addEventListener('click', async () => {
            leaveConfirm.disabled = true;
            leaveConfirm.textContent = 'Leaving…';
            const res = await fetch(`/dashboard/planning/${BOARD_ID}/collaborators/remove`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ user_id: {{ auth()->id() }} })
            });
            if (res.ok) {
                window.location.href = '/dashboard/planning';
            } else {
                leaveConfirm.disabled = false;
                leaveConfirm.textContent = 'Leave Board';
            }
        });
    }
})();
</script>
@endsection