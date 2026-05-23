@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-black text-white">Start Training Job</h1>
            <p class="text-white/60 text-sm md:text-base">Configure and launch a new training run for experiment #{{ $experiment->id }}</p>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('ai.training_runs.store') }}" class="space-y-6">
            @csrf
            
            <input type="hidden" name="experiment_id" value="{{ $experiment->id }}">

            <!-- Model Info -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Experiment Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-white/60 text-sm">Model</p>
                        <p class="text-white font-semibold mt-1">{{ $experiment->model->name }}</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm">Experiment ID</p>
                        <p class="text-white font-semibold mt-1">#{{ $experiment->id }}</p>
                    </div>
                </div>
            </div>

            <!-- Training Parameters -->
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <h3 class="text-white font-bold text-lg mb-4">Training Parameters</h3>
                
                <div class="space-y-4">
                    <!-- Epochs -->
                    <div>
                        <label for="epochs" class="block text-white font-semibold mb-2">Epochs</label>
                        <input type="number" id="epochs" name="epochs" value="10" min="1" max="1000" 
                               class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-blue-500/50">
                        <p class="text-white/60 text-sm mt-1">Number of training epochs (default: 10)</p>
                        @error('epochs')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Batch Size -->
                    <div>
                        <label for="batch_size" class="block text-white font-semibold mb-2">Batch Size</label>
                        <input type="number" id="batch_size" name="batch_size" value="32" min="1" max="1024"
                               class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-blue-500/50">
                        <p class="text-white/60 text-sm mt-1">Number of samples per gradient update (default: 32)</p>
                        @error('batch_size')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Learning Rate -->
                    <div>
                        <label for="learning_rate" class="block text-white font-semibold mb-2">Learning Rate</label>
                        <input type="number" id="learning_rate" name="learning_rate" value="0.001" step="0.00001" min="0.00001" max="1"
                               class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-blue-500/50">
                        <p class="text-white/60 text-sm mt-1">Learning rate for optimizer (default: 0.001)</p>
                        @error('learning_rate')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-4">
                <button type="submit" class="px-6 py-3 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all">
                    Start Training
                </button>
                <a href="{{ route('ai.experiments.show', $experiment) }}" class="px-6 py-3 bg-white/5 border border-white/10 rounded-xl font-bold hover:bg-white/10 transition-all">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
