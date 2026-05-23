<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    use HasFactory;

    protected $fillable = ['board_id','title','start_at','end_at','capacity_points','active'];

    protected $casts = [
        'start_at' => 'date',
        'end_at' => 'date',
        'active' => 'boolean',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
