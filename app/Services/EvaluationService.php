<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\TrainingRun;
use App\Models\Metric;

class EvaluationService
{
    /**
     * Evaluate a training run: compute/store metrics and update run status.
     * This is a scaffold: replace with integration to real evaluation pipeline.
     */
    public function evaluate(TrainingRun $run): array
    {
        // If metrics already exist for this run, return them
        $existing = $run->metrics()->get();
        if ($existing->count()) {
            return $existing->map(function ($m) {
                return ['name' => $m->name, 'value' => $m->value];
            })->toArray();
        }

        // Simple deterministic pseudo-evaluation based on parameters
        $params = $run->parameters ?? [];
        $seed = isset($params['seed']) ? intval($params['seed']) : $run->id;
        // generate reproducible pseudo-metrics
        $accuracy = ($this->pseudoRand($seed) * 0.2) + 0.7; // 0.7 - 0.9
        $loss = (1 - $accuracy) * 0.5;

        $metrics = [
            ['name' => 'accuracy', 'value' => round($accuracy, 4)],
            ['name' => 'loss', 'value' => round($loss, 6)],
        ];

        foreach ($metrics as $m) {
            Metric::create(['training_run_id' => $run->id, 'name' => $m['name'], 'value' => $m['value']]);
        }

        // mark run completed
        $run->update(['status' => 'completed', 'ended_at' => now()]);

        return $metrics;
    }

    /**
     * Compare two experiments by their best training run accuracy (with statistical tests).
     */
    public function compare(Experiment $a, Experiment $b): array
    {
        $best = function (Experiment $e) {
            $bestRun = null;
            $bestAcc = -INF;
            foreach ($e->trainingRuns as $r) {
                $accMetric = $r->metrics()->where('name', 'accuracy')->first();
                if ($accMetric && $accMetric->value > $bestAcc) {
                    $bestAcc = $accMetric->value;
                    $bestRun = $r;
                }
            }
            return ['run' => $bestRun, 'accuracy' => $bestAcc];
        };

        $aBest = $best($a);
        $bBest = $best($b);

        $better = null;
        if ($aBest['accuracy'] > $bBest['accuracy']) $better = 'a';
        elseif ($bBest['accuracy'] > $aBest['accuracy']) $better = 'b';

        // Statistical analysis
        $aSamples = $this->getAccuracySamples($a);
        $bSamples = $this->getAccuracySamples($b);
        $ttest    = $this->welchTTest($aSamples, $bSamples);
        $d        = $this->cohenD($aSamples, $bSamples);
        $aCI      = $this->confidenceInterval($aSamples);
        $bCI      = $this->confidenceInterval($bSamples);

        return [
            'better'       => $better,
            'a'            => $aBest,
            'b'            => $bBest,
            'ttest'        => $ttest,
            'cohen_d'      => round($d, 4),
            'effect_label' => $this->effectLabel($d),
            'a_ci'         => $aCI,
            'b_ci'         => $bCI,
        ];
    }

    // ─── Sample helpers ────────────────────────────────────────────────────────

    public function getAccuracySamples(Experiment $experiment): array
    {
        return $experiment->trainingRuns
            ->map(fn($run) => $run->metrics->firstWhere('name', 'accuracy')?->value)
            ->filter(fn($v) => $v !== null)
            ->values()
            ->toArray();
    }

    public function getLossSamples(Experiment $experiment): array
    {
        return $experiment->trainingRuns
            ->map(fn($run) => $run->metrics->firstWhere('name', 'loss')?->value)
            ->filter(fn($v) => $v !== null)
            ->values()
            ->toArray();
    }

    // ─── Descriptive statistics ────────────────────────────────────────────────

    private function arrayMean(array $samples): float
    {
        if (empty($samples)) return 0.0;
        return array_sum($samples) / count($samples);
    }

    private function arrayVariance(array $samples): float
    {
        $n = count($samples);
        if ($n < 2) return 0.0;
        $mean = $this->arrayMean($samples);
        return array_sum(array_map(fn($x) => ($x - $mean) ** 2, $samples)) / ($n - 1);
    }

    private function arrayStdDev(array $samples): float
    {
        return sqrt($this->arrayVariance($samples));
    }

    public function confidenceInterval(array $samples, float $z = 1.96): array
    {
        $n    = count($samples);
        $mean = $n > 0 ? $this->arrayMean($samples) : 0.0;
        $se   = $n > 1 ? $this->arrayStdDev($samples) / sqrt($n) : 0.0;
        return [
            'lower' => round($mean - $z * $se, 6),
            'upper' => round($mean + $z * $se, 6),
            'mean'  => round($mean, 6),
        ];
    }

