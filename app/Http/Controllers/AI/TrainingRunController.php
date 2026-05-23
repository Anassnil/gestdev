<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\TrainingRun;
use App\Models\Experiment;
use Illuminate\Http\Request;

class TrainingRunController extends Controller
{
    public function index()
    {
        $pagination = TrainingRun::with('experiment.model', 'metrics')
            ->whereHas('experiment.model', fn($q) => $q->where('user_id', auth()->id()))
            ->latest()
            ->paginate(20);

        // Map the items while preserving pagination
        $runs = $pagination->map(function ($run) {
            $accuracy = $run->metrics()->where('name', 'accuracy')->latest()->first()?->value ?? 0;
            return [
                'run' => $run,
                'accuracy' => $accuracy,
                'progress' => $this->calculateProgress($run),
            ];
        });

        // Create a custom paginated collection
        $pagination->setCollection($runs);

        return view('ai_training_runs.index', ['runs' => $pagination]);
    }

    public function show(TrainingRun $run)
    {
        $run->load('experiment.model', 'metrics');
        
        $accuracies = $run->metrics()->where('name', 'accuracy')->pluck('value')->toArray();
        $losses = $run->metrics()->where('name', 'loss')->pluck('value')->toArray();
        
        return view('ai_training_runs.show', [
            'run' => $run,
            'accuracies' => json_encode($accuracies),
            'losses' => json_encode($losses),
            'currentAccuracy' => end($accuracies) ?: 0,
            'progress' => $this->calculateProgress($run),
        ]);
    }

    public function create(Request $request)
    {
        $experimentId = $request->get('experiment_id');
        $experiment = Experiment::find($experimentId);
        
        if (!$experiment) {
            return redirect()->back()->with('error', 'Experiment not found.');
        }

        return view('ai_training_runs.create', ['experiment' => $experiment]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'experiment_id' => 'required|exists:experiments,id',
            'parameters' => 'nullable|json',
            'epochs' => 'nullable|integer|min:1|max:1000',
            'batch_size' => 'nullable|integer|min:1',
            'learning_rate' => 'nullable|numeric|min:0.00001',
        ]);

        $experiment = Experiment::find($request->experiment_id);
        
        $parameters = [
            'epochs' => $request->epochs ?? 10,
            'batch_size' => $request->batch_size ?? 32,
            'learning_rate' => $request->learning_rate ?? 0.001,
        ];

        if ($request->parameters) {
            $parameters = array_merge($parameters, json_decode($request->parameters, true));
        }

        $run = TrainingRun::create([
            'experiment_id' => $request->experiment_id,
            'parameters' => $parameters,
            'status' => 'queued',
            'started_at' => null,
        ]);

        // Queue training job (will be picked up by Laravel job queue)
        // For now, mark as running
        $run->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        return redirect()->route('ai.training_runs.show', $run)
            ->with('success', 'Training job queued! Monitoring progress...');
    }

    public function cancel(TrainingRun $run)
    {
        if (in_array($run->status, ['completed', 'failed', 'cancelled'])) {
            return redirect()->back()->with('error', 'Cannot cancel a ' . $run->status . ' run.');
        }

        $run->update(['status' => 'cancelled', 'ended_at' => now()]);

        return redirect()->back()->with('success', 'Training job cancelled.');
    }

    public function progress(TrainingRun $run)
    {
        return response()->json([
            'status' => $run->status,
            'progress' => $this->calculateProgress($run),
            'accuracy' => $run->metrics()->where('name', 'accuracy')->latest()->first()?->value ?? 0,
            'loss' => $run->metrics()->where('name', 'loss')->latest()->first()?->value ?? 0,
        ]);
    }

    private function calculateProgress(TrainingRun $run)
    {
        if ($run->status === 'queued') return 0;
        if ($run->status === 'completed') return 100;
        if ($run->status === 'failed' || $run->status === 'cancelled') return 0;
        
        // Calculate progress based on elapsed time
        if ($run->started_at) {
            $duration = $run->ended_at 
                ? $run->ended_at->diffInSeconds($run->started_at)
                : now()->diffInSeconds($run->started_at);
            
            $estimatedDuration = 300; // 5 minutes estimate
            $progress = min(99, ($duration / $estimatedDuration) * 100);
            return (int)$progress;
        }

        return 0;
    }
}
