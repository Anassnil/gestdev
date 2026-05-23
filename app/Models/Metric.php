<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    use HasFactory;

    protected $table = 'metrics';

    protected $fillable = ['training_run_id', 'name', 'value'];

    public function trainingRun()
    {
        return $this->belongsTo(TrainingRun::class);
    }
}
