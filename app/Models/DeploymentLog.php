<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeploymentLog extends Model
{
    use HasFactory;

    protected $table = 'deployment_logs';

    protected $fillable = ['deployment_id', 'message', 'level'];

    public function deployment()
    {
        return $this->belongsTo(Deployment::class);
    }
}
