<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiVersion extends Model
{
    protected $table = 'api_versions';

    protected $fillable = ['api_id', 'version', 'release_date', 'status'];

    protected $casts = ['release_date' => 'date'];

    public function api()
    {
        return $this->belongsTo(Api::class);
    }
}
