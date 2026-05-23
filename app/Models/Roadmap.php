<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roadmap extends Model
{
    use HasFactory;

    protected $fillable = ['board_id','title','description'];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class)->orderBy('position');
    }
}
