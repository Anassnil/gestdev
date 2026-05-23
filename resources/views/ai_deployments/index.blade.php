@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Deployments</h1>
                <p class="text-white/60 text-sm md:text-base">Active model deployments across environments</p>
            </div>
            <a href="#" class="px-4 py-2 bg-blue-600 rounded-xl font-bold hover:bg-blue-700 transition-all text-sm md:text-base">
                New Deployment
            </a>
        </div>

        <!-- Deployment Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Total Deployments</div>
                <div class="text-3xl md:text-4xl font-black text-white mt-2">{{ count($deployments) }}</div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">Active</div>
                <div class="text-3xl md:text-4xl font-black text-green-400 mt-2">
                    {{ count(array_filter($deployments, fn($d) => $d['deployment']->status === 'active')) }}
                </div>
            </div>
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="text-white/60 text-xs md:text-sm uppercase tracking-wide">With Errors</div>
                <div class="text-3xl md:text-4xl font-black text-red-400 mt-2">
                    {{ count(array_filter($deployments, fn($d) => $d['health']['errors'] > 0)) }}
                </div>
            </div>
        </div>

        <!-- Deployments List -->
        <div class="space-y-4">
            @forelse($deployments as $item)
                <a href="#" class="block glass rounded-2xl p-4 md:p-6 border border-white/10 hover:border-blue-500/40 transition-all">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                        <!-- Info -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg md:text-xl font-bold text-white">
                                        {{ $item['deployment']->version->model->name }}
                                    </h3>
                                    <p class="text-white/60 text-sm">
                                        v{{ $item['deployment']->version->version ?? $item['deployment']->model_version_id }}
                                        • {{ $item['deployment']->environment }}
                                    </p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $item['health']['status'] === 'active' ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300' }}">
                                    {{ ucfirst($item['health']['status']) }}
                                </span>
                            </div>
                            @if($item['deployment']->endpoint_url)
                                <p class="text-white/50 text-xs md:text-sm">
                                    <span class="text-white/40">Endpoint:</span> {{ $item['deployment']->endpoint_url }}
                                </p>
                            @endif
                        </div>

                        <!-- Health Metrics -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div class="bg-white/5 rounded-lg p-3">
                                <div class="text-white/60 text-xs">Uptime</div>
                                <div class="text-xl md:text-2xl font-black text-green-400">{{ $item['health']['uptime_percentage'] }}%</div>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <div class="text-white/60 text-xs">Errors</div>
                                <div class="text-xl md:text-2xl font-black {{ $item['health']['errors'] > 0 ? 'text-red-400' : 'text-white' }}">
                                    {{ $item['health']['errors'] }}
                                </div>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <div class="text-white/60 text-xs">Warnings</div>
                                <div class="text-xl md:text-2xl font-black {{ $item['health']['warnings'] > 0 ? 'text-yellow-400' : 'text-white' }}">
                                    {{ $item['health']['warnings'] }}
                                </div>
                            </div>
                            <div class="col-span-2 sm:col-span-1 bg-white/5 rounded-lg p-3">
                                <div class="text-white/60 text-xs">Logs</div>
                                <div class="text-xl md:text-2xl font-black text-blue-400">{{ $item['logs_count'] }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Logs Preview -->
                    @if(count($item['recent_logs']) > 0)
                        <div class="mt-4 pt-4 border-t border-white/10">
                            <div class="text-sm font-semibold text-white/60 mb-2">Recent Activity</div>
                            <div class="space-y-1">
                                @foreach($item['recent_logs']->take(3) as $log)
                                    <div class="text-xs text-white/50 truncate">
                                        <span class="inline-block w-2 h-2 rounded-full {{ $log->level === 'error' ? 'bg-red-400' : ($log->level === 'warning' ? 'bg-yellow-400' : 'bg-green-400') }} mr-2"></span>
                                        {{ $log->message }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </a>
            @empty
                <div class="glass rounded-2xl p-6 md:p-8 border border-white/10 text-center">
                    <p class="text-white/60">No deployments yet.</p>
                    <a href="#" class="mt-4 inline-block px-4 py-2 bg-blue-600 rounded-xl font-bold hover:bg-blue-700 transition-all">
                        Create Your First Deployment
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
