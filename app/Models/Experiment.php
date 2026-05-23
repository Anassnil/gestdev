<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experiment extends Model
{
    use HasFactory;

    protected $table = 'experiments';

    protected $fillable = ['ai_model_id', 'dataset_id', 'status', 'started_at', 'ended_at'];

    public function model()
    {
        return $this->belongsTo(AIModel::class, 'ai_model_id');
    }

    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }

    public function trainingRuns()
    {
        return $this->hasMany(TrainingRun::class);
    }
}
