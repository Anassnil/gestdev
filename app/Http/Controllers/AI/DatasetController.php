<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DatasetController extends Controller
{
    public function index()
    {
        $datasets = Dataset::where('user_id', auth()->id())
            ->withCount('experiments')
            ->get()
            ->map(function ($dataset) {
                return [
                    'dataset' => $dataset,
                    'size' => $dataset->metadata['size'] ?? 'Unknown',
                    'rows' => $dataset->metadata['rows'] ?? 0,
                    'features' => $dataset->metadata['features'] ?? 0,
                ];
            });

        return view('ai_datasets.index', ['datasets' => $datasets]);
    }

    public function create()
    {
        return view('ai_datasets.create');
    }

    public function show(Dataset $dataset)
    {
        $dataset->load('experiments');
        
        $metadata = $dataset->metadata ?? [];
        $preview = $metadata['preview'] ?? [];
        $statistics = $metadata['statistics'] ?? [];

        return view('ai_datasets.show', [
            'dataset' => $dataset,
            'preview' => $preview,
            'statistics' => $statistics,
            'rows' => $metadata['rows'] ?? 0,
            'features' => $metadata['features'] ?? 0,
            'size' => $metadata['size'] ?? 'Unknown',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:training,validation,test,custom',
            'description' => 'nullable|string',
            'file' => 'required|file|max:102400', // 100MB
        ]);

        // Store file
        $file = $request->file('file');
        $path = $file->store('datasets', 'public');
        
        // Parse file and extract metadata
        $metadata = $this->extractMetadata($file, $path);

        $dataset = Dataset::create([
            'name' => $request->name,
            'type' => $request->type,
            'path' => $path,
            'metadata' => $metadata,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('ai.datasets.show', $dataset)
            ->with('success', 'Dataset uploaded successfully!');
    }

    public function destroy(Dataset $dataset)
    {
        // Check if dataset is in use
        if ($dataset->experiments()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete dataset in use by experiments.');
        }

        Storage::disk('public')->delete($dataset->path);
        $dataset->delete();

        return redirect()->route('ai.datasets.index')
            ->with('success', 'Dataset deleted.');
    }

    private function extractMetadata($file, $path)
    {
        $size = $file->getSize();
        $metadata = [
            'size' => $this->formatBytes($size),
            'rows' => 0,
            'features' => 0,
            'preview' => [],
            'statistics' => [],
        ];

        try {
            // For CSV files, try to extract basic info
            if ($file->getClientOriginalExtension() === 'csv') {
                $handle = fopen($file->getRealPath(), 'r');
                $header = fgetcsv($handle);
                $metadata['features'] = count($header ?? []);
                $metadata['preview'][] = $header;
                
                // Read first 5 rows
                $rows = 0;
                while (($row = fgetcsv($handle)) && $rows < 5) {
                    $metadata['preview'][] = $row;
                    $rows++;
                }
                $metadata['rows'] = $rows;
                fclose($handle);
            }
        } catch (\Exception $e) {
            // Silently fail metadata extraction
        }

        return $metadata;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
