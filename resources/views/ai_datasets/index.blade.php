@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Datasets</h1>
                <p class="text-white/60 text-sm md:text-base">Upload and manage training datasets</p>
            </div>
            <a href="{{ route('ai.datasets.create') }}" class="px-4 py-2 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all text-sm md:text-base">
                Upload Dataset
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Total Datasets</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ count($datasets) }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Used in Experiments</div>
                <div class="text-3xl md:text-4xl font-black text-blue-400 mt-2">
                    {{ collect($datasets)->sum(fn($d) => $d['dataset']->experiments_count) }}
                </div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Total Rows</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">
                    {{ collect($datasets)->sum(fn($d) => $d['rows']) }}
                </div>
            </div>
        </div>

        <!-- Datasets Table -->
        @if(count($datasets) > 0)
            <div class="glass rounded-2xl p-4 md:p-6 border border-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Name</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Type</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Size</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Rows</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Features</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Experiments</th>
                                <th class="text-left py-3 px-4 text-white/60 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($datasets as $item)
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-all">
                                    <td class="py-3 px-4 text-white font-semibold">{{ $item['dataset']->name }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-blue-500/20 text-blue-300 rounded text-xs font-semibold capitalize">
                                            {{ $item['dataset']->type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-white/70">{{ $item['size'] }}</td>
                                    <td class="py-3 px-4 text-white/70">{{ $item['rows'] }}</td>
                                    <td class="py-3 px-4 text-white/70">{{ $item['features'] }}</td>
                                    <td class="py-3 px-4 text-white/70">{{ $item['dataset']->experiments_count }}</td>
                                    <td class="py-3 px-4">
                                        <a href="{{ route('ai.datasets.show', $item['dataset']) }}" class="text-blue-400 hover:text-blue-300 text-xs font-semibold">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="glass rounded-2xl p-8 md:p-12 border border-white/10 text-center">
                <p class="text-white/60 mb-4">No datasets uploaded yet.</p>
                <a href="{{ route('ai.datasets.create') }}" class="inline-block px-4 py-2 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all">
                    Upload Your First Dataset
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
