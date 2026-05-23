@extends('layouts.dashboard')

@section('dashboard-content')
<style>
    /* Brighter, more visible card base */
    .roadmap-card {
        background: rgba(13, 15, 70, 0.3); 
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        position: relative;
        overflow: hidden;
    }

    /* Elevated Hover State */
    .roadmap-card:hover {
        background: rgba(20, 25, 110, 0.5);
        border-color: rgba(59, 130, 246, 0.4);
        transform: translateY(-10px);
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.6), 
                    0 0 30px rgba(59, 130, 246, 0.1);
    }

    /* Shimmer Effect for Progress Bars */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .progress-shimmer::after {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        animation: shimmer 2s infinite;
    }

    .progress-glow {
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
    }
</style>

<div class="pt-8 px-6 pb-12">
    <div class="max-w-6xl mx-auto">
        
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
            <div class="space-y-1">
                <nav class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-[0.3em] text-white/30 mb-3">
                    <a href="{{ route('dashboard.planning.show', $board) }}" class="hover:text-blue-400 transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ $board->name }}
                    </a>
                    <span>/</span>
                    <span class="text-blue-500 italic">Roadmaps</span>
                </nav>
                <h1 class="text-5xl font-black tracking-tighter italic uppercase text-white leading-none">
                    Product <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">Strategy</span>
                </h1>
                <p class="text-white/40 text-sm max-w-md">Orchestrate your SaaS evolution, track major milestones, and visualize the path to launch.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.planning.show', $board) }}" 
                   class="px-5 py-3 glass rounded-xl text-[10px] font-black uppercase tracking-widest text-white/60 hover:text-white hover:bg-white/5 transition-all flex items-center gap-2">
                    Back
                </a>
                <a href="{{ route('dashboard.planning.roadmaps.create', $board) }}" 
                   class="px-6 py-3 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-900/40 hover:bg-blue-500 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                    New Roadmap
                </a>
            </div>
        </div>

        @if($roadmaps->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                @foreach($roadmaps as $r)
                    @php
                        $total = $r->milestones->count();
                        $completed = $r->milestones->where('completed', true)->count();
                        $progress = $total ? intval(($completed / $total) * 100) : 0;
                        $isHighProgress = $progress > 80;
                    @endphp
                    
                    <div class="roadmap-card rounded-[2.5rem] p-8 flex flex-col group border-l-4 {{ $progress == 100 ? 'border-l-green-500' : 'border-l-blue-600' }}">
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-8">
                                <div class="p-4 bg-blue-600/10 rounded-2xl text-blue-400 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L16 4m0 13V4m0 0L9 7" />
                                    </svg>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    @if($progress == 100)
                                        <span class="text-[9px] font-black bg-green-500/10 text-green-400 px-3 py-1 rounded-full uppercase tracking-tighter">Completed</span>
                                    @else
                                        <span class="text-[9px] font-black bg-blue-500/10 text-blue-400 px-3 py-1 rounded-full uppercase tracking-tighter italic animate-pulse text-xs">Active Phase</span>
                                    @endif

                                    <div class="flex ml-4 bg-black/20 rounded-xl p-1 border border-white/5">
                                        <a href="{{ route('dashboard.planning.roadmaps.show', [$board, $r]) }}" 
                                           class="p-2 hover:bg-white/10 rounded-lg text-white/40 hover:text-white transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <form method="POST" action="{{ route('dashboard.planning.roadmaps.destroy', [$board, $r]) }}" onsubmit="return confirm('Archive this roadmap?');">
                                            @csrf @method('DELETE')
                                            <button class="p-2 hover:bg-red-500/20 rounded-lg text-white/20 hover:text-red-400 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="text-3xl font-black text-white mb-2 tracking-tighter group-hover:translate-x-1 transition-transform italic uppercase">{{ $r->title }}</h3>
                            <p class="text-white/40 text-sm leading-relaxed mb-12 line-clamp-2">
                                {{ $r->description ?: 'Operational mission parameters are currently being defined by the engineering team.' }}
                            </p>
                        </div>

                        <div class="mt-auto space-y-4 relative z-10">
                            <div class="flex justify-between items-end">
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black uppercase tracking-[0.2em] text-white/20 block">System Integrity</span>
                                    <div class="text-xl font-black text-white italic tracking-tighter">
                                        {{ $progress }}% <span class="text-[10px] text-blue-400 not-italic ml-2 uppercase">Complete</span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-black text-white/20 uppercase tracking-widest italic">
                                    {{ $completed }} / {{ $total }} Units
                                </span>
                            </div>
                            
                            <div class="relative w-full h-3 bg-white/5 rounded-full overflow-hidden border border-white/5">
                                <div style="width: {{ $progress }}%" 
                                     class="absolute top-0 left-0 h-full rounded-full transition-all duration-1000 ease-out progress-glow progress-shimmer {{ $isHighProgress ? 'bg-gradient-to-r from-blue-500 to-cyan-300' : 'bg-blue-600' }}">
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-2">
                                <div class="flex -space-x-2">
                                    @for($i = 0; $i < min($total, 5); $i++)
                                        <div class="w-8 h-8 rounded-full border-2 border-[#05062d] bg-white/5 flex items-center justify-center text-[8px] font-black text-white/40 group-hover:border-blue-500/30 transition-colors">
                                            {{ $i + 1 }}
                                        </div>
                                    @endfor
                                    @if($total > 5)
                                        <div class="w-8 h-8 rounded-full border-2 border-[#05062d] bg-blue-600 flex items-center justify-center text-[8px] font-black text-white">+{{ $total - 5 }}</div>
                                    @endif
                                </div>
                                <a href="{{ route('dashboard.planning.roadmaps.show', [$board, $r]) }}" class="text-[9px] font-black text-blue-400 uppercase tracking-widest hover:text-white transition-colors">
                                    Execute Details →
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="glass p-20 text-center rounded-[3rem] border-2 border-dashed border-white/5">
                <div class="w-24 h-24 bg-blue-600/10 rounded-[2rem] flex items-center justify-center mx-auto mb-8 text-blue-400">
                    <svg class="w-12 h-12 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L16 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                <h3 class="text-3xl font-black text-white mb-3 italic uppercase tracking-tighter">Strategic Void Detected</h3>
                <p class="text-white/30 max-w-sm mx-auto mb-10 text-sm leading-relaxed">Your project roadmap is currently uninitialized. Create a strategy to begin tracking milestones.</p>
                <a href="{{ route('dashboard.planning.roadmaps.create', $board) }}" class="px-10 py-5 bg-white text-black font-black rounded-2xl hover:bg-blue-600 hover:text-white transition-all transform hover:scale-105 inline-block uppercase tracking-widest text-[10px]">
                    Initialize Strategy
                </a>
            </div>
        @endif
    </div>
</div>
@endsection