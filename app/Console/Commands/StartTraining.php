<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TrainingRun;
use App\Services\EvaluationService;

class StartTraining extends Command
{
    protected $signature = 'ai:start-training {runId}';
    protected $description = 'Start a training run (integration point).';

    public function handle()
    {
        $runId = $this->argument('runId');
        $run = TrainingRun::find($runId);
        if (! $run) {
            $this->error('Run not found.');
            return 1;
        }
        $run->update(['status' => 'running', 'started_at' => now()]);
        $this->info('Training started for run '.$runId);

        // NOTE: this command simulates a training workflow and then evaluates the run.
        // In production replace with an actual training job that calls back when finished.
        try {
            // call evaluation service to compute and store metrics
            $eval = app(EvaluationService::class);
            $metrics = $eval->evaluate($run);
            $this->info('Evaluation complete. Metrics: '.json_encode($metrics));
        } catch (\Exception $e) {
            $run->update(['status' => 'failed', 'ended_at' => now()]);
            $this->error('Evaluation failed: '.$e->getMessage());
            return 1;
        }

        $this->info('Training finished for run '.$runId);
        return 0;
    }
}
