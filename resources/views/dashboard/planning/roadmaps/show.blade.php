@extends('layouts.dashboard')

@section('dashboard-content')
<style>
    /* Premium Glass Morphism */
    .glass-panel {
        background: rgba(13, 15, 70, 0.4);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .glass-panel:hover {
        background: rgba(20, 25, 100, 0.5);
        border-color: rgba(59, 130, 246, 0.4);
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 
                    0 0 20px rgba(13, 0, 164, 0.2);
    }

    /* Milestone Navigation & Timeline */
    .milestone-item {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .milestone-item:hover {
        background: rgba(59, 130, 246, 0.05);
        border-color: rgba(59, 130, 246, 0.3);
        transform: translateX(8px);
    }

    .timeline-connector::before {
        content: '';
        position: absolute;
        left: 31px; /* Aligned with larger icon container */
        top: 0;
        bottom: 0;
        width: 1px;
        background: linear-gradient(to bottom, transparent, rgba(59, 130, 246, 0.2) 15%, rgba(59, 130, 246, 0.2) 85%, transparent);
        z-index: 0;
    }

    .completed-strike {
        text-decoration: line-through;
        opacity: 0.3;
        filter: grayscale(1);
    }

    /* Inputs & Scrollbars */
    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.3); border-radius: 10px; }
    
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 0.5;
        cursor: pointer;
    }

    .btn-action {
        transition: all 0.2s ease;
    }
    .btn-action:active {
        transform: scale(0.92);
    }

    /* ─── LIGHT MODE ─── */
    :root {
        --lm-bg: #F8FAFC;
        --lm-surface: #F1F5F9;
        --lm-card: #FFFFFF;
        --lm-border: rgba(31,41,55,0.10);
        --lm-text: #1F2937;
        --lm-muted: rgba(31,41,55,0.45);
        --lm-primary: #4F46E5;
    }

    [data-theme="light"] .glass-panel {
        background: #ffffff !important;
        border-color: var(--lm-border) !important;
        box-shadow: 0 4px 20px -6px rgba(31,41,55,0.10) !important;
    }
    [data-theme="light"] .glass-panel:hover {
        background: #f5f7ff !important;
        border-color: rgba(79,70,229,0.30) !important;
        box-shadow: 0 12px 32px -8px rgba(79,70,229,0.15), 0 4px 16px rgba(31,41,55,0.08) !important;
    }
    [data-theme="light"] .milestone-item {
        background: var(--lm-surface) !important;
        border-color: var(--lm-border) !important;
    }
    [data-theme="light"] .milestone-item:hover {
        background: #EEF2FF !important;
        border-color: rgba(79,70,229,0.25) !important;
    }

    /* Text overrides */
    [data-theme="light"] .text-white { color: var(--lm-text) !important; }
    [data-theme="light"] .text-white\/40 { color: var(--lm-muted) !important; }
    [data-theme="light"] .text-white\/30 { color: rgba(31,41,55,0.38) !important; }
    [data-theme="light"] .text-white\/20 { color: rgba(31,41,55,0.28) !important; }
    [data-theme="light"] .text-white\/10 { color: rgba(31,41,55,0.15) !important; }
    [data-theme="light"] .text-white\/60 { color: rgba(31,41,55,0.60) !important; }
    [data-theme="light"] .text-white\/80 { color: rgba(31,41,55,0.80) !important; }
    [data-theme="light"] .placeholder-white\/10::placeholder { color: rgba(31,41,55,0.28) !important; }

    /* Breadcrumb & nav */
    [data-theme="light"] nav.flex a,
    [data-theme="light"] nav.flex span { color: rgba(31,41,55,0.40) !important; }
    [data-theme="light"] nav.flex a:hover { color: var(--lm-primary) !important; }
    [data-theme="light"] nav.flex .text-blue-500 { color: var(--lm-primary) !important; }

    /* Heading */
    [data-theme="light"] h1.text-white { color: var(--lm-text) !important; }
    [data-theme="light"] p.text-white\/40 { color: var(--lm-muted) !important; }
    [data-theme="light"] .border-white\/10 { border-color: var(--lm-border) !important; }
    [data-theme="light"] .border-l-2.border-white\/10 { border-color: rgba(79,70,229,0.25) !important; }

    /* Stat cards */
    [data-theme="light"] .bg-white\/5 { background: var(--lm-surface) !important; }
    [data-theme="light"] .border-white\/5 { border-color: var(--lm-border) !important; }

    /* Form inputs */
    [data-theme="light"] input[type="text"],
    [data-theme="light"] input[type="date"],
    [data-theme="light"] textarea {
        background: var(--lm-surface) !important;
        border-color: var(--lm-border) !important;
        color: var(--lm-text) !important;
    }
    [data-theme="light"] input[type="text"]:focus,
    [data-theme="light"] input[type="date"]:focus,
    [data-theme="light"] textarea:focus {
        border-color: var(--lm-primary) !important;
        background: #EEF2FF !important;
    }
    [data-theme="light"] input[type="date"]::-webkit-calendar-picker-indicator { filter: none; opacity: 0.5; }

    /* Submit button */
    [data-theme="light"] button.bg-white.text-black {
        background: var(--lm-text) !important;
        color: #ffffff !important;
    }
    [data-theme="light"] button.bg-white.text-black:hover {
        background: var(--lm-primary) !important;
    }

    /* Terminal View button */
    [data-theme="light"] a.glass-panel.text-white\/60 {
        background: var(--lm-surface) !important;
        border-color: var(--lm-border) !important;
        color: var(--lm-muted) !important;
    }

    /* Milestone toggle dot (incomplete) */
    [data-theme="light"] .bg-\[#0D0F46\] { background: #E0E7FF !important; }
    [data-theme="light"] .border-white\/20 { border-color: rgba(79,70,229,0.25) !important; }

    /* Milestone action buttons */
    [data-theme="light"] .milestone-item button.bg-white\/5 {
        background: var(--lm-surface) !important;
        color: var(--lm-muted) !important;
    }
    [data-theme="light"] .milestone-item button.bg-white\/5:hover { background: var(--lm-primary) !important; color: white !important; }

    /* Timeline connector */
    [data-theme="light"] .timeline-connector::before {
        background: linear-gradient(to bottom, transparent, rgba(79,70,229,0.18) 15%, rgba(79,70,229,0.18) 85%, transparent) !important;
    }

    /* Scrollbar */
    [data-theme="light"] .custom-scroll::-webkit-scrollbar-thumb { background: rgba(79,70,229,0.20); }

    /* Progress bar label */
    [data-theme="light"] .text-blue-400\/50 { color: rgba(79,70,229,0.50) !important; }
    [data-theme="light"] .text-blue-400\/60 { color: rgba(79,70,229,0.60) !important; }
    [data-theme="light"] .bg-blue-400\/10 { background: rgba(79,70,229,0.08) !important; }

    /* Completed-strike opacity already visible; keep as is */
    [data-theme="light"] .completed-strike { opacity: 0.40; }

    /* Empty state */
    [data-theme="light"] .opacity-10 { opacity: 0.20 !important; }

    /* Append button in light mode */
    [data-theme="light"] #open-milestone-modal { background: #ffffff !important; border-color: var(--lm-border) !important; }
    [data-theme="light"] #open-milestone-modal:hover { border-color: rgba(79,70,229,0.30) !important; background: #f5f7ff !important; }
    /* Modal */
    [data-theme="light"] #milestone-modal > div { background: #ffffff !important; border-color: var(--lm-border) !important; }
    [data-theme="light"] #desc-info-modal > div { background: #ffffff !important; border-color: var(--lm-border) !important; }
    [data-theme="light"] #desc-info-btn { background: rgba(79,70,229,0.10) !important; color: var(--lm-primary) !important; }
    [data-theme="light"] #desc-info-btn:hover { background: rgba(79,70,229,0.20) !important; }
</style>

<div class="pt-8 px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-6">
            <div class="flex-1">
                <nav class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-[0.3em] text-white/30 mb-3">
                    <a href="{{ route('dashboard.planning.roadmaps.index', $board) }}" class="hover:text-blue-400 transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Roadmaps
                    </a>
                    <span>/</span>
                    <span class="text-blue-500 italic flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        Execution Console
                    </span>
                </nav>
                <h1 class="text-5xl font-black italic uppercase tracking-tighter text-white leading-none">
                    {{ $roadmap->title }}
                </h1>


                {{-- Description popup --}}
                <div id="desc-info-modal" class="hidden fixed inset-0 z-[300] flex items-center justify-center p-4" style="backdrop-filter:blur(10px); background:rgba(0,0,0,0.55);">
                    <div class="relative w-full max-w-lg glass-panel rounded-[2rem] p-8 border border-white/10 shadow-2xl">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-400 font-black text-sm">!</div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-white/40">Mission Parameters</span>
                            </div>
                            <button id="desc-info-close" class="p-2 rounded-xl text-white/20 hover:text-white hover:bg-white/5 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-white/70 text-sm leading-relaxed italic border-l-2 border-blue-500/40 pl-4">
                            "{{ $roadmap->description ?: 'Mission parameters uninitialized.' }}"
                        </p>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        const m = document.getElementById('desc-info-modal');
                        document.getElementById('desc-info-btn').onclick = () => m.classList.remove('hidden');
                        document.getElementById('desc-info-close').onclick = () => m.classList.add('hidden');
                        m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); });
                    });
                </script>
            </div>
            
            <div class="flex items-center gap-3">
                <button id="desc-info-btn" title="View description" class="px-4 py-3 glass-panel rounded-xl font-black text-white/60 hover:text-blue-400 hover:border-blue-500/40 flex items-center justify-center transition-all btn-action border border-white/10 text-base leading-none">!</button>
                <a href="{{ route('dashboard.planning.roadmaps.index', $board) }}" 
                   class="px-6 py-3 glass-panel rounded-xl text-[10px] font-black uppercase tracking-widest text-white/60 hover:text-white flex items-center gap-3 transition-all btn-action">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                    Terminal View
                </a>
            </div>
        </div>

        @php
            $milestones = $roadmap->milestones->sortBy('due_at');
            // Sequence view should show newest objectives first
            $seqMilestones = $roadmap->milestones->sortByDesc('created_at');
            $total = $milestones->count();
            $completedCount = $milestones->where('completed', true)->count();
            $progress = $total ? intval(($completedCount / $total) * 100) : 0;
            $next = $milestones->where('completed', false)->first();
            // build chart points: cumulative completion percentage over milestone sequence
            $cumulative = 0;
            $chartPoints = [];
            foreach($milestones as $m){
                if($m->completed) $cumulative++;
                $pct = $total ? intval(($cumulative / $total) * 100) : 0;
                $chartPoints[] = [
                    'label' => $m->due_at ? $m->due_at->format('M d') : $m->title,
                    'val' => $pct,
                    'completed' => (bool) $m->completed,
                ];
            }
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-10">
            <button id="open-milestone-modal" class="group glass-panel rounded-3xl p-6 border border-white/5 flex flex-col justify-between hover:border-blue-500/40 transition-all btn-action text-left">
                <div class="w-10 h-10 rounded-2xl bg-blue-600/20 flex items-center justify-center group-hover:bg-blue-600 transition-all mb-3">
                    <svg class="w-5 h-5 text-blue-400 group-hover:text-white transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div>
                    <div class="text-[10px] font-black uppercase tracking-[0.2em] text-white/30">Append Step</div>
                    <div class="text-sm font-black text-white mt-1 italic tracking-tight group-hover:text-blue-400 transition-colors">New Milestone</div>
                </div>
            </button>

            <div class="glass-panel rounded-3xl p-6 border-l-4 border-blue-500 relative overflow-hidden">
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-white/30 flex items-center gap-2">
                    <svg class="w-3 h-3 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    System Integrity
                </div>
                <div class="text-4xl font-black text-white mt-2 italic tracking-tighter">{{ $progress }}%</div>
                <div class="w-full bg-white/5 h-1.5 rounded-full mt-4 overflow-hidden border border-white/5">
                    <div class="h-full bg-gradient-to-r from-blue-600 to-cyan-400 shadow-[0_0_15px_rgba(59,130,246,0.6)] transition-all duration-1000" style="width: {{ $progress }}%"></div>
                </div>
            </div>
            
            <div class="glass-panel rounded-3xl p-6">
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-white/30 flex items-center gap-2">
                    <svg class="w-3 h-3 text-white/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Total Objectives
                </div>
                <div class="text-4xl font-black text-white mt-2 italic tracking-tighter">{{ $total }}</div>
                <div class="text-[9px] text-blue-400/50 font-black uppercase mt-2 italic tracking-widest">{{ $completedCount }} SECURED</div>
            </div>

            <div class="glass-panel rounded-3xl p-6 md:col-span-2 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <svg class="w-32 h-32 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-white/30 flex items-center gap-2">
                    <svg class="w-3 h-3 text-blue-500 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Immediate Directive
                </div>
                @if($next)
                    <div class="text-2xl font-black text-white mt-2 truncate tracking-tight group-hover:text-blue-400 transition-colors uppercase italic">{{ $next->title }}</div>
                    <div class="inline-flex items-center gap-2 text-[10px] text-blue-400 font-bold uppercase mt-3 px-3 py-1 bg-blue-400/10 rounded-lg">
                        <span class="w-1.5 h-1.5 bg-blue-400 rounded-full animate-pulse"></span>
                        T-Minus: {{ $next->due_at ? $next->due_at->diffForHumans() : 'UNDETERMINED' }}
                    </div>
                @else
                    <div class="text-2xl font-black text-green-500 mt-2 italic uppercase tracking-tighter">Mission Accomplished</div>
                    <div class="text-[9px] text-white/20 font-bold mt-2 italic uppercase">All sectors operational.</div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-7 space-y-8">
                <div class="glass-panel rounded-[2.5rem] p-8">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-white/40 italic flex items-center gap-3">
                            <svg class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M3 3v18h18M18 9l-6 6-4-4-4 4"/></svg>
                            Deployment Velocity
                        </h3>
                        <div class="flex gap-2">
                            <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                            <span class="w-2 h-2 bg-white/10 rounded-full"></span>
                        </div>
                    </div>
                    <div class="h-[320px]">
                        <canvas id="roadmap-timeline"></canvas>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="glass-panel rounded-[2.5rem] p-8 flex flex-col border border-white/5" style="height:500px;">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-white/40 mb-6 italic flex items-center gap-3 shrink-0">
                        <svg class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                        Sequence of Events
                    </h3>

                    <div class="flex-1 overflow-y-auto custom-scroll pr-4 space-y-6 timeline-connector relative" style="min-height:0;">
                        @forelse($seqMilestones as $m)
                            <div class="milestone-item group p-6 rounded-[2rem] relative z-10">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4">
                                            <div class="relative z-10">
                                                <div class="w-5 h-5 rounded-full border-2 {{ $m->completed ? 'bg-blue-500 border-blue-400 shadow-[0_0_12px_#3b82f6]' : 'bg-[#0D0F46] border-white/20' }} transition-all duration-500 flex items-center justify-center">
                                                    @if($m->completed)
                                                        <svg class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4"><path d="M20 6L9 17l-5-5"/></svg>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="text-base font-black italic tracking-tight text-white transition-all {{ $m->completed ? 'completed-strike' : '' }}">
                                                {{ $m->title }}
                                            </span>
                                        </div>
                                        
                                        <div class="mt-4 ml-9 space-y-3">
                                            @if($m->due_at)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-3 h-3 text-blue-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                                    <span class="text-[9px] font-black text-blue-400/60 uppercase tracking-widest">
                                                        {{ $m->due_at->format('M d, Y') }}
                                                    </span>
                                                </div>
                                            @endif
                                            <p class="text-[12px] text-white/30 leading-relaxed group-hover:text-white/60 transition-colors line-clamp-3 italic">
                                                {{ $m->notes ?: 'No further tactical details logged for this unit.' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                        <form method="POST" action="{{ route('dashboard.planning.roadmaps.milestones.update', [$board, $roadmap, $m]) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="completed" value="{{ $m->completed ? 0 : 1 }}">
                                            <button title="Toggle Status" class="p-3 bg-white/5 hover:bg-blue-600 rounded-xl transition-all hover:shadow-[0_0_15px_rgba(59,130,246,0.4)] btn-action">
                                                <svg class="w-4 h-4 {{ $m->completed ? 'text-white' : 'text-white/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('dashboard.planning.roadmaps.milestones.destroy', [$board, $roadmap, $m]) }}" onsubmit="return confirm('Erase this objective?');">
                                            @csrf @method('DELETE')
                                            <button title="Erase Objective" class="p-3 bg-white/5 hover:bg-red-600 text-white/20 hover:text-white rounded-xl transition-all btn-action">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-24 text-center opacity-10">
                                <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>
                                <p class="text-xs font-black uppercase tracking-[0.4em]">Grid Empty</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Append Strategic Step Modal --}}
