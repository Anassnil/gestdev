<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    protected $fillable = ['name', 'type', 'status', 'description', 'user_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function versions()
    {
        return $this->hasMany(ModelVersion::class, 'ai_model_id');
    }

    public function experiments()
    {
        return $this->hasMany(Experiment::class, 'ai_model_id');
    }
}
