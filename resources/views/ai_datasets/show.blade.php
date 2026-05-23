@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">{{ $dataset->name }}</h1>
                <p class="text-white/60 text-sm md:text-base">Dataset #{{ $dataset->id }} • {{ ucfirst($dataset->type) }}</p>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('ai.datasets.destroy', $dataset) }}" onsubmit="return confirm('Delete this dataset?');" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button class="px-4 py-2 bg-red-600/20 border border-red-500/30 rounded-xl font-bold text-red-300 hover:bg-red-600/30 transition-all text-sm md:text-base">
                        Delete
                    </button>
                </form>
                <a href="{{ route('ai.datasets.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm md:text-base">
                    Back
                </a>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="glass rounded-2xl p-4 border border-white/10">
                <p class="text-white/60 text-xs md:text-sm uppercase tracking-wide">File Size</p>
                <p class="text-2xl md:text-3xl font-black text-white mt-2">{{ $dataset->file_size_formatted }}</p>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <p class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Total Rows</p>
                <p class="text-2xl md:text-3xl font-black text-blue-400 mt-2">{{ $dataset->rows_count ?? 0 }}</p>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <p class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Features</p>
                <p class="text-2xl md:text-3xl font-black text-green-400 mt-2">{{ $dataset->features_count ?? 0 }}</p>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <p class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Used In</p>
                <p class="text-2xl md:text-3xl font-black text-purple-400 mt-2">{{ $dataset->experiments()->count() }} Exp.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Description -->
                @if($dataset->description)
                    <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                        <h3 class="text-white font-bold text-lg mb-3">Description</h3>
                        <p class="text-white/70 text-sm md:text-base leading-relaxed">{{ $dataset->description }}</p>
                    </div>
                @endif

                <!-- Data Preview -->
                @if($dataset->preview_data)
                    <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                        <h3 class="text-white font-bold text-lg mb-4">Data Preview</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-white/10">
                                        @php
                                            $previewData = json_decode($dataset->preview_data, true);
                                            if (!empty($previewData) && is_array($previewData[0])) {
                                                foreach (array_keys($previewData[0]) as $header) {
                                                    echo "<th class='px-3 py-2 text-left text-white/60 font-semibold'>" . htmlspecialchars($header) . "</th>";
                                                }
                                            }
                                        @endphp
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        if (!empty($previewData)) {
                                            foreach ($previewData as $row) {
                                                echo "<tr class='border-b border-white/5 hover:bg-white/5 transition-colors'>";
                                                foreach ($row as $cell) {
                                                    echo "<td class='px-3 py-2 text-white/70'>" . htmlspecialchars(substr($cell, 0, 50)) . "</td>";
                                                }
                                                echo "</tr>";
                                            }
                                        }
                                    @endphp
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Statistics -->
                <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                    <h3 class="text-white font-bold text-lg mb-4">Dataset Information</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-3 border-b border-white/10">
                            <span class="text-white/60">File Type</span>
                            <span class="text-white font-semibold">{{ $dataset->file_type ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-white/10">
                            <span class="text-white/60">Dataset Type</span>
                            <span class="inline-block px-2 py-1 bg-blue-500/20 text-blue-300 rounded font-semibold text-sm">{{ ucfirst($dataset->type) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-white/10">
                            <span class="text-white/60">Created</span>
                            <span class="text-white font-semibold">{{ $dataset->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3">
                            <span class="text-white/60">Last Updated</span>
                            <span class="text-white font-semibold">{{ $dataset->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Experiments Using This Dataset -->
                <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                    <h3 class="text-white font-bold text-lg mb-4">Experiments Using Dataset</h3>
                    @php
                        $experiments = $dataset->experiments()->limit(5)->get();
                    @endphp
                    @if($experiments->count() > 0)
                        <div class="space-y-3">
                            @foreach($experiments as $exp)
                                <a href="{{ route('ai.experiments.show', $exp) }}" class="block p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors group">
                                    <p class="text-white font-semibold text-sm group-hover:text-blue-400">Exp #{{ $exp->id }}</p>
                                    <p class="text-white/60 text-xs">{{ $exp->model->name }}</p>
                                </a>
                            @endforeach
                            @if($dataset->experiments()->count() > 5)
                                <p class="text-white/60 text-xs text-center mt-2">... and {{ $dataset->experiments()->count() - 5 }} more</p>
                            @endif
                        </div>
                    @else
                        <p class="text-white/60 text-sm">Not used in any experiments yet</p>
                    @endif
                </div>

                <!-- Quick Actions -->
                <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                    <h3 class="text-white font-bold text-lg mb-4">Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('ai.datasets.index') }}" class="block w-full px-4 py-2 bg-blue-600/20 border border-blue-500/30 rounded-lg text-center font-bold text-blue-300 hover:bg-blue-600/30 transition-all text-sm">
                            Download Dataset
                        </a>
                        <a href="{{ route('ai.experiments.index') }}" class="block w-full px-4 py-2 bg-green-600/20 border border-green-500/30 rounded-lg text-center font-bold text-green-300 hover:bg-green-600/30 transition-all text-sm">
                            Use in Experiment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
