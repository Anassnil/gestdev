<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIPlan extends Model
{
    protected $table = 'ai_plans';

    protected $fillable = [
        'board_id', 'user_id', 'title', 'input_text', 'result_json'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (AIPlan $plan) {
            if (auth()->check() && empty($plan->user_id)) {
                $plan->user_id = auth()->id();
            }
        });
    }

    protected $casts = [
        'result_json' => 'array',
    ];
}
