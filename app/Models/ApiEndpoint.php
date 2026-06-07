<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiEndpoint extends Model
{
    protected $table = 'api_endpoints';

    protected $fillable = [
        'api_id', 'collection_id', 'name', 'path', 'method', 'description', 'version', 'status',
    ];

    public function api()
    {
        return $this->belongsTo(Api::class);
    }

    public function collection()
    {
        return $this->belongsTo(ApiCollection::class, 'collection_id');
    }
}
