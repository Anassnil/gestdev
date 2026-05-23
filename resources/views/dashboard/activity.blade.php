@extends('layouts.dashboard')

@section('dashboard-content')
<div class="space-y-6 p-2">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight flex items-center gap-3">
                <div class="p-2 bg-purple-600 rounded-lg shadow-lg shadow-purple-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                All Activity
            </h1>
            <p class="text-white/50 mt-1 ml-12">Complete history of changes across your projects.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 glass rounded-xl border border-white/10 hover:bg-white/10 transition-all text-sm font-semibold">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
    </div>

    @if($activities->isEmpty())
    <div class="glass p-12 rounded-2xl border border-white/5 text-center">
        <div class="w-20 h-20 mx-auto bg-white/5 rounded-full flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white/10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" />
            </svg>
        </div>
        <p class="text-white/60 font-medium text-lg">No activity yet</p>
        <p class="text-sm text-white/30 mt-1">Start creating boards and tasks to see your activity here.</p>
    </div>
    @else
    <div class="glass p-6 rounded-2xl border border-white/5">
        <div class="space-y-1">
            @php $lastDate = null; @endphp
            @foreach($activities as $activity)
                @php $date = $activity['time']->format('F j, Y'); @endphp
                @if($date !== $lastDate)
                    @if(!$loop->first)
                        <div class="my-3"></div>
                    @endif
                    <div class="px-3 py-2">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20">{{ $date }}</span>
                    </div>
                    @php $lastDate = $date; @endphp
                @endif
                <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-white/5 transition-all">
                    <div class="mt-0.5 flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center
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
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold truncate">{{ $activity['title'] }}</span>
                            @if($activity['board'])
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-white/5 text-white/40 font-medium flex-shrink-0">{{ $activity['board'] }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-white/40">{{ $activity['desc'] }}</span>
                            <span class="text-[10px] text-white/20">&middot;</span>
                            <span class="text-[10px] text-white/20">{{ $activity['time']->diffForHumans() }}</span>
                            <span class="text-[10px] text-white/20">&middot;</span>
                            <span class="text-[10px] text-white/20">{{ $activity['time']->format('g:i A') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
