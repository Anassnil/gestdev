<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiEnvironment extends Model
{
    protected $table = 'api_environments';

    protected $fillable = ['api_id', 'name', 'base_url'];

    public function api()
    {
        return $this->belongsTo(Api::class);
    }
}
