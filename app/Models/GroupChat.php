<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupChat extends Model
{
    protected $fillable = [
        'name',
        'board_id',
        'created_by',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(GroupChatMember::class);
    }

    public function messages()
    {
        return $this->hasMany(GroupChatMessage::class);
    }
}
