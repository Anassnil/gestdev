<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardCollaborator extends Model
{
    protected $fillable = [
        'board_id',
        'user_id',
        'role',
        'invited_by',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
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
