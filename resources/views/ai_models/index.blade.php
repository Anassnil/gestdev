@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">AI Models</h1>
                <p class="text-white/60 text-sm md:text-base">Manage your ML models and track performance</p>
            </div>
            <form method="POST" action="{{ route('ai.models.store') }}" class="w-full sm:w-auto flex flex-col sm:flex-row gap-2">
                @csrf
                <input name="name" placeholder="Model name" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white outline-none text-sm md:text-base" required />
                <input name="type" placeholder="Type" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white outline-none text-sm md:text-base" />
                <button class="px-4 py-2 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all text-sm md:text-base whitespace-nowrap">Create</button>
            </form>
        </div>

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Total Models</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ count($models) }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Avg Best Accuracy</div>
                <div class="text-3xl md:text-4xl font-black text-blue-400 mt-2">
                    {{ count($models) > 0 ? round(collect($models)->pluck('best_accuracy')->avg() * 100, 1) : 0 }}%
                </div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Total Experiments</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ collect($models)->pluck('experiments_count')->sum() }}</div>
            </div>
        </div>

        <!-- Models Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($models as $item)
                <a href="{{ route('ai.models.show', $item['model']) }}" class="glass rounded-2xl p-4 md:p-6 border border-white/10 hover:border-blue-500/40 hover:shadow-lg transition-all group">
                    <!-- Model Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg md:text-xl font-bold text-white group-hover:text-blue-400 transition-all">
                                {{ $item['model']->name }}
                            </h3>
                            <p class="text-white/60 text-xs md:text-sm">
                                {{ $item['model']->type ?? 'General' }} • {{ ucfirst($item['model']->status) }}
                            </p>
                        </div>
                    </div>

                    <!-- Metrics -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-blue-500/10 rounded-lg p-2 md:p-3">
                            <div class="text-white/60 text-xs">Best Accuracy</div>
                            <div class="text-xl md:text-2xl font-black text-blue-400 mt-1">
                                {{ round($item['best_accuracy'] * 100, 1) }}%
                            </div>
                        </div>
                        <div class="bg-white/5 rounded-lg p-2 md:p-3">
                            <div class="text-white/60 text-xs">Average Accuracy</div>
                            <div class="text-xl md:text-2xl font-black text-white mt-1">
                                {{ round($item['avg_accuracy'] * 100, 1) }}%
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-2 text-xs md:text-sm">
                        <div class="text-white/70">
                            <span class="text-white/50">Experiments:</span>
                            <span class="font-bold text-white ml-1">{{ $item['experiments_count'] }}</span>
                        </div>
                        <div class="text-white/70">
                            <span class="text-white/50">Versions:</span>
                            <span class="font-bold text-white ml-1">{{ $item['versions_count'] }}</span>
                        </div>
                    </div>

                    <!-- Latest Experiment -->
                    @if($item['latest_experiment'])
                        <div class="mt-4 pt-4 border-t border-white/10">
                            <p class="text-xs text-white/60">
                                Latest: Exp #{{ $item['latest_experiment']->id }} • {{ $item['latest_experiment']->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @endif
                </a>
            @empty
                <div class="col-span-full glass rounded-2xl p-8 md:p-12 border border-white/10 text-center">
                    <p class="text-white/60 mb-4">No models yet. Create your first model to get started.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
