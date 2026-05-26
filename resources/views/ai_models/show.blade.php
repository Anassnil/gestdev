@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">{{ $model->name }}</h1>
                <p class="text-white/60 text-sm md:text-base">{{ $model->type ?? 'General' }} • {{ $model->status }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($latestExperiment)
                    <a href="{{ route('ai.training_runs.create', ['experiment_id' => $latestExperiment->id]) }}" class="px-4 py-2 bg-blue-600 rounded-xl text-white hover:bg-blue-700 transition-all text-sm md:text-base">Start Training</a>
                @endif
                <a href="{{ route('ai.experiments.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm md:text-base">Experiments</a>
                @if(Route::has('ai.models.index'))
                    <a href="{{ route('ai.models.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm md:text-base">Back</a>
                @endif
            </div>
        </div>

        @if($model->experiments->count() === 0)
            <div class="glass rounded-2xl p-4 md:p-6 border border-amber-500/30 bg-amber-500/5 mb-8">
                <h3 class="text-amber-300 font-bold text-lg mb-2">This model is not running yet</h3>
                <p class="text-white/70 text-sm mb-4">Create an experiment first, then start a training job. This is why all metrics are currently 0%.</p>

                @if($availableDatasets->count() > 0)
                    <form method="POST" action="{{ route('ai.experiments.store') }}" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input type="hidden" name="ai_model_id" value="{{ $model->id }}">

                        <select name="dataset_id" class="px-3 py-2 rounded-xl bg-[#02010A] border border-white/10 text-white flex-1" required>
                            <option value="">Select a dataset</option>
                            @foreach($availableDatasets as $dataset)
                                <option value="{{ $dataset->id }}">{{ $dataset->name }}</option>
                            @endforeach
                        </select>

                        <button class="px-4 py-2 bg-amber-500 text-black rounded-xl font-bold hover:bg-amber-400 transition-all">
                            Create Experiment
                        </button>
                    </form>
                @else
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('ai.datasets.create') }}" class="px-4 py-2 bg-blue-600 rounded-xl text-white hover:bg-blue-700 transition-all text-sm">Upload Dataset</a>
                        <a href="{{ route('ai.datasets.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm">View Datasets</a>
                    </div>
                    <p class="text-white/60 text-xs mt-3">You need at least one dataset before creating an experiment.</p>
                @endif
            </div>
        @endif

        <!-- Performance Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Best Accuracy</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ round($bestAccuracy * 100, 1) }}%</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Avg Accuracy</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ round($avgAccuracy * 100, 1) }}%</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Experiments</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ count($model->experiments) }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Versions</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ count($model->versions) }}</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Accuracy Chart -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Accuracy Trend</h3>
                @if(count(json_decode($accuracyHistory, true)) > 0)
                    <canvas id="accuracyChart" height="80"></canvas>
                @else
                    <div class="h-28 flex items-center justify-center text-sm text-white/60 bg-white/5 rounded-xl border border-white/10">
                        No accuracy metrics yet. Start a training job to populate this chart.
                    </div>
                @endif
            </div>

            <!-- Loss Chart -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Loss Trend</h3>
                @if(count(json_decode($lossHistory, true)) > 0)
                    <canvas id="lossChart" height="80"></canvas>
                @else
                    <div class="h-28 flex items-center justify-center text-sm text-white/60 bg-white/5 rounded-xl border border-white/10">
                        No loss metrics yet. Start a training job to populate this chart.
                    </div>
                @endif
            </div>
        </div>

        <!-- Versions -->
        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10 mb-8">
            <h3 class="text-white font-bold text-lg mb-4">Model Versions</h3>
            @if($model->versions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Version</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Status</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($model->versions as $v)
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-all">
                                    <td class="py-3 px-4 text-white">{{ $v->version ?? 'v' . $v->id }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-blue-500/20 text-blue-300 rounded text-xs font-semibold">{{ $v->status }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-white/60">{{ $v->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-white/60">No versions created yet.</p>
            @endif
        </div>

        <!-- Experiments -->
        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
            <h3 class="text-white font-bold text-lg mb-4">Recent Experiments</h3>
            @if($model->experiments->count() > 0)
                <div class="space-y-3">
                    @foreach($model->experiments->sortByDesc('created_at')->take(5) as $exp)
                        <a href="{{ route('ai.experiments.show', $exp) }}" class="block p-4 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 hover:border-blue-500/40 transition-all">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-white font-semibold">Experiment #{{ $exp->id }}</div>
                                    <div class="text-white/60 text-sm">{{ $exp->dataset?->name ?? 'Unknown Dataset' }} • {{ $exp->trainingRuns->count() }} runs</div>
                                </div>
                                <span class="px-2 py-1 bg-green-500/20 text-green-300 rounded text-xs font-semibold">{{ $exp->status }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-white/60">No experiments yet.</p>
            @endif
        </div>
    </div>
</div>

<script>
    const accuracyCanvas = document.getElementById('accuracyChart');
    const lossCanvas = document.getElementById('lossChart');
    const accuracyData = {!! $accuracyHistory !!};
    const lossData = {!! $lossHistory !!};

    if (accuracyCanvas && accuracyData.length > 0) {
        const ctx1 = accuracyCanvas.getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: Array.from({length: accuracyData.length}, (_, i) => 'Run ' + (i + 1)),
                datasets: [{
                    label: 'Accuracy',
                    data: accuracyData,
                    borderColor: '#0D00A4',
                    backgroundColor: 'rgba(13, 0, 164, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#0D00A4',
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1,
                        ticks: { color: 'rgba(255, 255, 255, 0.6)' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: 'rgba(255, 255, 255, 0.6)' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    if (lossCanvas && lossData.length > 0) {
        const ctx2 = lossCanvas.getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: Array.from({length: lossData.length}, (_, i) => 'Run ' + (i + 1)),
                datasets: [{
                    label: 'Loss',
                    data: lossData,
                    borderColor: '#EAB308',
                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#EAB308',
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: 'rgba(255, 255, 255, 0.6)' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: 'rgba(255, 255, 255, 0.6)' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
</script>
@endsection
