<?php

namespace App\Services;

use App\Models\AIModel;
use App\Models\ModelVersion;

class ModelRegistry
{
    public function register(AIModel $model)
    {
        // placeholder for registering a model (could push to external registry)
        return $model;
    }

    public function addVersion(AIModel $model, array $config = [], ?string $version = null)
    {
        return $model->versions()->create(['version' => $version, 'config' => $config, 'status' => 'created']);
    }

    public function getVersions(int $modelId)
    {
        return ModelVersion::where('ai_model_id', $modelId)->get();
    }
}
