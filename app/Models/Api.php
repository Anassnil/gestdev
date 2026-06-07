<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Api extends Model
{
    protected $table = 'apis';

    protected $fillable = [
        'user_id', 'name', 'slug', 'description', 'base_url', 'version', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collections()
    {
        return $this->hasMany(ApiCollection::class);
    }

    public function environments()
    {
        return $this->hasMany(ApiEnvironment::class);
    }

    public function endpoints()
    {
        return $this->hasMany(ApiEndpoint::class);
    }

    public function versions()
    {
        return $this->hasMany(ApiVersion::class);
    }
}