<div id="milestone-modal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4" style="backdrop-filter:blur(12px); background:rgba(0,0,0,0.65);">
    <div class="relative w-full max-w-xl glass-panel rounded-[2.5rem] p-8 shadow-2xl border border-white/10">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-2xl bg-blue-600/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white uppercase italic tracking-tight">Append Strategic Step</h3>
                    <p class="text-[10px] text-white/30 font-mono uppercase tracking-widest mt-0.5">Initialize a new milestone</p>
                </div>
            </div>
            <button id="close-milestone-modal" class="p-2 rounded-xl text-white/20 hover:text-white hover:bg-white/5 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('dashboard.planning.roadmaps.milestones.store', [$board, $roadmap]) }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase text-white/20 ml-2 tracking-widest">Objective Title</label>
                    <div class="relative">
                        <input name="title" required class="w-full pl-12 pr-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-white focus:border-blue-500 focus:bg-blue-500/5 focus:outline-none placeholder-white/10 text-sm transition-all" placeholder="e.g., Kernel Deployment">
                        <svg class="w-4 h-4 absolute left-5 top-1/2 -translate-y-1/2 text-white/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase text-white/20 ml-2 tracking-widest">Target Date</label>
                    <div class="relative">
                        <input name="due_at" type="date" class="w-full pl-12 pr-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-white focus:border-blue-500 focus:outline-none text-sm transition-all">
                        <svg class="w-4 h-4 absolute left-5 top-1/2 -translate-y-1/2 text-white/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                </div>
            </div>
            <div class="space-y-2">
                <label class="text-[9px] font-black uppercase text-white/20 ml-2 tracking-widest">Mission Notes</label>
                <textarea name="notes" rows="3" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-white focus:border-blue-500 focus:outline-none placeholder-white/10 text-sm transition-all" placeholder="Define tactical constraints..."></textarea>
            </div>
            <button class="group w-full py-5 bg-white text-black font-black uppercase tracking-[0.2em] text-[10px] rounded-2xl hover:bg-blue-600 hover:text-white transition-all shadow-2xl flex items-center justify-center gap-3 btn-action">
                Initialize Milestone
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </form>
    </div>
