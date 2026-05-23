<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'visibility',
        'default_branch_id',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function defaultBranch()
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }

    public function collaborators()
    {
        return $this->hasMany(RepositoryCollaborator::class);
    }
}
