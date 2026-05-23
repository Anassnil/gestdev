<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id','title','description','status','position','assignee_id',
        'priority','points','type','due_date','dependencies','tags','sprint_id','quadrant','pr_url'
    ];

    protected $casts = [
        'dependencies' => 'array',
        'tags' => 'array',
        'due_date' => 'date',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
