<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Models\ModelVersion;
use Illuminate\Http\Request;

class DeploymentController extends Controller
{
    public function index()
    {
        $deployments = Deployment::with('version.model', 'logs')
            ->whereHas('version.model', fn($q) => $q->where('user_id', auth()->id()))
            ->get()
            ->map(function ($dep) {
                $recentLogs = $dep->logs()->latest()->limit(5)->get();
                return [
                    'deployment' => $dep,
                    'health' => $this->getDeploymentHealth($dep),
                    'recent_logs' => $recentLogs,
                    'logs_count' => $dep->logs()->count(),
                ];
            });

        return view('ai_deployments.index', ['deployments' => $deployments]);
    }

    public function show(Deployment $deployment)
    {
        $deployment->load('version.model', 'logs');
        $logs = $deployment->logs()->latest()->paginate(20);
        
        // Get deployment health metrics
        $health = $this->getDeploymentHealth($deployment);

        return view('ai_deployments.show', [
            'deployment' => $deployment,
            'logs' => $logs,
            'health' => json_encode($health),
        ]);
    }

    private function getDeploymentHealth(Deployment $deployment)
    {
        // Placeholder - integrate with actual monitoring service
        $recentLogs = $deployment->logs()->latest()->limit(100)->get();
        $errors = $recentLogs->where('level', 'error')->count();
        $warnings = $recentLogs->where('level', 'warning')->count();
        
        return [
            'status' => $deployment->status,
            'errors' => $errors,
            'warnings' => $warnings,
            'last_check' => $recentLogs->first()?->created_at ?? null,
            'uptime_percentage' => 99.5, // Placeholder
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate(['model_version_id' => 'required|exists:model_versions,id', 'environment' => 'required|string']);
        $dep = Deployment::create(['model_version_id' => $data['model_version_id'], 'environment' => $data['environment'], 'status' => 'deploying']);
        // Call deploy service (integration point)
        return redirect()->back()->with('success', 'Deployment started.');
    }
}
