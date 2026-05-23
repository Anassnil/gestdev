<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AIModel;

class AIModelFactory extends Factory
{
    protected $model = AIModel::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' model',
            'type' => 'test',
            'status' => 'created',
        ];
    }
}
