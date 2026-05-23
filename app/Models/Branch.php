<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'name',
        'head_commit_hash',
        'is_default',
        'is_protected',
        'protection_rules',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_protected' => 'boolean',
        'protection_rules' => 'array',
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
