<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $fillable = [
        'board_id','title','description','type','version','target_date','priority','status','position','meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'target_date' => 'date',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
