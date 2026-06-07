<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiCollection extends Model
{
    protected $table = 'api_collections';

    protected $fillable = ['api_id', 'name', 'description'];

    public function api()
    {
        return $this->belongsTo(Api::class);
    }

    public function endpoints()
    {
        return $this->hasMany(ApiEndpoint::class, 'collection_id');
    }
}
