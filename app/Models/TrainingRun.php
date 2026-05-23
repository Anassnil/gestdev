<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingRun extends Model
{
    use HasFactory;

    protected $table = 'training_runs';

    protected $casts = [
        'parameters' => 'array',
    ];

    protected $fillable = ['experiment_id', 'parameters', 'status', 'started_at', 'ended_at'];

    public function experiment()
    {
        return $this->belongsTo(Experiment::class);
    }

    public function metrics()
    {
        return $this->hasMany(Metric::class);
    }
}
