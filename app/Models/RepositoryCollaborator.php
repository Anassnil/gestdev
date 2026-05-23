<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepositoryCollaborator extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'user_id',
        'role',
        'invited_by',
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
