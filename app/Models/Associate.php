<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Associate extends Model
{
    protected $fillable = [
        'user_id',
        'associate_user_id',
        'relationship_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function associateUser()
    {
        return $this->belongsTo(User::class, 'associate_user_id');
    }
}
