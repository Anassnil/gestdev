@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Training Jobs</h1>
                <p class="text-white/60 text-sm md:text-base">Monitor and manage all training jobs</p>
            </div>
            <a href="{{ route('ai.experiments.index') }}" class="px-4 py-2 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all text-sm md:text-base">
                Start New Job
            </a>
        </div>

        <!-- Status Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Total Jobs</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ $runs->total() }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Running</div>
                <div class="text-3xl md:text-4xl font-black text-blue-400 mt-2">
                    {{ $runs->count() > 0 ? collect($runs->items())->where('run.status', 'running')->count() : 0 }}
                </div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Completed</div>
                <div class="text-3xl md:text-4xl font-black text-green-400 mt-2">
                    {{ $runs->count() > 0 ? collect($runs->items())->where('run.status', 'completed')->count() : 0 }}
                </div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Queued</div>
                <div class="text-3xl md:text-4xl font-black text-yellow-400 mt-2">
                    {{ $runs->count() > 0 ? collect($runs->items())->where('run.status', 'queued')->count() : 0 }}
                </div>
            </div>
        </div>

        <!-- Training Jobs Table -->
        @if($runs->count() > 0)
            <div class="space-y-4">
                @foreach($runs as $item)
                    <a href="{{ route('ai.training_runs.show', $item['run']) }}" class="block glass rounded-2xl p-4 md:p-6 border border-white/10 hover:border-blue-500/40 transition-all">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                            <!-- Info -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-lg font-bold text-white">
                                        {{ $item['run']->experiment->model->name }}
                                    </h3>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ 
                                        $item['run']->status === 'completed' ? 'bg-green-500/20 text-green-300' : 
                                        ($item['run']->status === 'running' ? 'bg-blue-500/20 text-blue-300' : 
                                        ($item['run']->status === 'queued' ? 'bg-yellow-500/20 text-yellow-300' : 'bg-red-500/20 text-red-300'))
                                    }}">
                                        {{ ucfirst($item['run']->status) }}
                                    </span>
                                </div>
                                <p class="text-white/60 text-sm">
                                    Experiment #{{ $item['run']->experiment->id }} • Run #{{ $item['run']->id }}
                                </p>
                            </div>

                            <!-- Progress -->
                            <div>
                                <div class="text-white/60 text-sm mb-2">Progress</div>
                                <div class="w-full bg-white/10 rounded-full h-2 mb-2">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $item['progress'] }}%"></div>
                                </div>
                                <p class="text-white text-sm font-semibold">{{ $item['progress'] }}%</p>
                            </div>

                            <!-- Metrics -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-white/5 rounded-lg p-3">
                                    <div class="text-white/60 text-xs">Accuracy</div>
                                    <div class="text-xl font-black text-blue-400 mt-1">
                                        {{ round($item['accuracy'] * 100, 1) }}%
                                    </div>
                                </div>
                                <div class="bg-white/5 rounded-lg p-3">
                                    <div class="text-white/60 text-xs">Started</div>
                                    <div class="text-sm text-white/70 mt-1">
                                        {{ $item['run']->started_at?->diffForHumans() ?? 'Not started' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($runs->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $runs->links() }}
                </div>
            @endif
        @else
            <div class="glass rounded-2xl p-8 md:p-12 border border-white/10 text-center">
                <p class="text-white/60 mb-4">No training jobs yet.</p>
                <a href="{{ route('ai.experiments.index') }}" class="inline-block px-4 py-2 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all">
                    Start Your First Training Job
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
