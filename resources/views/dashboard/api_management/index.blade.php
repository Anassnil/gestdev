@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-screen-2xl mx-auto">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight flex items-center gap-3">
                    <div class="p-2 bg-violet-600 rounded-lg shadow-lg shadow-violet-500/20">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    API Management
                </h1>
                <p class="text-white/40 text-sm mt-1 ml-14">Design, document and manage your APIs.</p>
            </div>
            <button onclick="document.getElementById('modalNewApi').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm
                       bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500
                       text-white shadow-lg shadow-violet-500/25 hover:shadow-violet-500/40
                       hover:scale-[1.03] active:scale-95 transition-all duration-200 whitespace-nowrap flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New API
            </button>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="flash-success mb-6 rounded-xl border border-green-500/30 bg-green-500/10 text-green-300 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="flash-error mb-6 rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="glass p-5 rounded-2xl border border-white/5">
                <div class="text-xs text-white/50 uppercase tracking-wider font-medium mb-1">Total APIs</div>
                <div class="text-3xl font-black" style="color: var(--text-primary)">{{ $stats['total'] }}</div>
            </div>
            <div class="glass p-5 rounded-2xl border border-white/5">
                <div class="text-xs text-white/50 uppercase tracking-wider font-medium mb-1">Active</div>
                <div class="text-3xl font-black text-green-400">{{ $stats['active'] }}</div>
            </div>
            <div class="glass p-5 rounded-2xl border border-white/5">
                <div class="text-xs text-white/50 uppercase tracking-wider font-medium mb-1">Endpoints</div>
                <div class="text-3xl font-black text-blue-400">{{ $stats['endpoints'] }}</div>
            </div>
            <div class="glass p-5 rounded-2xl border border-white/5">
                <div class="text-xs text-white/50 uppercase tracking-wider font-medium mb-1">Collections</div>
                <div class="text-3xl font-black text-violet-400">{{ $stats['collections'] }}</div>
            </div>
        </div>

        {{-- API List --}}
        @if($apis->isEmpty())
            <div class="glass rounded-2xl border border-white/5 p-16 text-center">
                <div class="w-20 h-20 mx-auto bg-violet-500/10 rounded-2xl flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2" style="color: var(--text-primary)">No APIs yet</h3>
                <p class="text-white/40 text-sm mb-6">Create your first API to get started designing and documenting.</p>
                <button onclick="document.getElementById('modalNewApi').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm bg-gradient-to-r from-violet-600 to-indigo-600 text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create First API
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($apis as $api)
                    <a href="{{ route('dashboard.api_management.show', $api) }}"
                        class="glass group p-5 rounded-2xl border border-white/5 hover:border-violet-500/40
                               transition-all duration-300 hover:shadow-[0_0_30px_rgba(139,92,246,0.10)] block">
                        <div class="flex items-start justify-between mb-3">
                            <div class="p-2.5 bg-violet-500/10 rounded-xl text-violet-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-lg
                                @if($api->status === 'active') bg-green-500/20 text-green-400
                                @elseif($api->status === 'deprecated') bg-red-500/20 text-red-400
                                @else bg-yellow-500/20 text-yellow-400 @endif">
                                {{ ucfirst($api->status) }}
                            </span>
                        </div>

                        <h3 class="font-bold text-lg group-hover:text-violet-300 transition-colors duration-200" style="color: var(--text-primary)">
                            {{ $api->name }}
                        </h3>
                        @if($api->base_url)
                            <p class="text-xs text-white/30 font-mono mt-0.5 truncate">{{ $api->base_url }}</p>
                        @endif
                        @if($api->description)
                            <p class="text-sm text-white/50 mt-2 line-clamp-2">{{ $api->description }}</p>
                        @endif

                        <div class="flex items-center gap-4 mt-4 pt-4 border-t border-white/5 text-xs text-white/40">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                {{ $api->endpoints_count }} endpoint{{ $api->endpoints_count !== 1 ? 's' : '' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                {{ $api->collections_count }} collection{{ $api->collections_count !== 1 ? 's' : '' }}
                            </span>
                            <span class="ml-auto font-mono text-white/20">v{{ $api->version }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</div>

{{-- Modal: New API --}}
<div id="modalNewApi" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="document.getElementById('modalNewApi').classList.add('hidden')"></div>
    <div class="relative max-w-lg w-full glass rounded-2xl border border-white/10 p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold" style="color: var(--text-primary)">New API</h3>
            <button onclick="document.getElementById('modalNewApi').classList.add('hidden')"
                class="text-white/40 hover:text-white transition-colors text-xl leading-none">✕</button>
        </div>
        <form method="POST" action="{{ route('dashboard.api_management.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Name *</label>
                <input type="text" name="name" placeholder="My REST API" required autofocus
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30
                           focus:outline-none focus:border-violet-500 text-sm transition-colors">
            </div>
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Base URL</label>
                <input type="url" name="base_url" placeholder="https://api.example.com/v1"
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30
                           focus:outline-none focus:border-violet-500 text-sm transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Version</label>
                    <input type="text" name="version" placeholder="1.0.0" value="1.0.0"
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30
                               focus:outline-none focus:border-violet-500 text-sm transition-colors">
                </div>
                <div>
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Status</label>
                    <select name="status"
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white
                               focus:outline-none focus:border-violet-500 text-sm transition-colors">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="deprecated">Deprecated</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Description</label>
                <textarea name="description" rows="3" placeholder="Describe what this API does..."
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30
                           focus:outline-none focus:border-violet-500 text-sm resize-none transition-colors"></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="flex-1 px-4 py-2.5 rounded-xl font-bold text-sm
                           bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500
                           text-white transition-all duration-200">
                    Create API
                </button>
                <button type="button" onclick="document.getElementById('modalNewApi').classList.add('hidden')"
                    class="px-4 py-2.5 rounded-xl font-bold text-sm bg-white/5 text-white/70 hover:bg-white/10 transition-all">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