    // ─── Inferential statistics ────────────────────────────────────────────────

    /**
     * Welch's two-sample t-test (unequal variances).
     * Returns t-statistic, degrees of freedom, two-tailed p-value, and significance flag.
     */
    public function welchTTest(array $a, array $b): array
    {
        $n1 = count($a);
        $n2 = count($b);

        if ($n1 < 2 || $n2 < 2) {
            return ['t' => 0.0, 'df' => 0.0, 'p_value' => 1.0, 'significant' => false];
        }

        $mean1 = $this->arrayMean($a);
        $mean2 = $this->arrayMean($b);
        $var1  = $this->arrayVariance($a);
        $var2  = $this->arrayVariance($b);

        $se = sqrt($var1 / $n1 + $var2 / $n2);
        if ($se < 1e-10) {
            return ['t' => 0.0, 'df' => 0.0, 'p_value' => 1.0, 'significant' => false];
        }

        $t = ($mean1 - $mean2) / $se;

        // Welch–Satterthwaite degrees of freedom
        $seSq = $var1 / $n1 + $var2 / $n2;
        $df   = $seSq ** 2 / (
            ($var1 / $n1) ** 2 / ($n1 - 1) + ($var2 / $n2) ** 2 / ($n2 - 1)
        );

        $p = $this->tTwoTailP(abs($t), $df);

        return [
            't'           => round($t, 4),
            'df'          => round($df, 2),
            'p_value'     => round($p, 4),
            'significant' => $p < 0.05,
        ];
    }

    /**
     * Cohen's d effect size.
     */
    public function cohenD(array $a, array $b): float
    {
        $n1 = count($a);
        $n2 = count($b);
        if ($n1 < 1 || $n2 < 1) return 0.0;

        $mean1 = $this->arrayMean($a);
        $mean2 = $this->arrayMean($b);
        $var1  = $n1 > 1 ? $this->arrayVariance($a) : 0.0;
        $var2  = $n2 > 1 ? $this->arrayVariance($b) : 0.0;

        $pooledVar = max(0, (($n1 - 1) * $var1 + ($n2 - 1) * $var2) / max(1, $n1 + $n2 - 2));
        $pooledStd = sqrt($pooledVar);

        if ($pooledStd < 1e-10) return 0.0;
        return ($mean1 - $mean2) / $pooledStd;
    }

    private function effectLabel(float $d): string
    {
        $abs = abs($d);
        if ($abs < 0.2) return 'negligible';
        if ($abs < 0.5) return 'small';
        if ($abs < 0.8) return 'medium';
        return 'large';
    }

    // ─── Multi-experiment comparison ───────────────────────────────────────────

