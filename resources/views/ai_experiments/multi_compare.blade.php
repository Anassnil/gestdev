@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-7xl mx-auto">

        {{-- ── Header ─────────────────────────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Multi-Model Comparison</h1>
                <p class="text-white/50 text-sm md:text-base mt-1">
                    Statistical analysis across multiple experiments
                </p>
            </div>
            <a href="{{ route('ai.experiments.index') }}"
               class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm text-white/70">
                ← Back to Experiments
            </a>
        </div>

        @if(session('error'))
            <div class="glass rounded-2xl p-4 border border-red-500/30 bg-red-500/5 mb-6 text-red-400 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($comparison === null)
        {{-- ── Experiment Selection Form ──────────────────────────────────────── --}}
        <div class="glass rounded-2xl p-6 md:p-8 border border-white/10">
            <h2 class="text-xl font-bold text-white mb-2">Select Experiments to Compare</h2>
            <p class="text-white/50 text-sm mb-6">Choose 2 or more experiments for a statistical comparison report.</p>

            <form method="GET" action="{{ route('ai.experiments.multi_compare') }}">
                <div class="space-y-3 mb-8">
                    @forelse($allExperiments as $exp)
                        <label class="flex items-center gap-4 p-4 rounded-xl border border-white/10 bg-white/3 hover:bg-white/6 cursor-pointer transition-all group">
                            <input type="checkbox" name="ids[]" value="{{ $exp->id }}"
                                   class="w-4 h-4 accent-blue-500 cursor-pointer">
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-white">
                                    Experiment #{{ $exp->id }}
                                    <span class="text-white/40 font-normal ml-2">{{ $exp->model->name }}</span>
                                </div>
                                <div class="text-xs text-white/40 mt-0.5">
                                    Dataset: {{ $exp->dataset->name }} &nbsp;·&nbsp; Status: {{ $exp->status }}
                                </div>
                            </div>
                            <div class="text-xs text-white/30 group-hover:text-white/50 transition-colors">
                                #{{ $exp->id }}
                            </div>
                        </label>
                    @empty
                        <p class="text-white/40 text-center py-8">No experiments found. Create some experiments first.</p>
                    @endforelse
                </div>

                @if($allExperiments->isNotEmpty())
                    <button type="submit"
                            class="px-6 py-3 bg-[#0D00A4] hover:bg-[#1500c9] rounded-xl font-bold text-white transition-all">
                        Run Comparison →
                    </button>
                @endif
            </form>
        </div>

        @else
        {{-- ── Results ─────────────────────────────────────────────────────────── --}}

        @php
            $ranking    = $comparison['ranking'];
            $pairwise   = $comparison['pairwise'];
            $stats      = $comparison['stats'];
            $winnerId   = $comparison['winner'];
            $expMap     = $experiments->keyBy('id');
            $colors     = ['#0D00A4','#0EA5E9','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6'];
        @endphp

        {{-- Winner banner --}}
        @if($winnerId)
        <div class="glass rounded-2xl p-5 border border-yellow-500/30 bg-yellow-500/5 mb-8 flex items-center gap-4">
            <div class="text-3xl">🏆</div>
            <div>
                <div class="text-yellow-300 font-black text-xl">
                    Experiment #{{ $winnerId }} Wins
                    @if($expMap->has($winnerId))
                        — {{ $expMap[$winnerId]->model->name }}
                    @endif
                </div>
                <div class="text-white/50 text-sm mt-0.5">
                    Best accuracy: {{ round($stats[$winnerId]['best_accuracy'] * 100, 2) }}%
                    &nbsp;·&nbsp; {{ $stats[$winnerId]['runs_count'] }} training run(s)
                </div>
            </div>
        </div>
        @endif

        {{-- New comparison link --}}
        <div class="flex justify-end mb-6">
            <a href="{{ route('ai.experiments.multi_compare') }}"
               class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-sm text-white/60 hover:bg-white/10 transition-all">
                ↺ New Comparison
            </a>
        </div>

        {{-- ── Experiment cards ──────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-10">
            @foreach($ranking as $idx => $s)
            @php $exp = $expMap[$s['id']]; $color = $colors[$idx % count($colors)]; @endphp
            <div class="glass rounded-2xl p-5 border"
                 style="border-color: {{ $color }}44; background: linear-gradient(135deg, {{ $color }}08, transparent);">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white"
                              style="background:{{ $color }}55;">
                            #{{ $s['rank'] }}
                        </span>
                        <span class="font-bold text-white text-sm">Exp #{{ $s['id'] }}</span>
                    </div>
                    @if($s['id'] === $winnerId)
                        <span class="text-xs text-yellow-300 font-semibold">🏆 Best</span>
                    @elseif($s['gap_pct'] > 0)
                        <span class="text-xs text-white/40">-{{ $s['gap_pct'] }}%</span>
                    @endif
                </div>
                <div class="text-white/60 text-xs mb-2">{{ $exp->model->name }}</div>

                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div>
                        <div class="text-white/40 text-xs">Best Acc</div>
                        <div class="text-2xl font-black" style="color:{{ $color }};">
                            {{ round($s['best_accuracy'] * 100, 1) }}%
                        </div>
                    </div>
                    <div>
                        <div class="text-white/40 text-xs">Avg Acc</div>
                        <div class="text-xl font-black text-white">
                            {{ round($s['avg_accuracy'] * 100, 1) }}%
                        </div>
                    </div>
                    <div>
                        <div class="text-white/40 text-xs">95% CI</div>
                        <div class="text-xs text-white/70 font-mono mt-0.5">
                            [{{ round($s['accuracy_ci']['lower'] * 100, 1) }},
                            {{ round($s['accuracy_ci']['upper'] * 100, 1) }}]%
                        </div>
                    </div>
                    <div>
                        <div class="text-white/40 text-xs">Runs</div>
                        <div class="text-xl font-black text-white">{{ $s['runs_count'] }}</div>
                    </div>
                </div>

                @if($s['gap'] > 0)
                <div class="mt-3 pt-3 border-t border-white/5 text-xs text-white/30">
                    {{ round($s['gap'] * 100, 2) }}% behind leader
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- ── Charts ──────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
            <div class="glass rounded-2xl p-5 border border-white/10">
                <h3 class="text-white font-bold mb-4">Accuracy Comparison</h3>
                <canvas id="accuracyChart" height="160"></canvas>
            </div>
            <div class="glass rounded-2xl p-5 border border-white/10">
                <h3 class="text-white font-bold mb-4">Best Loss Comparison</h3>
                <canvas id="lossChart" height="160"></canvas>
            </div>
        </div>

        {{-- ── Statistical Significance Matrix ─────────────────────────────────── --}}
        @if(count($pairwise) > 0)
        <div class="glass rounded-2xl p-5 md:p-6 border border-white/10 mb-10 overflow-x-auto">
            <h3 class="text-white font-bold mb-1">Pairwise Statistical Significance</h3>
            <p class="text-white/40 text-xs mb-5">
                Welch's t-test (two-tailed). p &lt; 0.05 = statistically significant difference.
            </p>

            @php
                $expIds = $experiments->pluck('id')->toArray();
            @endphp

            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-white/40 font-normal p-2 text-xs">Exp A → B</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">t-stat</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">df</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">p-value</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Significant?</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Cohen's d</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Effect</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Winner</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pairwise as $key => $pw)
                    <tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
                        <td class="p-2 text-white/70 font-mono text-xs">
                            #{{ $pw['exp_a'] }} vs #{{ $pw['exp_b'] }}
                        </td>
                        <td class="p-2 text-center text-white/60 font-mono text-xs">
                            {{ $pw['t_stat'] }}
                        </td>
                        <td class="p-2 text-center text-white/50 font-mono text-xs">
                            {{ $pw['df'] }}
                        </td>
                        <td class="p-2 text-center font-mono text-xs
                            {{ $pw['p_value'] < 0.05 ? 'text-green-400' : ($pw['p_value'] < 0.1 ? 'text-yellow-400' : 'text-white/40') }}">
                            {{ $pw['p_value'] === 1.0 ? 'N/A' : $pw['p_value'] }}
                        </td>
                        <td class="p-2 text-center">
                            @if($pw['p_value'] === 1.0)
                                <span class="text-white/30 text-xs">—</span>
                            @elseif($pw['significant'])
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400 font-semibold">Yes</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-white/5 text-white/30">No</span>
                            @endif
                        </td>
                        <td class="p-2 text-center text-white/60 font-mono text-xs">
                            {{ $pw['cohen_d'] }}
                        </td>
                        <td class="p-2 text-center">
                            @php
                                $effectColors = ['negligible' => 'text-white/30', 'small' => 'text-blue-400', 'medium' => 'text-yellow-400', 'large' => 'text-orange-400'];
                            @endphp
                            <span class="text-xs capitalize {{ $effectColors[$pw['effect_label']] ?? 'text-white/40' }}">
                                {{ $pw['effect_label'] }}
                            </span>
                        </td>
                        <td class="p-2 text-center text-xs">
                            @if($pw['winner'] === 'tie')
                                <span class="text-white/30">Tie</span>
                            @elseif($pw['winner'] === 'a')
                                <span class="text-blue-400 font-semibold">#{{ $pw['exp_a'] }}</span>
                            @else
                                <span class="text-blue-400 font-semibold">#{{ $pw['exp_b'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── Performance Ranking Table ────────────────────────────────────────── --}}
        <div class="glass rounded-2xl p-5 md:p-6 border border-white/10 mb-10 overflow-x-auto">
            <h3 class="text-white font-bold mb-1">Performance Ranking</h3>
            <p class="text-white/40 text-xs mb-5">Ranked by best accuracy across all training runs.</p>

            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-white/40 font-normal p-2 text-xs">Rank</th>
                        <th class="text-left text-white/40 font-normal p-2 text-xs">Experiment</th>
                        <th class="text-left text-white/40 font-normal p-2 text-xs">Model</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Best Acc</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Avg Acc</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Best Loss</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">95% CI</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Runs</th>
                        <th class="text-center text-white/40 font-normal p-2 text-xs">Gap</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ranking as $idx => $s)
                    @php $exp = $expMap[$s['id']]; $color = $colors[$idx % count($colors)]; @endphp
                    <tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
                        <td class="p-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white"
                                  style="background:{{ $color }}55;">
                                #{{ $s['rank'] }}
                            </span>
                        </td>
                        <td class="p-2 text-white/80 font-mono text-xs">
                            <a href="{{ route('ai.experiments.show', $s['id']) }}"
                               class="hover:text-white transition-colors">#{{ $s['id'] }}</a>
                        </td>
                        <td class="p-2 text-white/60 text-xs">{{ $exp->model->name }}</td>
                        <td class="p-2 text-center font-bold text-sm" style="color:{{ $color }};">
                            {{ round($s['best_accuracy'] * 100, 2) }}%
                        </td>
                        <td class="p-2 text-center text-white/60 text-xs">
                            {{ round($s['avg_accuracy'] * 100, 2) }}%
                        </td>
                        <td class="p-2 text-center text-white/60 text-xs">
                            {{ round($s['best_loss'], 4) }}
                        </td>
                        <td class="p-2 text-center text-white/50 font-mono text-xs">
                            [{{ round($s['accuracy_ci']['lower'] * 100, 1) }},
                            {{ round($s['accuracy_ci']['upper'] * 100, 1) }}]%
                        </td>
                        <td class="p-2 text-center text-white/60 text-xs">{{ $s['runs_count'] }}</td>
                        <td class="p-2 text-center text-xs">
                            @if($s['gap'] == 0)
                                <span class="text-yellow-300 font-semibold">Leader</span>
                            @else
                                <span class="text-white/30">-{{ round($s['gap'] * 100, 2) }}%</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Promote Winner ──────────────────────────────────────────────────── --}}
        @if($winnerId && $expMap->has($winnerId))
        <div class="flex gap-3">
            <form method="POST" action="{{ route('ai.experiments.promote', $winnerId) }}">
                @csrf
                <button type="submit"
                        class="px-6 py-3 bg-[#0D00A4] hover:bg-[#1500c9] rounded-xl font-bold text-white transition-all">
                    Promote Winner (Exp #{{ $winnerId }})
                </button>
            </form>
        </div>
        @endif

        @endif {{-- end comparison results --}}
    </div>
</div>

@if($comparison !== null)
<script>
(function () {
    const colors  = @json($colors);
    const ranking = @json($comparison['ranking']);
    const labels  = ranking.map(r => 'Exp #' + r.id);
    const bgAlpha = '99';

    // Accuracy chart
    const accCtx = document.getElementById('accuracyChart').getContext('2d');
    new Chart(accCtx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Best Accuracy',
                    data: ranking.map(r => +(r.best_accuracy * 100).toFixed(2)),
                    backgroundColor: ranking.map((_, i) => colors[i % colors.length] + bgAlpha),
                    borderColor:     ranking.map((_, i) => colors[i % colors.length]),
                    borderWidth: 1,
                    borderRadius: 6,
                },
                {
                    label: 'Avg Accuracy',
                    data: ranking.map(r => +(r.avg_accuracy * 100).toFixed(2)),
                    backgroundColor: ranking.map((_, i) => colors[i % colors.length] + '33'),
                    borderColor:     ranking.map((_, i) => colors[i % colors.length]),
                    borderWidth: 1,
                    borderRadius: 6,
                    borderDash: [4, 4],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { labels: { color: 'rgba(255,255,255,0.6)', boxWidth: 12 } },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + '%' } },
            },
            scales: {
                y: { beginAtZero: false, min: 50, max: 100,
                     ticks: { color: 'rgba(255,255,255,0.5)', callback: v => v + '%' },
                     grid:  { color: 'rgba(255,255,255,0.05)' } },
                x: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { display: false } },
            },
        },
    });

    // Loss chart
    const lossCtx = document.getElementById('lossChart').getContext('2d');
    new Chart(lossCtx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Best Loss',
                data: ranking.map(r => +r.best_loss.toFixed(6)),
                backgroundColor: ranking.map((_, i) => colors[i % colors.length] + bgAlpha),
                borderColor:     ranking.map((_, i) => colors[i % colors.length]),
                borderWidth: 1,
                borderRadius: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { labels: { color: 'rgba(255,255,255,0.6)', boxWidth: 12 } },
            },
            scales: {
                y: { beginAtZero: true,
                     ticks: { color: 'rgba(255,255,255,0.5)' },
                     grid:  { color: 'rgba(255,255,255,0.05)' } },
                x: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { display: false } },
            },
        },
    });
})();
</script>
@endif
@endsection
