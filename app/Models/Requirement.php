<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id', 'parent_id', 'title', 'description', 'type', 'priority', 'status',
        'tags', 'linked_feature_id', 'linked_feature_type', 'position', 'created_by', 'version',
        'estimate', 'acceptance_criteria'
    ];

    protected $casts = [
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function parent()
    {
        return $this->belongsTo(Requirement::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Requirement::class, 'parent_id');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'requirement_task');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function linkedFeature()
    {
        return $this->morphTo('linked_feature');
    }
}
