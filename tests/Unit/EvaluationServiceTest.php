<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\EvaluationService;
use App\Models\TrainingRun;
use App\Models\Experiment;
use App\Models\AIModel;
use App\Models\Dataset;

class EvaluationServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_evaluate_creates_metrics_and_completes_run()
    {
        $model = AIModel::factory()->create(['name' => 't-model']);
        $dataset = Dataset::factory()->create(['name' => 't-data']);
        $experiment = Experiment::create(['ai_model_id' => $model->id, 'dataset_id' => $dataset->id, 'status' => 'created']);

        $run = TrainingRun::create(['experiment_id' => $experiment->id, 'parameters' => ['seed' => 42], 'status' => 'queued']);

        $eval = app(EvaluationService::class);
        $metrics = $eval->evaluate($run);

        $this->assertIsArray($metrics);
        $this->assertNotEmpty($metrics);

        $run->refresh();
        $this->assertEquals('completed', $run->status);
        $this->assertDatabaseHas('metrics', ['training_run_id' => $run->id, 'name' => 'accuracy']);
    }
}
