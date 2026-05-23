<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AIModel;
use App\Models\Dataset;
use App\Models\Metric;
use Illuminate\Http\Request;

class AIModelController extends Controller
{
    public function index()
    {
        $models = AIModel::with('versions', 'experiments')
            ->where('user_id', auth()->id())
            ->get()
            ->map(function ($model) {
                // Aggregate performance metrics for each model
                $metrics = Metric::whereIn('training_run_id', 
                    $model->experiments()
                        ->with('trainingRuns')
                        ->get()
                        ->pluck('trainingRuns')
                        ->flatten()
                        ->pluck('id')
                )->get();

                $accuracies = $metrics->where('name', 'accuracy')->pluck('value')->toArray();

                return [
                    'model' => $model,
                    'best_accuracy' => count($accuracies) > 0 ? max($accuracies) : 0,
                    'avg_accuracy' => count($accuracies) > 0 ? array_sum($accuracies) / count($accuracies) : 0,
                    'experiments_count' => $model->experiments()->count(),
                    'versions_count' => $model->versions()->count(),
                    'latest_experiment' => $model->experiments()->latest()->first(),
                ];
            });

        return view('ai_models.index', ['models' => $models]);
    }

    public function show(AIModel $model)
    {
        $model->load('versions', 'experiments.trainingRuns.metrics');
        $availableDatasets = Dataset::query()->orderBy('name')->get(['id', 'name']);
        $latestExperiment = $model->experiments->sortByDesc('created_at')->first();
        
        // Collect all metrics for performance history
        $allMetrics = [];
        $experimentData = [];
        
        foreach ($model->experiments as $exp) {
            $expAccuracies = [];
            foreach ($exp->trainingRuns as $run) {
                foreach ($run->metrics as $metric) {
                    $allMetrics[] = $metric->toArray();
                    if ($metric->name === 'accuracy') {
                        $expAccuracies[] = $metric->value;
                    }
                }
            }
            if (count($expAccuracies) > 0) {
                $experimentData[] = [
                    'name' => 'Exp #' . $exp->id,
                    'best' => max($expAccuracies),
                    'avg' => array_sum($expAccuracies) / count($expAccuracies),
                ];
            }
        }

        $accuracyHistory = collect($allMetrics)
            ->where('name', 'accuracy')
            ->map(fn($m) => (float)$m['value'])
            ->values()
            ->toArray();

        $lossHistory = collect($allMetrics)
            ->where('name', 'loss')
            ->map(fn($m) => (float)$m['value'])
            ->values()
            ->toArray();

        return view('ai_models.show', [
            'model' => $model,
            'accuracyHistory' => json_encode($accuracyHistory),
            'lossHistory' => json_encode($lossHistory),
            'experimentData' => json_encode($experimentData),
            'bestAccuracy' => count($accuracyHistory) > 0 ? round(max($accuracyHistory), 4) : 0,
            'avgAccuracy' => count($accuracyHistory) > 0 ? round(array_sum($accuracyHistory) / count($accuracyHistory), 4) : 0,
            'availableDatasets' => $availableDatasets,
            'latestExperiment' => $latestExperiment,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string', 'type' => 'nullable|string', 'description' => 'nullable|string']);
        $data['user_id'] = auth()->id();
        $model = AIModel::create($data);
        return redirect()->route('ai.models.index')->with('success', 'Model created.');
    }

    public function update(Request $request, AIModel $model)
    {
        $data = $request->validate(['name' => 'sometimes|string', 'type' => 'nullable|string', 'status' => 'nullable|string', 'description' => 'nullable|string']);
        $model->update($data);
        return redirect()->back()->with('success', 'Model updated.');
    }

    public function destroy(AIModel $model)
    {
        $model->delete();
        return redirect()->route('ai.models.index')->with('success', 'Model deleted.');
    }
}
