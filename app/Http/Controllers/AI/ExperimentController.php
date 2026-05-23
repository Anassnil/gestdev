<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Experiment;
use App\Models\AIModel;
use App\Models\Dataset;
use Illuminate\Http\Request;

class ExperimentController extends Controller
{
    public function index()
    {
        $experiments = Experiment::with('model', 'dataset')
            ->whereHas('model', fn($q) => $q->where('user_id', auth()->id()))
            ->get();
        return view('ai_experiments.index', ['experiments' => $experiments]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['ai_model_id' => 'required|exists:ai_models,id', 'dataset_id' => 'required|exists:datasets,id']);

        // Verify the model belongs to the authenticated user.
        if (AIModel::where('id', $data['ai_model_id'])->where('user_id', auth()->id())->doesntExist()) {
            abort(403);
        }

        $exp = Experiment::create($data + ['status' => 'created']);
        return redirect()->back()->with('success', 'Experiment created.');
    }

    public function show(Experiment $experiment)
    {
        $experiment->load('trainingRuns.metrics', 'model', 'dataset');
        
        // Prepare metrics data for visualization
        $trainingData = $experiment->trainingRuns->map(function ($run) {
            $metrics = $run->metrics->keyBy('name');
            return [
                'id' => $run->id,
                'accuracy' => $metrics->get('accuracy')?->value ?? 0,
                'loss' => $metrics->get('loss')?->value ?? 0,
                'created_at' => $run->created_at,
                'status' => $run->status,
            ];
        })->values();

        $accuracies = $trainingData->pluck('accuracy')->toArray();
        $losses = $trainingData->pluck('loss')->toArray();

        return view('ai_experiments.show', [
            'experiment' => $experiment,
            'trainingData' => $trainingData,
            'accuracies' => json_encode($accuracies),
            'losses' => json_encode($losses),
            'bestAccuracy' => count($accuracies) > 0 ? max($accuracies) : 0,
            'avgAccuracy' => count($accuracies) > 0 ? array_sum($accuracies) / count($accuracies) : 0,
        ]);
    }

    public function compare(Request $request, Experiment $experiment)
    {
        // compare this experiment to another experiment by id
        $otherId = $request->get('other_experiment_id');
        $other = Experiment::find($otherId);
        if (! $other) return redirect()->back()->with('error', 'Other experiment not found.');

        $experiment->load('trainingRuns.metrics');
        $other->load('trainingRuns.metrics');

        // Get best metrics for each
        $getMetrics = function ($exp) {
            $accuracies = [];
            $losses = [];
            foreach ($exp->trainingRuns as $run) {
                foreach ($run->metrics as $metric) {
                    if ($metric->name === 'accuracy') $accuracies[] = $metric->value;
                    if ($metric->name === 'loss') $losses[] = $metric->value;
                }
            }
            return [
                'best_accuracy' => count($accuracies) > 0 ? max($accuracies) : 0,
                'avg_accuracy' => count($accuracies) > 0 ? array_sum($accuracies) / count($accuracies) : 0,
                'best_loss' => count($losses) > 0 ? min($losses) : 0,
                'avg_loss' => count($losses) > 0 ? array_sum($losses) / count($losses) : 0,
                'runs_count' => count($exp->trainingRuns),
            ];
        };

        $eval = app(\App\Services\EvaluationService::class);
        $report = $eval->compare($experiment, $other);

        return view('ai_experiments.compare', [
            'a' => $experiment,
            'b' => $other,
            'a_metrics' => $getMetrics($experiment),
            'b_metrics' => $getMetrics($other),
            'report' => $report,
            'better' => $report['better'] === 'a' ? 'A' : ($report['better'] === 'b' ? 'B' : 'None'),
        ]);
    }

    public function multiCompare(Request $request)
    {
        $ids = $request->query('ids', []);

        $allExperiments = Experiment::with('model', 'dataset')
            ->whereHas('model', fn($q) => $q->where('user_id', auth()->id()))
            ->get();

        if (empty($ids) || count($ids) < 2) {
            return view('ai_experiments.multi_compare', [
                'allExperiments' => $allExperiments,
                'experiments'    => collect(),
                'comparison'     => null,
            ]);
        }

        $experiments = Experiment::with('trainingRuns.metrics', 'model', 'dataset')
            ->whereIn('id', $ids)
            ->whereHas('model', fn($q) => $q->where('user_id', auth()->id()))
            ->get();

        if ($experiments->count() < 2) {
            return redirect()->route('ai.experiments.multi_compare')
                ->with('error', 'Select at least 2 experiments to compare.');
        }

        $eval = app(\App\Services\EvaluationService::class);
        $comparison = $eval->multiCompare($experiments->all());

        return view('ai_experiments.multi_compare', [
            'allExperiments' => $allExperiments,
            'experiments'    => $experiments,
            'comparison'     => $comparison,
        ]);
    }

    public function promoteBest(Experiment $experiment)
    {
        // choose the best training run by accuracy and promote it as a model version
        $bestRun = null; $bestAcc = -INF;
        foreach ($experiment->trainingRuns as $r) {
            $acc = $r->metrics()->where('name', 'accuracy')->value('value');
            if ($acc !== null && $acc > $bestAcc) {
                $bestAcc = $acc; $bestRun = $r;
            }
        }
        if (! $bestRun) {
            return redirect()->back()->with('error', 'No evaluated runs to promote.');
        }

        // create a new model version from the run parameters
        $registry = app(\App\Services\ModelRegistry::class);
        $model = $experiment->model;
        $version = $registry->addVersion($model, $bestRun->parameters ?? [], 'v'.time());
        $version->update(['status' => 'promoted']);

        // optional: create a staging deployment
        $dep = \App\Models\Deployment::create(['model_version_id' => $version->id, 'environment' => 'staging', 'status' => 'active']);
        \App\Models\DeploymentLog::create(['deployment_id' => $dep->id, 'message' => 'Auto-promoted from experiment '.$experiment->id, 'level' => 'info']);

        return redirect()->back()->with('success', 'Promoted best run as version '.$version->id);
    }
}
