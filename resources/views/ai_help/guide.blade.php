@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-12">
            <h1 class="text-4xl md:text-5xl font-black text-white mb-2">AI Model Management Guide</h1>
            <p class="text-white/60 text-lg">Step-by-step instructions to train and deploy AI models</p>
        </div>

        <!-- Quick Start Tabs -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
            <!-- Getting Started -->
            <div class="glass rounded-2xl p-6 border border-white/10">
                <h2 class="text-2xl font-bold text-white mb-4">🚀 Getting Started (5 min)</h2>
                <ol class="space-y-4">
                    <li class="flex gap-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">1</span>
                        <div>
                            <p class="font-semibold text-white"><a href="/ai/datasets/create" class="hover:text-blue-400">Upload a Dataset</a></p>
                            <p class="text-white/60 text-sm">CSV, JSON, or Excel file (max 100MB)</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">2</span>
                        <div>
                            <p class="font-semibold text-white"><a href="/ai/models" class="hover:text-blue-400">Create a Model</a></p>
                            <p class="text-white/60 text-sm">Pick a type: classification, regression, clustering, NLP</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">3</span>
                        <div>
                            <p class="font-semibold text-white"><a href="/ai/experiments" class="hover:text-blue-400">Create an Experiment</a></p>
                            <p class="text-white/60 text-sm">Link your model with your dataset</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">4</span>
                        <div>
                            <p class="font-semibold text-white"><a href="/ai/training-runs" class="hover:text-blue-400">Start Training</a></p>
                            <p class="text-white/60 text-sm">Configure parameters and begin training</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">5</span>
                        <div>
                            <p class="font-semibold text-white">Monitor Progress</p>
                            <p class="text-white/60 text-sm">Watch real-time charts and metrics update</p>
                        </div>
                    </li>
                </ol>
            </div>

            <!-- Key Metrics -->
            <div class="glass rounded-2xl p-6 border border-white/10">
                <h2 class="text-2xl font-bold text-white mb-4">📊 Understanding Metrics</h2>
                <div class="space-y-4">
                    <div>
                        <p class="font-semibold text-white">Accuracy</p>
                        <div class="mt-2 space-y-1 text-sm">
                            <p><span class="bg-green-500/20 text-green-300 px-2 py-1 rounded">90-100%</span> Excellent</p>
                            <p><span class="bg-blue-500/20 text-blue-300 px-2 py-1 rounded">80-90%</span> Very Good</p>
                            <p><span class="bg-yellow-500/20 text-yellow-300 px-2 py-1 rounded">70-80%</span> Good</p>
                            <p><span class="bg-red-500/20 text-red-300 px-2 py-1 rounded">&lt;50%</span> Needs Work</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="font-semibold text-white">Loss (Lower is Better)</p>
                        <div class="mt-2 space-y-1 text-sm">
                            <p><span class="bg-green-500/20 text-green-300 px-2 py-1 rounded">&lt;0.1</span> Excellent</p>
                            <p><span class="bg-blue-500/20 text-blue-300 px-2 py-1 rounded">0.1-0.5</span> Good</p>
                            <p><span class="bg-yellow-500/20 text-yellow-300 px-2 py-1 rounded">0.5-1.0</span> Okay</p>
                            <p><span class="bg-red-500/20 text-red-300 px-2 py-1 rounded">&gt;1.0</span> Poor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Guide -->
        <div class="glass rounded-2xl p-6 md:p-8 border border-white/10 mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">⚙️ Training Parameters Guide</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Epochs -->
                <div class="bg-white/5 rounded-lg p-4">
                    <h3 class="text-lg font-bold text-white mb-2">Epochs</h3>
                    <p class="text-white/60 text-sm mb-3">How many times the model sees all the data</p>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-semibold text-white">Default:</span> 10</p>
                        <p><span class="font-semibold text-white">Range:</span> 1 - 1000</p>
                        <p><span class="font-semibold text-white">For beginners:</span> 10-20</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-white/10">
                        <p class="text-xs text-white/50">More = potentially better but slower</p>
                    </div>
                </div>

                <!-- Batch Size -->
                <div class="bg-white/5 rounded-lg p-4">
                    <h3 class="text-lg font-bold text-white mb-2">Batch Size</h3>
                    <p class="text-white/60 text-sm mb-3">Samples processed before updating</p>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-semibold text-white">Default:</span> 32</p>
                        <p><span class="font-semibold text-white">Range:</span> 1 - 1024</p>
                        <p><span class="font-semibold text-white">For beginners:</span> 16-32</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-white/10">
                        <p class="text-xs text-white/50">Larger = faster, smaller = more precise</p>
                    </div>
                </div>

                <!-- Learning Rate -->
                <div class="bg-white/5 rounded-lg p-4">
                    <h3 class="text-lg font-bold text-white mb-2">Learning Rate</h3>
                    <p class="text-white/60 text-sm mb-3">How quickly the model learns</p>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-semibold text-white">Default:</span> 0.001</p>
                        <p><span class="font-semibold text-white">Range:</span> 0.00001 - 1</p>
                        <p><span class="font-semibold text-white">For beginners:</span> 0.001</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-white/10">
                        <p class="text-xs text-white/50">Lower = stable, higher = fast</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="glass rounded-2xl p-6 md:p-8 border border-white/10 mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">🔧 Troubleshooting</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-red-500/5 border border-red-500/20 rounded-lg p-4">
                    <h3 class="text-white font-bold mb-2">❌ No data showing in charts?</h3>
                    <ul class="text-sm text-white/60 space-y-1">
                        <li>✓ Wait for training to complete (progress = 100%)</li>
                        <li>✓ Refresh the page</li>
                        <li>✓ Create an experiment first</li>
                        <li>✓ Start a training job</li>
                    </ul>
                </div>

                <div class="bg-red-500/5 border border-red-500/20 rounded-lg p-4">
                    <h3 class="text-white font-bold mb-2">❌ File won't upload?</h3>
                    <ul class="text-sm text-white/60 space-y-1">
                        <li>✓ File size must be &lt; 100MB</li>
                        <li>✓ Format must be .csv, .json, .parquet, or .xlsx</li>
                        <li>✓ Clear browser cache and try again</li>
                        <li>✓ Try different browser</li>
                    </ul>
                </div>

                <div class="bg-yellow-500/5 border border-yellow-500/20 rounded-lg p-4">
                    <h3 class="text-white font-bold mb-2">⚠️ Accuracy stuck at 0%?</h3>
                    <ul class="text-sm text-white/60 space-y-1">
                        <li>✓ Training might still be processing</li>
                        <li>✓ Try increasing epochs to 20</li>
                        <li>✓ Check data quality (no missing values)</li>
                        <li>✓ Try different model type</li>
                    </ul>
                </div>

                <div class="bg-yellow-500/5 border border-yellow-500/20 rounded-lg p-4">
                    <h3 class="text-white font-bold mb-2">⚠️ Training stuck at 0%?</h3>
                    <ul class="text-sm text-white/60 space-y-1">
                        <li>✓ Wait 30 seconds for queue to process</li>
                        <li>✓ Cancel and restart the job</li>
                        <li>✓ Try with fewer epochs (10 instead of 50)</li>
                        <li>✓ Check browser console (F12) for errors</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Reference Table -->
        <div class="glass rounded-2xl p-6 md:p-8 border border-white/10 mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">⚡ Quick Navigation</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left py-3 px-4 text-white/60 font-semibold">Action</th>
                            <th class="text-left py-3 px-4 text-white/60 font-semibold">URL / Link</th>
                            <th class="text-left py-3 px-4 text-white/60 font-semibold">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-white/5 hover:bg-white/5">
                            <td class="py-3 px-4 text-white">Browse Datasets</td>
                            <td class="py-3 px-4"><a href="/ai/datasets" class="text-blue-400 hover:text-blue-300">/ai/datasets</a></td>
                            <td class="py-3 px-4 text-white/60">1 min</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5">
                            <td class="py-3 px-4 text-white">Upload Dataset</td>
                            <td class="py-3 px-4"><a href="/ai/datasets/create" class="text-blue-400 hover:text-blue-300">/ai/datasets/create</a></td>
                            <td class="py-3 px-4 text-white/60">2 min</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5">
                            <td class="py-3 px-4 text-white">Create Model</td>
                            <td class="py-3 px-4"><a href="/ai/models" class="text-blue-400 hover:text-blue-300">/ai/models</a></td>
                            <td class="py-3 px-4 text-white/60">1 min</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5">
                            <td class="py-3 px-4 text-white">Create Experiment</td>
                            <td class="py-3 px-4"><a href="/ai/experiments" class="text-blue-400 hover:text-blue-300">/ai/experiments</a></td>
                            <td class="py-3 px-4 text-white/60">1 min</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5">
                            <td class="py-3 px-4 text-white">Start Training</td>
                            <td class="py-3 px-4"><a href="/ai/training-runs" class="text-blue-400 hover:text-blue-300">/ai/training-runs</a></td>
                            <td class="py-3 px-4 text-white/60">2-5 min</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5">
                            <td class="py-3 px-4 text-white">View Model Dashboard</td>
                            <td class="py-3 px-4"><a href="/ai/models/1" class="text-blue-400 hover:text-blue-300">/ai/models/[id]</a></td>
                            <td class="py-3 px-4 text-white/60">1 min</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sample Data -->
        <div class="glass rounded-2xl p-6 md:p-8 border border-white/10">
            <h2 class="text-2xl font-bold text-white mb-4">📝 Sample Data to Try</h2>
            <p class="text-white/60 mb-4">Copy this data, save it as test_data.csv, and upload it to get started:</p>
            <div class="bg-black/30 rounded-lg p-4 overflow-x-auto font-mono text-sm text-white/70">
                <pre>id,age,income,credit_score,employment,approved
1,25,35000,650,employed,no
2,35,65000,750,employed,yes
3,22,28000,600,unemployed,no
4,45,85000,800,employed,yes
5,28,50000,720,employed,yes
6,31,72000,780,employed,yes
7,19,22000,580,unemployed,no
8,52,95000,820,employed,yes
9,26,45000,700,employed,yes
10,38,68000,760,employed,yes</pre>
            </div>
            <p class="text-white/60 text-sm mt-4">This is a classification dataset for predicting loan approval. It's perfect for testing!</p>
        </div>
    </div>
</div>
@endsection
