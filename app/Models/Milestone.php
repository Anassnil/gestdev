<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    use HasFactory;

    protected $fillable = ['roadmap_id','title','notes','due_at','completed','position'];

    protected $casts = [
        'due_at' => 'date',
        'completed' => 'boolean',
    ];

    public function roadmap()
    {
        return $this->belongsTo(Roadmap::class);
    }
}
