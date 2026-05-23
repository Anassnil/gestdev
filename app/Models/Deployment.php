<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    use HasFactory;

    protected $table = 'deployments';

    protected $fillable = ['model_version_id', 'environment', 'status', 'endpoint_url'];

    public function version()
    {
        return $this->belongsTo(ModelVersion::class, 'model_version_id');
    }

    public function logs()
    {
        return $this->hasMany(\App\Models\DeploymentLog::class, 'deployment_id');
    }
}
