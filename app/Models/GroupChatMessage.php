<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GroupChatMessage extends Model
{
    protected $fillable = [
        'group_chat_id',
        'sender_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    public function getIsImageAttribute(): bool
    {
        return $this->attachment_mime && str_starts_with($this->attachment_mime, 'image/');
    }

    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
