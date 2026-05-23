<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIRequest extends Model
{
    protected $table = 'ai_requests';

    protected $fillable = [
        'user_id',
        'type',
        'input',
        'output',
        'status',
        'model',
        'tokens_used',
        'retries',
        'duration_ms',
        'meta',
    ];

    protected $casts = [
        'meta'       => 'array',
        'tokens_used'=> 'integer',
        'retries'    => 'integer',
        'duration_ms'=> 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['success', 'cached']);
    }
}
