@extends('layouts.dashboard')

@section('dashboard-content')
<div class="space-y-8 p-2">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 flex-wrap">
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

        <div class="flex flex-row gap-3 flex-shrink-0">
            {{-- Open Planning --}}
            <a href="{{ route('dashboard.planning.index') }}"
                class="group inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm
                       bg-gradient-to-r from-indigo-600 to-violet-600
                       hover:from-indigo-500 hover:to-violet-500
                       text-white shadow-lg shadow-indigo-500/25
                       hover:shadow-indigo-500/50 hover:shadow-xl
                       hover:scale-[1.04] active:scale-95
                       transition-all duration-300 whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 group-hover:rotate-[-8deg] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Open Planning
            </a>

            {{-- Create Project --}}
            <a href="#"
                class="group inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm
                       bg-gradient-to-r from-emerald-600/20 to-teal-600/20
                       border border-emerald-500/30
                       text-emerald-300
                       hover:from-emerald-600/35 hover:to-teal-600/35
                       hover:border-emerald-400/60
                       hover:shadow-[0_0_20px_rgba(16,185,129,0.28)]
                       hover:scale-[1.04] active:scale-95
                       transition-all duration-300 whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 group-hover:rotate-90 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Create Project
            </a>
        </div>
    </div>

    {{-- Stats grid: 3 equal cols, 2 rows. Row 2: Active Times spans cols 1+2, Quick Shortcuts in col 3 --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- ROW 1 --}}

        {{-- Active Boards --}}
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

        {{-- Open Tasks --}}
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

        {{-- Completed Today --}}
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

        {{-- ROW 2 --}}

        {{-- Active Times — spans cols 1+2 --}}
        <div class="glass p-4 rounded-2xl border border-white/5 md:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-xs flex items-center gap-1.5 text-white/50 uppercase tracking-widest">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Active Times
                </h3>
                <div class="flex gap-1" id="activeTimesToggle">
                    <button data-view="hours" class="at-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Hours</button>
                    <button data-view="days"  class="at-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all bg-white/5 text-white/30 border border-white/5 hover:bg-white/10 hover:text-white/60">Days</button>
                    <button data-view="months" class="at-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all bg-white/5 text-white/30 border border-white/5 hover:bg-white/10 hover:text-white/60">Months</button>
                </div>
            </div>
            <div style="position:relative;height:200px;">
                <canvas id="activeTimesChart"></canvas>
            </div>
        </div>

        {{-- Quick Shortcuts — col 3 only --}}
        <div class="glass p-4 rounded-2xl border border-white/5">
            <h2 class="font-semibold text-xs mb-3 flex items-center gap-2 text-white/50 uppercase tracking-widest">
                <span class="w-1.5 h-3.5 bg-blue-600 rounded-full"></span>
                Quick Shortcuts
            </h2>
            @php $firstBoard = \App\Models\Board::where('user_id', Auth::id())->first(); @endphp
            <div class="flex flex-col gap-1.5">
                <a href="{{ route('dashboard.planning.index') }}" class="group flex items-center gap-2.5 px-3 py-2 bg-indigo-600/80 hover:bg-indigo-600 rounded-lg transition-all text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    <span class="font-medium">Go to Planning</span>
                </a>
                @if($firstBoard)
                <a href="{{ route('dashboard.planning.project_tracking', $firstBoard) }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-lg bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/15 transition-all text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 text-white/40 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-white/70 group-hover:text-white font-medium transition-colors">New Task</span>
                </a>
                <a href="{{ route('dashboard.planning.sprint_board', $firstBoard) }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-lg bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/15 transition-all text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 text-white/40 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <span class="text-white/70 group-hover:text-white font-medium transition-colors">Sprint Board</span>
                </a>
                <a href="{{ route('dashboard.planning.diagrams.index', $firstBoard) }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-lg bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/15 transition-all text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 text-white/40 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14" /></svg>
                    <span class="text-white/70 group-hover:text-white font-medium transition-colors">Diagrams</span>
                </a>
                @else
                <a href="{{ route('dashboard.planning.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-lg bg-white/5 border border-white/5 hover:bg-white/10 transition-all text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 text-white/40 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-white/70 group-hover:text-white font-medium transition-colors">Create a Board First</span>
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
        <div class="glass p-6 rounded-2xl border border-white/5 flex flex-col">
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
            <div class="space-y-1">
                @foreach($activities->take(4) as $activity)
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

        <div class="glass p-6 rounded-2xl border border-white/5 flex flex-col">
            <h3 class="font-bold text-lg mb-6 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Team Status
            </h3>
            
            <div class="space-y-4">
                @if(!empty($teams) && $teams->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($teams as $team)
                                @php
                                $members = $team->collaboratorUsers ?? $team->members ?? $team->users ?? collect();
                                $membersCount = is_countable($members) ? count($members) : 0;
                                $initials = strtoupper(substr($team->name ?? 'TM', 0, 2));
                            @endphp
                            <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 font-bold">{{ $initials }}</div>
                                    <div>
                                        <div class="text-sm font-bold text-white/80">{{ $team->name }}</div>
                                        <div class="text-xs text-white/40">{{ $membersCount }} member{{ $membersCount === 1 ? '' : 's' }}{{ $team->description ? ' • ' . \Illuminate\Support\Str::limit($team->description, 40) : '' }}</div>
                                    </div>
                                </div>
                                <span class="w-2 h-2 {{ ($team->active ?? true) ? 'bg-green-500' : 'bg-gray-400' }} rounded-full animate-pulse shadow-[0_0_10px_rgba(34,197,94,0.6)]"></span>
                            </div>
                        @endforeach
                    </div>
                @else
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
                @endif
            </div>
        </div>
    </div>
    {{-- ── LAST CONNECTIONS ── --}}
    <div class="glass p-6 rounded-2xl border border-white/5">
            <h3 class="font-bold text-lg mb-5 flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Last Connections
            </h3>

            @if($recentSessions->isEmpty())
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="w-12 h-12 bg-white/5 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-white/40 text-sm">No session data available</p>
                </div>
            @else
                <div class="space-y-1">
                    @foreach($recentSessions as $sess)
                        @php
                            $ua = $sess->user_agent ?? '';
                            $browser = str_contains($ua, 'Edg') ? 'Edge'
                                : (str_contains($ua, 'Chrome') ? 'Chrome'
                                : (str_contains($ua, 'Firefox') ? 'Firefox'
                                : (str_contains($ua, 'Safari') ? 'Safari'
                                : 'Browser')));
                            $os = str_contains($ua, 'Windows') ? 'Windows'
                                : (str_contains($ua, 'Mac OS') ? 'macOS'
                                : (str_contains($ua, 'Linux') ? 'Linux'
                                : (str_contains($ua, 'Android') ? 'Android'
                                : (str_contains($ua, 'iPhone') ? 'iOS' : 'Unknown'))));
                            $isCurrent = $sess->last_activity > now()->subMinutes(10)->timestamp;
                            $lastSeen  = \Carbon\Carbon::createFromTimestamp($sess->last_activity)->diffForHumans();
                        @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 transition-all group">
                            <div class="w-9 h-9 rounded-lg flex-shrink-0 flex items-center justify-center
                                {{ $isCurrent ? 'bg-indigo-500/20 text-indigo-400' : 'bg-white/5 text-white/40' }}">
                                @if($browser === 'Chrome')
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="4"/><path d="M12 2a10 10 0 000 20A10 10 0 0012 2zm0 2a8 8 0 016.32 3.09L12 12l-2.76-4.78A8 8 0 0112 4zm-7.9 6.54L9.72 12l-3.24 5.62A8 8 0 014.1 10.54zm3.62 8.38L12 13.2l4.28 5.72A8 8 0 017.72 18.92zm9.34-.84L12.28 12l5.54-1.46a8 8 0 01-1.26 8.54z"/></svg>
                                @elseif($browser === 'Firefox')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M12 7c2.76 0 5 2.24 5 5s-2.24 5-5 5-5-2.24-5-5 2.24-5 5-5z"/></svg>
                                @elseif($browser === 'Edge')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-white">{{ $browser }}</span>
                                    @if($isCurrent)
                                        <span class="text-[10px] px-1.5 py-0.5 bg-green-500/20 text-green-400 rounded-full font-bold leading-none">Current</span>
                                    @endif
                                </div>
                                <div class="text-xs text-white/40 mt-0.5 truncate">{{ $os }} &middot; {{ $sess->ip_address ?? 'Unknown IP' }}</div>
                            </div>
                            <div class="text-[11px] text-white/30 flex-shrink-0">{{ $lastSeen }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
    (function(){
        var datasets = {
            hours:  { labels: ['00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'], data: @json(array_values($activityByHour)) },
            days:   { labels: @json($activityByDayLabels),   data: @json($activityByDay) },
            months: { labels: @json($activityByMonthLabels), data: @json($activityByMonth) },
        };
        var currentView = 'hours';
        var chartInst   = null;

        function getCSSVar(v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); }
        function isLight()    { return document.documentElement.getAttribute('data-theme') === 'light'; }

        function buildChart() {
            var light         = isLight();
            var textPrimary   = getCSSVar('--text-primary') || (light ? '#1F2937' : '#ffffff');
            var textMuted     = getCSSVar('--text-muted')   || (light ? 'rgba(31,41,55,0.64)' : 'rgba(255,255,255,0.5)');
            var gridColor     = light ? 'rgba(31,41,55,0.07)' : 'rgba(255,255,255,0.04)';
            var tooltipBg     = light ? 'rgba(255,255,255,0.97)' : 'rgba(1,2,10,0.92)';
            var tooltipBorder = light ? 'rgba(31,41,55,0.12)' : 'rgba(255,255,255,0.1)';

            var set = datasets[currentView];
            var barBg, barBorder;
            if (currentView === 'hours') {
                barBg     = set.data.map(function(_, i) { return (i >= 9 && i <= 17) ? 'rgba(99,102,241,0.65)' : 'rgba(99,102,241,0.22)'; });
                barBorder = set.data.map(function(_, i) { return (i >= 9 && i <= 17) ? 'rgba(99,102,241,1)'    : 'rgba(99,102,241,0.55)'; });
            } else if (currentView === 'days') {
                barBg     = 'rgba(99,102,241,0.45)';
                barBorder = 'rgba(99,102,241,0.9)';
            } else {
                barBg     = 'rgba(139,92,246,0.45)';
                barBorder = 'rgba(139,92,246,0.9)';
            }

            var tooltipTitle = function(items) {
                if (currentView === 'hours') return items[0].label + ':00 – ' + items[0].label + ':59';
                return items[0].label;
            };

            var canvas = document.getElementById('activeTimesChart');
            if (!canvas) return;
            if (chartInst) { chartInst.destroy(); }

            chartInst = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: set.labels,
                    datasets: [{
                        label: 'Actions',
                        data: set.data,
                        backgroundColor: barBg,
                        borderColor: barBorder,
                        borderWidth: 1.5,
                        borderRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 350 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: tooltipBg,
                            titleColor: textPrimary,
                            bodyColor: textMuted,
                            borderColor: tooltipBorder,
                            borderWidth: 1,
                            callbacks: {
                                title: tooltipTitle,
                                label: function(item) { return item.raw + ' action' + (item.raw !== 1 ? 's' : ''); },
                            },
                        },
                    },
                    scales: {
                        x: { ticks: { color: textMuted, font: { size: currentView === 'hours' ? 10 : 9 }, maxRotation: currentView === 'months' ? 0 : 45 }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: { color: textMuted, stepSize: 1 }, grid: { color: gridColor } },
                    },
                },
            });
        }

        // Toggle buttons
        document.querySelectorAll('.at-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentView = this.dataset.view;
                document.querySelectorAll('.at-btn').forEach(function(b) {
                    b.className = b.className
                        .replace(/bg-indigo-500\/20|text-indigo-300|border-indigo-500\/30/g, '')
                        .trim();
                    b.classList.add('bg-white/5', 'text-white/30', 'border-white/5');
                });
                this.classList.remove('bg-white/5', 'text-white/30', 'border-white/5');
                this.classList.add('bg-indigo-500/20', 'text-indigo-300', 'border-indigo-500/30');
                buildChart();
            });
        });

        buildChart();

        new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (m.attributeName === 'data-theme') { buildChart(); }
            });
        }).observe(document.documentElement, { attributes: true });
    })();
    </script>

</div>
@endsection
