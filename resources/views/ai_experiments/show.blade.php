@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Experiment #{{ $experiment->id }}</h1>
                <p class="text-white/60 text-sm md:text-base">{{ $experiment->model->name }} • {{ $experiment->dataset->name ?? 'No Dataset' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('ai.experiments.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm md:text-base">
                    Back
                </a>
            </div>
        </div>

        <!-- Status & Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Status</div>
                <div class="text-2xl md:text-3xl font-black text-white mt-2 capitalize">{{ $experiment->status }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Best Accuracy</div>
                <div class="text-2xl md:text-3xl font-black text-blue-400 mt-2">{{ round($bestAccuracy * 100, 1) }}%</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Avg Accuracy</div>
                <div class="text-2xl md:text-3xl font-black text-white mt-2">{{ round($avgAccuracy * 100, 1) }}%</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Training Runs</div>
                <div class="text-2xl md:text-3xl font-black text-white mt-2">{{ count($trainingData) }}</div>
            </div>
        </div>

        <!-- Performance Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Accuracy Progress -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Accuracy Progress</h3>
                <canvas id="accuracyChart" height="80"></canvas>
            </div>

            <!-- Loss Progress -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Loss Progress</h3>
                <canvas id="lossChart" height="80"></canvas>
            </div>
        </div>

        <!-- Training Runs Table -->
        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10 mb-8">
            <h3 class="text-white font-bold text-lg mb-4">Training Runs</h3>
            @if(count($trainingData) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Run ID</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Status</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Accuracy</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Loss</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trainingData as $run)
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-all">
                                    <td class="py-3 px-4 text-white font-semibold">#{{ $run['id'] }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-blue-500/20 text-blue-300 rounded text-xs font-semibold capitalize">{{ $run['status'] }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-white">{{ round($run['accuracy'] * 100, 2) }}%</td>
                                    <td class="py-3 px-4 text-white">{{ round($run['loss'], 4) }}</td>
                                    <td class="py-3 px-4 text-white/60">{{ $run['created_at']->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-white/60">No training runs yet.</p>
            @endif
        </div>

        <!-- Compare & Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div>
                <h3 class="text-white font-bold text-lg mb-4">Compare with Other Experiment</h3>
                <form method="GET" action="{{ route('ai.experiments.compare', $experiment) }}" class="flex flex-col sm:flex-row gap-2">
                    <select name="other_experiment_id" class="flex-1 px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white">
                        @foreach(\App\Models\Experiment::where('id','!=',$experiment->id)->get() as $o)
                            <option value="{{ $o->id }}" class="bg-[#01020a]">Exp #{{ $o->id }} - {{ $o->model->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all">
                        Compare
                    </button>
                </form>
            </div>
            <div>
                <h3 class="text-white font-bold text-lg mb-4">Promote Best Run</h3>
                <form method="POST" action="{{ route('ai.experiments.promote', $experiment) }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 rounded-xl font-bold text-white hover:bg-green-700 transition-all">
                        Auto-promote Best Run
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const accuracyData = {!! $accuracies !!};
    const lossData = {!! $losses !!};

    function getChartThemeColors() {
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        return {
            tick: isLight ? 'rgba(71, 85, 105, 0.95)' : 'rgba(255, 255, 255, 0.6)',
            grid: isLight ? 'rgba(15, 23, 42, 0.12)' : 'rgba(255, 255, 255, 0.05)',
        };
    }

    function baseChartOptions() {
        const colors = getChartThemeColors();
        return {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: colors.tick },
                    grid: { color: colors.grid }
                },
                x: {
                    ticks: { color: colors.tick },
                    grid: { display: false }
                }
            }
        };
    }

    // Accuracy Chart
    const ctx1 = document.getElementById('accuracyChart').getContext('2d');
    const accuracyChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: Array.from({length: accuracyData.length}, (_, i) => 'Run ' + (i + 1)),
            datasets: [{
                label: 'Accuracy',
                data: accuracyData.map(v => v * 100),
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
            ...baseChartOptions(),
            scales: {
                ...baseChartOptions().scales,
                y: {
                    ...baseChartOptions().scales.y,
                    max: 100,
                },
            },
        },
    });

    // Loss Chart
    const ctx2 = document.getElementById('lossChart').getContext('2d');
    const lossChart = new Chart(ctx2, {
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
        options: baseChartOptions(),
    });

    function applyChartTheme() {
        const colors = getChartThemeColors();

        [accuracyChart, lossChart].forEach((chart) => {
            chart.options.scales.x.ticks.color = colors.tick;
            chart.options.scales.y.ticks.color = colors.tick;
            chart.options.scales.y.grid.color = colors.grid;
            chart.update();
        });
    }

    window.addEventListener('theme-changed', applyChartTheme);
</script>
@endsection
