<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Dataset;

class DatasetFactory extends Factory
{
    protected $model = Dataset::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' dataset',
            'type' => 'test',
            'path' => '/tmp/data.csv',
        ];
    }
}
