<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagram extends Model
{
    use HasFactory;

    protected $fillable = ['board_id','type','title','image','code'];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
