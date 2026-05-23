@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Training Run #{{ $run->id }}</h1>
                <p class="text-white/60 text-sm md:text-base">{{ $run->experiment->model->name }} • Experiment #{{ $run->experiment->id }}</p>
            </div>
            <div class="flex gap-2">
                @if($run->status === 'running' || $run->status === 'queued')
                    <form method="POST" action="{{ route('ai.training_runs.cancel', $run) }}" onsubmit="return confirm('Cancel this training job?');">
                        @csrf
                        <button class="px-4 py-2 bg-red-600 rounded-xl font-bold text-white hover:bg-red-700 transition-all text-sm md:text-base">
                            Cancel
                        </button>
                    </form>
                @endif
                <a href="{{ route('ai.training_runs.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm md:text-base">
                    Back
                </a>
            </div>
        </div>

        <!-- Status Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Progress Card -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-white font-bold text-lg">Progress</h3>
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ 
                        $run->status === 'completed' ? 'bg-green-500/20 text-green-300' : 
                        ($run->status === 'running' ? 'bg-blue-500/20 text-blue-300' : 
                        ($run->status === 'queued' ? 'bg-yellow-500/20 text-yellow-300' : 'bg-red-500/20 text-red-300'))
                    }}">
                        {{ ucfirst($run->status) }}
                    </span>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="w-full bg-white/10 rounded-full h-4 mb-2">
                        <div id="progress-bar" class="bg-gradient-to-r from-blue-600 to-blue-500 h-4 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                    </div>
                    <div class="flex justify-between items-center">
                        <p id="progress-text" class="text-white font-bold text-lg">{{ $progress }}%</p>
                        <p class="text-white/60 text-sm">{{ $run->started_at?->diffForHumans() ?? 'Not started' }}</p>
                    </div>
                </div>

                <!-- Timing -->
                <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-white/10">
                    <div>
                        <p class="text-white/60 text-sm">Started</p>
                        <p class="text-white font-semibold">{{ $run->started_at?->format('H:i:s') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm">Duration</p>
                        <p class="text-white font-semibold">{{ $run->ended_at ? $run->started_at->diffInSeconds($run->ended_at) : now()->diffInSeconds($run->started_at) }}s</p>
                    </div>
                </div>
            </div>

            <!-- Metrics Card -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Current Metrics</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-500/10 rounded-lg p-3">
                        <p class="text-white/60 text-sm">Accuracy</p>
                        <p id="accuracy-metric" class="text-3xl font-black text-blue-400 mt-2">{{ round($currentAccuracy * 100, 1) }}%</p>
                    </div>
                    <div class="bg-yellow-500/10 rounded-lg p-3">
                        <p class="text-white/60 text-sm">Loss</p>
                        <p id="loss-metric" class="text-3xl font-black text-yellow-400 mt-2">0.0000</p>
                    </div>
                    <div class="bg-green-500/10 rounded-lg p-3">
                        <p class="text-white/60 text-sm">Epochs</p>
                        <p class="text-3xl font-black text-green-400 mt-2">{{ $run->parameters['epochs'] ?? 10 }}</p>
                    </div>
                    <div class="bg-purple-500/10 rounded-lg p-3">
                        <p class="text-white/60 text-sm">Batch Size</p>
                        <p class="text-3xl font-black text-purple-400 mt-2">{{ $run->parameters['batch_size'] ?? 32 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Accuracy Chart -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Accuracy Over Time</h3>
                <canvas id="accuracyChart" height="80"></canvas>
            </div>

            <!-- Loss Chart -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Loss Over Time</h3>
                <canvas id="lossChart" height="80"></canvas>
            </div>
        </div>

        <!-- Parameters -->
        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
            <h3 class="text-white font-bold text-lg mb-4">Training Parameters</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($run->parameters ?? [] as $key => $value)
                    <div class="bg-white/5 rounded-lg p-3">
                        <p class="text-white/60 text-sm capitalize">{{ str_replace('_', ' ', $key) }}</p>
                        <p class="text-white font-semibold mt-1">{{ is_array($value) ? json_encode($value) : $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    const runId = {{ $run->id }};
    const accuraciesData = {!! $accuracies !!};
    const lossesData = {!! $losses !!};

    // Accuracy Chart
    const ctx1 = document.getElementById('accuracyChart').getContext('2d');
    const accuracyChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: Array.from({length: accuraciesData.length}, (_, i) => 'Step ' + (i + 1)),
            datasets: [{
                label: 'Accuracy',
                data: accuraciesData.map(v => v * 100),
                borderColor: '#0D00A4',
                backgroundColor: 'rgba(13, 0, 164, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#0D00A4',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { color: 'rgba(255, 255, 255, 0.6)' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                x: { ticks: { color: 'rgba(255, 255, 255, 0.6)' }, grid: { display: false } }
            }
        }
    });

    // Loss Chart
    const ctx2 = document.getElementById('lossChart').getContext('2d');
    const lossChart = new Chart(ctx2, {
        type: 'line',
        data: {
            labels: Array.from({length: lossesData.length}, (_, i) => 'Step ' + (i + 1)),
            datasets: [{
                label: 'Loss',
                data: lossesData,
                borderColor: '#EAB308',
                backgroundColor: 'rgba(234, 179, 8, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#EAB308',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: 'rgba(255, 255, 255, 0.6)' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                x: { ticks: { color: 'rgba(255, 255, 255, 0.6)' }, grid: { display: false } }
            }
        }
    });

    // Poll for progress updates
    @if($run->status === 'running' || $run->status === 'queued')
    const pollProgress = setInterval(async () => {
        try {
            const response = await fetch(`/ai/training-runs/${runId}/progress`);
            const data = await response.json();

            // Update progress bar
            document.getElementById('progress-bar').style.width = data.progress + '%';
            document.getElementById('progress-text').textContent = data.progress + '%';

            // Update metrics
            document.getElementById('accuracy-metric').textContent = (data.accuracy * 100).toFixed(1) + '%';
            document.getElementById('loss-metric').textContent = data.loss.toFixed(4);

            // Stop polling if completed
            if (data.status === 'completed' || data.status === 'failed' || data.status === 'cancelled') {
                clearInterval(pollProgress);
                location.reload();
            }
        } catch (error) {
            console.error('Error fetching progress:', error);
        }
    }, 2000); // Poll every 2 seconds
    @endif
</script>
@endsection
