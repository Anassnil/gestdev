@extends('layouts.dashboard')

@section('dashboard-content')
<div class="space-y-8 p-2">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight flex items-center gap-3 flex-wrap">
                <div class="p-2 bg-blue-600 rounded-lg shadow-lg shadow-blue-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </div>
                Overview
            </h1>
            <p class="text-white/50 mt-1 md:ml-12">Welcome back! Here's what's happening today.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <a href="{{ route('dashboard.planning.index') }}" class="flex items-center justify-center gap-2 px-3 sm:px-5 py-2 sm:py-2.5 bg-white text-[#02010A] rounded-xl font-bold hover:bg-blue-50 transition-all active:scale-95 shadow-lg shadow-white/5 text-sm sm:text-base whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="hidden sm:inline">Open Planning</span>
                <span class="sm:hidden">Planning</span>
            </a>
            <a href="#" class="flex items-center justify-center gap-2 px-3 sm:px-5 py-2 sm:py-2.5 glass rounded-xl font-semibold border border-white/10 hover:bg-white/10 transition-all active:scale-95 text-sm sm:text-base">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Create Project</span>
                <span class="sm:hidden">Create</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass p-5 rounded-2xl border border-white/5 hover:border-blue-500/40 transition-all group cursor-default">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-500/10 rounded-xl text-blue-400 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                </div>
                <span class="text-xs font-bold text-blue-400 bg-blue-400/10 px-2 py-1 rounded-lg">Live</span>
            </div>
            <div class="text-sm font-medium text-white/50 uppercase tracking-wider">Active Boards</div>
            <div class="text-3xl font-black mt-1">{{ \App\Models\Board::where('user_id', Auth::id())->count() }}</div>
        </div>

        <div class="glass p-5 rounded-2xl border border-white/5 hover:border-yellow-500/40 transition-all group cursor-default">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-yellow-500/10 rounded-xl text-yellow-400 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex -space-x-2">
                    <div class="w-6 h-6 rounded-full bg-gray-600 border border-[#02010A]"></div>
                    <div class="w-6 h-6 rounded-full bg-gray-500 border border-[#02010A]"></div>
                </div>
            </div>
            <div class="text-sm font-medium text-white/50 uppercase tracking-wider">Open Tasks</div>
            <div class="flex items-baseline gap-2 mt-1">
                <div class="text-3xl font-black">{{ \App\Models\Task::where('status','!=','done')->whereHas('board', fn($q) => $q->where('user_id', Auth::id())->orWhereExists(fn($sub) => $sub->from('board_collaborators')->whereColumn('board_collaborators.board_id','boards.id')->where('board_collaborators.user_id', Auth::id())))->count() }}</div>
                <span class="text-xs text-yellow-500 font-bold">Needs review</span>
            </div>
        </div>

        <div class="glass p-5 rounded-2xl border border-white/5 hover:border-green-500/40 transition-all group cursor-default">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-green-500/10 rounded-xl text-green-400 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <svg class="w-12 h-6 text-green-500/50" viewBox="0 0 100 40">
                    <path d="M0 35 Q 25 35, 40 15 T 100 5" fill="none" stroke="currentColor" stroke-width="3" />
                </svg>
            </div>
            <div class="text-sm font-medium text-white/50 uppercase tracking-wider">Completed Today</div>
            <div class="flex items-baseline gap-2 mt-1">
                <div class="text-3xl font-black">{{ \App\Models\Task::where('status','done')->whereDate('updated_at', now())->whereHas('board', fn($q) => $q->where('user_id', Auth::id()))->count() }}</div>
                <span class="text-xs text-green-500 font-bold">+12% inc.</span>
            </div>
        </div>
    </div>

    <div class="glass p-6 rounded-2xl border border-white/5 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </div>
        
        <h2 class="font-bold text-lg mb-5 flex items-center gap-2">
            <span class="w-1.5 h-5 bg-blue-600 rounded-full"></span>
            Quick Shortcuts
        </h2>
        @php $firstBoard = \App\Models\Board::where('user_id', Auth::id())->first(); @endphp
        <div class="flex gap-4 flex-wrap">
            <a href="{{ route('dashboard.planning.index') }}" class="group flex items-center gap-3 px-5 py-3 bg-[#0D00A4] rounded-xl hover:bg-blue-700 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="font-medium">Go to Planning</span>
            </a>

            @if($firstBoard)
            <a href="{{ route('dashboard.planning.project_tracking', $firstBoard) }}" class="flex items-center gap-3 px-5 py-3 glass rounded-xl border border-white/5 hover:bg-white/10 hover:border-white/20 transition-all group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/50 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-white/80 group-hover:text-white font-medium">New Task</span>
            </a>

            <a href="{{ route('dashboard.planning.sprint_board', $firstBoard) }}" class="flex items-center gap-3 px-5 py-3 glass rounded-xl border border-white/5 hover:bg-white/10 hover:border-white/20 transition-all group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/50 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="text-white/80 group-hover:text-white font-medium">Sprint Board</span>
            </a>

            <a href="{{ route('dashboard.planning.diagrams.index', $firstBoard) }}" class="flex items-center gap-3 px-5 py-3 glass rounded-xl border border-white/5 hover:bg-white/10 hover:border-white/20 transition-all group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/50 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14" />
                </svg>
                <span class="text-white/80 group-hover:text-white font-medium">Diagrams</span>
            </a>

            <!-- AI Models quick shortcut removed from dashboard shortcuts -->
            @else
            <a href="{{ route('dashboard.planning.index') }}" class="flex items-center gap-3 px-5 py-3 glass rounded-xl border border-white/5 hover:bg-white/10 hover:border-white/20 transition-all group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/50 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-white/80 group-hover:text-white font-medium">Create a Board First</span>
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass p-6 rounded-2xl border border-white/5">
            <h3 class="font-bold text-lg mb-6 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Recent Activity
                </div>
                <a href="{{ route('dashboard.activity') }}" class="text-xs text-white/30 hover:text-white transition-colors uppercase font-bold tracking-widest">View All</a>
            </h3>
            
            @if($activities->isEmpty())
            <div class="flex flex-col items-center justify-center py-10 text-center space-y-3">
                <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white/10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-white/60 font-medium">Nothing to report yet</p>
                    <p class="text-xs text-white/30">New updates will appear here automatically.</p>
                </div>
            </div>
            @else
            <div class="space-y-1 max-h-80 overflow-y-auto pr-1">
                @foreach($activities as $activity)
                <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-white/5 transition-all group">
                    {{-- Icon --}}
                    <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                        @if($activity['color'] === 'green') bg-green-500/10 text-green-400
                        @elseif($activity['color'] === 'yellow') bg-yellow-500/10 text-yellow-400
                        @elseif($activity['color'] === 'purple') bg-purple-500/10 text-purple-400
                        @elseif($activity['color'] === 'teal') bg-teal-500/10 text-teal-400
                        @else bg-blue-500/10 text-blue-400 @endif">
                        @if($activity['icon'] === 'task')
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($activity['icon'] === 'requirement')
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        @elseif($activity['icon'] === 'board')
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
                        @elseif($activity['icon'] === 'diagram')
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                        @endif
                    </div>
                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold truncate">{{ $activity['title'] }}</span>
                            @if($activity['board'])
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-white/5 text-white/40 font-medium flex-shrink-0">{{ $activity['board'] }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-white/40">{{ $activity['desc'] }}</span>
                            <span class="text-[10px] text-white/20">&middot;</span>
                            <span class="text-[10px] text-white/20">{{ $activity['time']->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="glass p-6 rounded-2xl border border-white/5">
            <h3 class="font-bold text-lg mb-6 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Team Status
            </h3>
            
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 font-bold">?</div>
                        <div>
                            <div class="text-sm font-bold text-white/80">System Bot</div>
                            <div class="text-xs text-white/40">Ready to assist</div>
                        </div>
                    </div>
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(34,197,94,0.6)]"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