    /**
     * Compare multiple experiments with pairwise statistical tests and ranking.
     */
    public function multiCompare(array $experiments): array
    {
        // Per-experiment stats
        $stats = [];
        foreach ($experiments as $exp) {
            $accSamples  = $this->getAccuracySamples($exp);
            $lossSamples = $this->getLossSamples($exp);
            $ci          = $this->confidenceInterval($accSamples);

            $stats[$exp->id] = [
                'id'               => $exp->id,
                'accuracy_samples' => $accSamples,
                'loss_samples'     => $lossSamples,
                'best_accuracy'    => count($accSamples) > 0 ? max($accSamples) : 0.0,
                'avg_accuracy'     => $ci['mean'],
                'best_loss'        => count($lossSamples) > 0 ? min($lossSamples) : 0.0,
                'avg_loss'         => count($lossSamples) > 0 ? $this->arrayMean($lossSamples) : 0.0,
                'accuracy_std'     => count($accSamples) > 1 ? $this->arrayStdDev($accSamples) : 0.0,
                'accuracy_ci'      => $ci,
                'runs_count'       => count($accSamples),
            ];
        }

        // Pairwise comparisons
        $pairwise = [];
        $count = count($experiments);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $ea = $experiments[$i];
                $eb = $experiments[$j];
                $sa = $stats[$ea->id];
                $sb = $stats[$eb->id];

                $ttest  = $this->welchTTest($sa['accuracy_samples'], $sb['accuracy_samples']);
                $d      = $this->cohenD($sa['accuracy_samples'], $sb['accuracy_samples']);
                $winner = match (true) {
                    $sa['best_accuracy'] > $sb['best_accuracy'] => 'a',
                    $sb['best_accuracy'] > $sa['best_accuracy'] => 'b',
                    default                                      => 'tie',
                };

                $pairwise["{$ea->id}-{$eb->id}"] = [
                    'exp_a'        => $ea->id,
                    'exp_b'        => $eb->id,
                    't_stat'       => $ttest['t'],
                    'df'           => $ttest['df'],
                    'p_value'      => $ttest['p_value'],
                    'significant'  => $ttest['significant'],
                    'cohen_d'      => round($d, 4),
                    'effect_label' => $this->effectLabel($d),
                    'winner'       => $winner,
                ];
            }
        }

        // Ranking by best accuracy
        $topAccuracy = collect($stats)->max('best_accuracy');
        $ranking = collect($stats)
            ->sortByDesc('best_accuracy')
            ->values()
            ->map(function ($s, $idx) use ($topAccuracy) {
                return array_merge($s, [
                    'rank'    => $idx + 1,
                    'gap'     => round($topAccuracy - $s['best_accuracy'], 6),
                    'gap_pct' => $topAccuracy > 0
                        ? round(($topAccuracy - $s['best_accuracy']) / $topAccuracy * 100, 2)
                        : 0.0,
                ]);
            })
            ->toArray();

        $winner = $ranking[0]['id'] ?? null;

        return compact('stats', 'pairwise', 'ranking', 'winner');
    }

    // ─── Numerical methods ─────────────────────────────────────────────────────

    /** Two-tailed p-value using t-distribution CDF via regularized incomplete beta. */
    private function tTwoTailP(float $t, float $df): float
    {
        if ($df <= 0) return 1.0;
        $x = $df / ($df + $t * $t);
        return $this->regularizedIncompleteBeta($x, $df / 2.0, 0.5);
    }

    /**
     * Regularized incomplete beta function I(x; a, b) via Lentz's continued fraction.
     * Reference: Numerical Recipes, §6.4.
     */
    private function regularizedIncompleteBeta(float $x, float $a, float $b): float
    {
        if ($x <= 0.0) return 0.0;
        if ($x >= 1.0) return 1.0;

        // Use symmetry relation for numerical stability
        if ($x > ($a + 1.0) / ($a + $b + 2.0)) {
            return 1.0 - $this->regularizedIncompleteBeta(1.0 - $x, $b, $a);
        }

        $lbetaAB = $this->logGamma($a + $b) - $this->logGamma($a) - $this->logGamma($b);
        $front   = exp(log($x) * $a + log(1.0 - $x) * $b - $lbetaAB) / $a;

        // Lentz's modified continued fraction
        $qab = $a + $b;
        $qap = $a + 1.0;
        $qam = $a - 1.0;
        $c   = 1.0;
        $d   = 1.0 - $qab * $x / $qap;
        if (abs($d) < 1e-30) $d = 1e-30;
        $d = 1.0 / $d;
        $h = $d;

        for ($m = 1; $m <= 200; $m++) {
            $m2 = 2 * $m;
            // Even step
            $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
            $d  = 1.0 + $aa * $d; if (abs($d) < 1e-30) $d = 1e-30;
            $c  = 1.0 + $aa / $c; if (abs($c) < 1e-30) $c = 1e-30;
            $d  = 1.0 / $d;
            $h *= $d * $c;
            // Odd step
            $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
            $d  = 1.0 + $aa * $d; if (abs($d) < 1e-30) $d = 1e-30;
            $c  = 1.0 + $aa / $c; if (abs($c) < 1e-30) $c = 1e-30;
            $d  = 1.0 / $d;
            $del = $d * $c;
            $h  *= $del;
            if (abs($del - 1.0) < 1e-10) break;
        }

        return $front * $h;
    }

    /** Log-gamma using Lanczos approximation (g=7). */
    private function logGamma(float $x): float
    {
        if ($x < 0.5) {
            return log(M_PI / sin(M_PI * $x)) - $this->logGamma(1.0 - $x);
        }
        $x  -= 1.0;
        $g   = 7;
        $p   = [0.99999999999980993, 676.5203681218851, -1259.1392167224028,
                771.32342877765313, -176.61502916214059, 12.507343278686905,
                -0.13857109526572012, 9.9843695780195716e-6, 1.5056327351493116e-7];
        $ag  = $p[0];
        for ($i = 1; $i < $g + 2; $i++) {
            $ag += $p[$i] / ($x + $i);
        }
        $t = $x + $g + 0.5;
        return 0.5 * log(2.0 * M_PI) + ($x + 0.5) * log($t) - $t + log($ag);
    }

    private function pseudoRand(int $seed): float
    {
        // simple LCG for reproducible pseudo-random in [0,1)
        $m = 2147483647; $a = 16807; $x = $seed % $m;
        $x = ($a * $x) % $m;
        return $x / $m;
    }
}
