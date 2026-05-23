<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TrainingRun;

class TrainingRunFactory extends Factory
{
    protected $model = TrainingRun::class;

    public function definition()
    {
        return [
            'parameters' => ['seed' => rand(1,1000)],
            'status' => 'queued',
        ];
    }
}
