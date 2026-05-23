<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use SoftDeletes;

    protected $table = 'messages';

    protected $fillable = [
        'sender_id', 'receiver_id', 'body', 'read_at',
        'attachment_path', 'attachment_name', 'attachment_mime',
        'reply_to_id', 'edited_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    public function getIsImageAttribute(): bool
    {
        return $this->attachment_mime && str_starts_with($this->attachment_mime, 'image/');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