</div>

<script>
    (function(){
        const modal = document.getElementById('milestone-modal');
        document.getElementById('open-milestone-modal').onclick = () => modal.classList.remove('hidden');
        document.getElementById('close-milestone-modal').onclick = () => modal.classList.add('hidden');
        modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });
    })();
</script>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
    (function(){
        const points = @json($chartPoints);
        const ctx = document.getElementById('roadmap-timeline').getContext('2d');
        const chartGradient = ctx.createLinearGradient(0, 0, 0, 320);
        chartGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
        chartGradient.addColorStop(1, 'rgba(13, 15, 70, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: points.map(p => p.label),
                datasets: [{
                    data: points.map(p => p.val),
                    borderColor: '#3b82f6',
                    borderWidth: 4,
                    pointBackgroundColor: points.map(p => p.completed ? '#06b6d4' : '#0D0F46'),
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 10,
                    tension: 0.2,
                    fill: true,
                    backgroundColor: chartGradient,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0D0F46',
                        titleFont: { family: 'Inter', weight: '900', size: 12 },
                        bodyFont: { family: 'Inter', size: 11 },
                        padding: 12,
                        borderColor: 'rgba(59, 130, 246, 0.3)',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: { 
                        grid: { color: 'rgba(255,255,255,0.03)' }, 
                        ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 10, weight: '900', family: 'Inter' } } 
                    },
                    y: { 
                        display: true, 
                        min: 0, 
                        max: 100,
                        ticks: { color: 'rgba(255,255,255,0.3)' }
                    }
                }
            }
        });
    })();
</script>
@endsection