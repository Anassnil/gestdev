<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    use HasFactory;

    protected $table = 'datasets';

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $fillable = ['name', 'type', 'path', 'metadata', 'user_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function experiments()
    {
        return $this->hasMany(Experiment::class);
    }

    // Accessors for commonly used metadata
    public function getFileSizeFormattedAttribute()
    {
        return $this->metadata['size'] ?? 'Unknown';
    }

    public function getRowsCountAttribute()
    {
        return $this->metadata['rows'] ?? 0;
    }

    public function getFeaturesCountAttribute()
    {
        return $this->metadata['features'] ?? 0;
    }

    public function getPreviewDataAttribute()
    {
        $preview = $this->metadata['preview'] ?? [];
        if (empty($preview)) {
            return null;
        }

        // Convert array to JSON with headers as keys
        if (count($preview) > 1) {
            $headers = $preview[0];
            $rows = array_slice($preview, 1);
            return json_encode(array_map(function ($row) use ($headers) {
                return array_combine($headers, $row ?? []);
            }, $rows));
        }

        return json_encode($preview);
    }

    public function getFileTypeAttribute()
    {
        // Try to get from path or metadata
        $pathInfo = pathinfo($this->path);
        return strtoupper($pathInfo['extension'] ?? 'UNKNOWN');
    }
}
