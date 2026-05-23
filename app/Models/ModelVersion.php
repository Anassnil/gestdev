<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelVersion extends Model
{
    use HasFactory;

    protected $table = 'model_versions';

    protected $casts = [
        'config' => 'array',
    ];

    protected $fillable = ['ai_model_id', 'version', 'config', 'status'];

    public function model()
    {
        return $this->belongsTo(AIModel::class, 'ai_model_id');
    }

    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }
}
