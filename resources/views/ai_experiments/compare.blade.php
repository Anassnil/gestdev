@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Experiment Comparison</h1>
                <p class="text-white/60 text-sm md:text-base">Comparing Exp #{{ $a->id }} vs Exp #{{ $b->id }}</p>
            </div>
            <a href="{{ route('ai.experiments.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm md:text-base">
                Back
            </a>
        </div>

        <!-- Comparison Results -->
        @if($better !== 'None')
            <div class="glass rounded-2xl p-4 md:p-6 border border-green-500/30 bg-green-500/5 mb-8">
                <div class="text-white/60 text-sm uppercase tracking-wide">Winner</div>
                <div class="text-2xl md:text-3xl font-black text-green-400 mt-2">
                    Experiment {{ $better === 'A' ? $a->id : $b->id }} Wins
                </div>
            </div>
        @endif

        <!-- Metrics Comparison Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Experiment A -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Experiment A (#{{ $a->id }})</h3>
                <div class="space-y-4">
                    <div>
                        <div class="text-white/60 text-sm">Best Accuracy</div>
                        <div class="text-2xl md:text-3xl font-black text-blue-400">{{ round($a_metrics['best_accuracy'] * 100, 1) }}%</div>
                    </div>
                    <div>
                        <div class="text-white/60 text-sm">Average Accuracy</div>
                        <div class="text-2xl md:text-3xl font-black text-white">{{ round($a_metrics['avg_accuracy'] * 100, 1) }}%</div>
                    </div>
                    <div>
                        <div class="text-white/60 text-sm">Best Loss</div>
                        <div class="text-2xl md:text-3xl font-black text-white">{{ round($a_metrics['best_loss'], 4) }}</div>
                    </div>
                    <div>
                        <div class="text-white/60 text-sm">Training Runs</div>
                        <div class="text-2xl md:text-3xl font-black text-white">{{ $a_metrics['runs_count'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Experiment B -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Experiment B (#{{ $b->id }})</h3>
                <div class="space-y-4">
                    <div>
                        <div class="text-white/60 text-sm">Best Accuracy</div>
                        <div class="text-2xl md:text-3xl font-black text-blue-400">{{ round($b_metrics['best_accuracy'] * 100, 1) }}%</div>
                    </div>
                    <div>
                        <div class="text-white/60 text-sm">Average Accuracy</div>
                        <div class="text-2xl md:text-3xl font-black text-white">{{ round($b_metrics['avg_accuracy'] * 100, 1) }}%</div>
                    </div>
                    <div>
                        <div class="text-white/60 text-sm">Best Loss</div>
                        <div class="text-2xl md:text-3xl font-black text-white">{{ round($b_metrics['best_loss'], 4) }}</div>
                    </div>
                    <div>
                        <div class="text-white/60 text-sm">Training Runs</div>
                        <div class="text-2xl md:text-3xl font-black text-white">{{ $b_metrics['runs_count'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparison Chart -->
        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10 mb-8">
            <h3 class="text-white font-bold text-lg mb-4">Accuracy Comparison</h3>
            <canvas id="comparisonChart" height="80"></canvas>
        </div>

        <!-- Statistical Analysis -->
        @php
            $ttest      = $report['ttest'] ?? null;
            $cohenD     = $report['cohen_d'] ?? null;
            $effectLabel= $report['effect_label'] ?? null;
            $aCI        = $report['a_ci'] ?? null;
            $bCI        = $report['b_ci'] ?? null;
        @endphp

        @if($ttest)
        <div class="glass rounded-2xl p-4 md:p-6 border border-white/10 mb-8">
            <h3 class="text-white font-bold text-lg mb-4">Statistical Analysis</h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white/5 rounded-xl p-4">
                    <div class="text-white/40 text-xs uppercase tracking-wide mb-1">t-statistic</div>
                    <div class="text-2xl font-black text-white font-mono">{{ $ttest['t'] }}</div>
                </div>
                <div class="bg-white/5 rounded-xl p-4">
                    <div class="text-white/40 text-xs uppercase tracking-wide mb-1">Degrees of Freedom</div>
                    <div class="text-2xl font-black text-white font-mono">{{ $ttest['df'] }}</div>
                </div>
                <div class="bg-white/5 rounded-xl p-4">
                    <div class="text-white/40 text-xs uppercase tracking-wide mb-1">p-value (2-tail)</div>
                    <div class="text-2xl font-black font-mono
                        {{ $ttest['p_value'] < 0.05 ? 'text-green-400' : ($ttest['p_value'] < 0.1 ? 'text-yellow-400' : 'text-white/50') }}">
                        {{ $ttest['p_value'] === 1.0 ? 'N/A' : $ttest['p_value'] }}
                    </div>
                </div>
                <div class="bg-white/5 rounded-xl p-4">
                    <div class="text-white/40 text-xs uppercase tracking-wide mb-1">Significant?</div>
                    @if($ttest['p_value'] === 1.0)
                        <div class="text-xl font-bold text-white/30">—</div>
                    @elseif($ttest['significant'])
                        <div class="text-xl font-black text-green-400">Yes ✓</div>
                        <div class="text-xs text-white/30 mt-1">p &lt; 0.05</div>
                    @else
                        <div class="text-xl font-black text-white/40">No</div>
                        <div class="text-xs text-white/30 mt-1">p ≥ 0.05</div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white/5 rounded-xl p-4">
                    <div class="text-white/40 text-xs uppercase tracking-wide mb-1">Cohen's d</div>
                    <div class="text-2xl font-black text-white font-mono">{{ $cohenD }}</div>
                    <div class="text-xs mt-1 capitalize
                        {{ match($effectLabel) { 'large' => 'text-orange-400', 'medium' => 'text-yellow-400', 'small' => 'text-blue-400', default => 'text-white/30' } }}">
                        {{ $effectLabel }} effect
                    </div>
                </div>
                @if($aCI)
                <div class="bg-white/5 rounded-xl p-4">
                    <div class="text-white/40 text-xs uppercase tracking-wide mb-1">Exp A — 95% CI</div>
                    <div class="text-sm font-bold text-white/70 font-mono mt-2">
                        [{{ round($aCI['lower'] * 100, 1) }}%, {{ round($aCI['upper'] * 100, 1) }}%]
                    </div>
                    <div class="text-xs text-white/30 mt-1">Mean: {{ round($aCI['mean'] * 100, 2) }}%</div>
                </div>
                @endif
                @if($bCI)
                <div class="bg-white/5 rounded-xl p-4">
                    <div class="text-white/40 text-xs uppercase tracking-wide mb-1">Exp B — 95% CI</div>
                    <div class="text-sm font-bold text-white/70 font-mono mt-2">
                        [{{ round($bCI['lower'] * 100, 1) }}%, {{ round($bCI['upper'] * 100, 1) }}%]
                    </div>
                    <div class="text-xs text-white/30 mt-1">Mean: {{ round($bCI['mean'] * 100, 2) }}%</div>
                </div>
                @endif
            </div>

            <p class="text-white/25 text-xs mt-4">
                Welch's two-sample t-test (unequal variances assumed). Effect size by Cohen's d:
                negligible &lt;0.2, small 0.2–0.5, medium 0.5–0.8, large &gt;0.8.
            </p>
        </div>
        @endif

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3">
            @if($better === 'A')
                <form method="POST" action="{{ route('ai.experiments.promote', $a) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all text-center">
                        Promote Experiment A
                    </button>
                </form>
            @endif
            @if($better === 'B')
                <form method="POST" action="{{ route('ai.experiments.promote', $b) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all text-center">
                        Promote Experiment B
                    </button>
                </form>
            @endif
            <a href="{{ route('ai.experiments.index') }}" class="flex-1 px-4 py-3 bg-white/5 border border-white/10 rounded-xl font-bold hover:bg-white/10 transition-all text-center">
                Back to Experiments
            </a>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('comparisonChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Best Accuracy', 'Average Accuracy'],
            datasets: [
                {
                    label: 'Experiment A',
                    data: [
                        {!! $a_metrics['best_accuracy'] !!} * 100,
                        {!! $a_metrics['avg_accuracy'] !!} * 100
                    ],
                    backgroundColor: '#0D00A4',
                },
                {
                    label: 'Experiment B',
                    data: [
                        {!! $b_metrics['best_accuracy'] !!} * 100,
                        {!! $b_metrics['avg_accuracy'] !!} * 100
                    ],
                    backgroundColor: '#0EA5E9',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: { color: 'rgba(255, 255, 255, 0.7)' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
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
</script>
@endsection
