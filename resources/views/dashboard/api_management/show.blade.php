@extends('layouts.dashboard')

@php
    $methodColors = [
        'GET'    => 'bg-green-500/20 text-green-400 border-green-500/30',
        'POST'   => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        'PUT'    => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        'PATCH'  => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        'DELETE' => 'bg-red-500/20 text-red-400 border-red-500/30',
    ];
    $statusColors = [
        'active'     => 'bg-green-500/20 text-green-400',
        'draft'      => 'bg-yellow-500/20 text-yellow-400',
        'deprecated' => 'bg-red-500/20 text-red-400',
    ];
@endphp

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-screen-2xl mx-auto">

        {{-- 1. Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-8">
            <div class="flex items-start gap-4">
                <a href="{{ route('dashboard.api_management.index') }}"
                    class="back-repo-btn mt-1 p-2 rounded-xl border border-white/10 text-white/50 hover:text-white hover:border-violet-500/50 transition-all duration-200 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight" style="color: var(--text-primary)">
                            {{ $api->name }}
                        </h1>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-lg {{ $statusColors[$api->status] ?? 'bg-white/10 text-white/50' }}">
                            {{ ucfirst($api->status) }}
                        </span>
                        <span class="text-xs font-mono text-white/30 bg-white/5 px-2 py-1 rounded-md">v{{ $api->version }}</span>
                    </div>
                    @if($api->base_url)
                        <p class="text-sm text-white/40 font-mono mt-1">{{ $api->base_url }}</p>
                    @endif
                    @if($api->description)
                        <p class="text-sm text-white/50 mt-1 max-w-2xl">{{ $api->description }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button onclick="document.getElementById('modalEditApi').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold
                           bg-white/5 border border-white/10 text-white/70 hover:bg-white/10 hover:text-white
                           transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit API
                </button>
            </div>
        </div>

        {{-- 2. Stats (2Ã—2) + Quick Actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">

            {{-- 2Ã—2 Stats Grid --}}
            <div class="lg:col-span-2 grid grid-cols-2 gap-4">
                <div class="glass p-5 rounded-2xl border border-white/5">
                    <div class="text-xs text-white/50 uppercase tracking-wider font-medium mb-1">Endpoints</div>
                    <div class="text-3xl font-black text-blue-400">{{ $api->endpoints->count() }}</div>
                </div>
                <div class="glass p-5 rounded-2xl border border-white/5">
                    <div class="text-xs text-white/50 uppercase tracking-wider font-medium mb-1">Collections</div>
                    <div class="text-3xl font-black text-violet-400">{{ $api->collections->count() }}</div>
                </div>
                <div class="glass p-5 rounded-2xl border border-white/5">
                    <div class="text-xs text-white/50 uppercase tracking-wider font-medium mb-1">Environments</div>
                    <div class="text-3xl font-black text-indigo-400">{{ $api->environments->count() }}</div>
                </div>
                <div class="glass p-5 rounded-2xl border border-white/5">
                    <div class="text-xs text-white/50 uppercase tracking-wider font-medium mb-1">Versions</div>
                    <div class="text-3xl font-black text-pink-400">{{ $api->versions->count() }}</div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="glass rounded-2xl border border-white/5 p-5">
                <h2 class="font-bold text-xs text-white/40 uppercase tracking-wider mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <button onclick="document.getElementById('modalAddEndpoint').classList.remove('hidden')"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl bg-white/3 hover:bg-violet-500/10
                               border border-transparent hover:border-violet-500/20 transition-all text-left group">
                        <div class="w-7 h-7 rounded-lg bg-violet-500/15 text-violet-400 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </div>
                        <div class="text-xs font-semibold" style="color: var(--text-primary)">Add Endpoint</div>
                    </button>
                    <button onclick="document.getElementById('modalAddCollection').classList.remove('hidden')"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl bg-white/3 hover:bg-indigo-500/10
                               border border-transparent hover:border-indigo-500/20 transition-all text-left group">
                        <div class="w-7 h-7 rounded-lg bg-indigo-500/15 text-indigo-400 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div class="text-xs font-semibold" style="color: var(--text-primary)">New Collection</div>
                    </button>
                    <button onclick="document.getElementById('modalAddEnvironment').classList.remove('hidden')"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl bg-white/3 hover:bg-blue-500/10
                               border border-transparent hover:border-blue-500/20 transition-all text-left group">
                        <div class="w-7 h-7 rounded-lg bg-blue-500/15 text-blue-400 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                        <div class="text-xs font-semibold" style="color: var(--text-primary)">Add Environment</div>
                    </button>
                    <button onclick="document.getElementById('modalAddVersion').classList.remove('hidden')"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl bg-white/3 hover:bg-pink-500/10
                               border border-transparent hover:border-pink-500/20 transition-all text-left group">
                        <div class="w-7 h-7 rounded-lg bg-pink-500/15 text-pink-400 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <div class="text-xs font-semibold" style="color: var(--text-primary)">Tag Release</div>
                    </button>
                </div>
            </div>

        </div>

        {{-- 3. Endpoints + API System Info / Danger Zone --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">

            {{-- Endpoints + Collections/Environments (left 2/3) --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="glass rounded-2xl border border-white/5 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-base" style="color: var(--text-primary)">Endpoints</h2>
                    <button onclick="document.getElementById('modalAddEndpoint').classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                               bg-violet-500/15 text-violet-400 border border-violet-500/20
                               hover:bg-violet-500/25 transition-all duration-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Endpoint
                    </button>
                </div>
                @if($api->endpoints->isEmpty())
                <div class="text-center py-12 text-white/30">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <p class="text-sm">No endpoints yet â€” add your first one.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($api->endpoints as $endpoint)
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/3 hover:bg-white/5 group transition-colors border border-transparent hover:border-white/5">
                            <span class="text-xs font-black px-2 py-0.5 rounded-md border font-mono min-w-[54px] text-center flex-shrink-0
                                {{ $methodColors[$endpoint->method] ?? 'bg-white/10 text-white/50 border-white/10' }}">
                                {{ $endpoint->method }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-mono text-white/80 truncate block">{{ $endpoint->path }}</span>
                                @if($endpoint->name !== $endpoint->path)
                                    <span class="text-xs text-white/40">{{ $endpoint->name }}</span>
                                @endif
                            </div>
                            @if($endpoint->collection)
                                <span class="hidden sm:inline text-xs text-white/30 bg-white/5 px-2 py-0.5 rounded-md truncate max-w-[100px]">
                                    {{ $endpoint->collection->name }}
                                </span>
                            @endif
                            <span class="text-xs px-2 py-0.5 rounded-md {{ $statusColors[$endpoint->status] ?? 'bg-white/10 text-white/50' }} hidden sm:inline">
                                {{ ucfirst($endpoint->status) }}
                            </span>
                            <form method="POST" action="{{ route('dashboard.api_management.endpoints.destroy', [$api, $endpoint]) }}"
                                onsubmit="return confirm('Delete this endpoint?')" class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400/60 hover:text-red-400 transition-colors p-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
                </div>
                {{-- Collections & Environments --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {{-- Collections --}}
                    <div class="glass rounded-2xl border border-white/5 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="font-bold text-sm tracking-wide" style="color: var(--text-primary)">Collections</h2>
                            <button onclick="document.getElementById('modalAddCollection').classList.remove('hidden')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold
                                       bg-indigo-500/15 text-indigo-400 border border-indigo-500/20
                                       hover:bg-indigo-500/25 transition-all duration-200">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                New
                            </button>
                        </div>
                        @if($api->collections->isEmpty())
                            <p class="text-xs text-white/30 text-center py-6">No collections yet.</p>
                        @else
                            <div class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                                @foreach($api->collections as $collection)
                                    <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl bg-white/3 border border-transparent hover:border-white/5 group transition-colors">
                                        <div class="w-6 h-6 rounded-lg bg-indigo-500/15 text-indigo-400 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium truncate" style="color: var(--text-primary)">{{ $collection->name }}</p>
                                        </div>
                                        <span class="text-[10px] text-white/30 bg-white/5 px-1.5 py-0.5 rounded">
                                            {{ $collection->endpoints->count() }} ep.
                                        </span>
                                        <form method="POST" action="{{ route('dashboard.api_management.collections.destroy', [$api, $collection]) }}"
                                            onsubmit="return confirm('Delete collection?')" class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-400/60 hover:text-red-400 transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Environments --}}
                    <div class="glass rounded-2xl border border-white/5 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="font-bold text-sm tracking-wide" style="color: var(--text-primary)">Environments</h2>
                            <button onclick="document.getElementById('modalAddEnvironment').classList.remove('hidden')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold
                                       bg-blue-500/15 text-blue-400 border border-blue-500/20
                                       hover:bg-blue-500/25 transition-all duration-200">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add
                            </button>
                        </div>
                        @if($api->environments->isEmpty())
                            <p class="text-xs text-white/30 text-center py-6">No environments yet.</p>
                        @else
                            <div class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                                @foreach($api->environments as $environment)
                                    <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl bg-white/3 border border-transparent hover:border-white/5 group transition-colors">
                                        <div class="w-6 h-6 rounded-lg bg-blue-500/15 text-blue-400 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium truncate" style="color: var(--text-primary)">{{ $environment->name }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('dashboard.api_management.environments.destroy', [$api, $environment]) }}"
                                            onsubmit="return confirm('Delete environment?')" class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-400/60 hover:text-red-400 transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- API System Info + Danger Zone (right 1/3) --}}
            <div class="space-y-6">
                <div class="glass rounded-2xl border border-white/5 p-5">
                    <h2 class="font-bold text-xs text-white/40 uppercase tracking-wider mb-3">API System Info</h2>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center border-b border-white/5 pb-2">
                            <span class="text-white/40">Slug</span>
                            <span class="font-mono text-white/70 bg-white/5 px-2 py-0.5 rounded text-[11px] truncate max-w-[140px]">{{ $api->slug }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-white/5 pb-2">
                            <span class="text-white/40">Active Spec</span>
                            <span class="font-mono text-white/70">v{{ $api->version }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-white/5 pb-2">
                            <span class="text-white/40">Created At</span>
                            <span class="text-white/60 font-medium">{{ $api->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-white/40">Last Updated</span>
                            <span class="text-white/60 font-medium">{{ $api->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Danger Zone --}}
                <div class="glass rounded-2xl border border-red-500/10 bg-red-500/[0.02] p-5">
                    <h2 class="font-bold text-xs text-red-400 uppercase tracking-wider mb-2">Danger Zone</h2>
                    <p class="text-xs text-white/40 mb-4">Permanently drops the workspace environment including all specs.</p>
                    <form method="POST" action="{{ route('dashboard.api_management.destroy', $api) }}"
                        onsubmit="return confirm('Permanently delete this API and all its data? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold
                                   bg-red-500/10 text-red-400 border border-red-500/20
                                   hover:bg-red-500/20 hover:border-red-500/30 transition-all duration-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete API Spec
                        </button>
                    </form>
                </div>
            </div>

        </div>

        {{-- 4. Versions --}}
        <div class="mb-8">
                {{-- Versions --}}
                <div class="glass rounded-2xl border border-white/5 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-bold text-base" style="color: var(--text-primary)">Release Versions</h2>
                        <button onclick="document.getElementById('modalAddVersion').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                                   bg-pink-500/15 text-pink-400 border border-pink-500/20
                                   hover:bg-pink-500/25 transition-all duration-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tag Version
                        </button>
                    </div>
                    @if($api->versions->isEmpty())
                        <p class="text-sm text-white/30 text-center py-8">No versions tagged yet.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($api->versions as $ver)
                                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/3 border border-transparent hover:border-white/5 group transition-colors">
                                    <div class="w-7 h-7 rounded-lg bg-pink-500/15 text-pink-400 flex items-center justify-center flex-shrink-0 text-xs font-black">
                                        v
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-mono font-semibold" style="color: var(--text-primary)">{{ $ver->version }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] uppercase font-bold px-1.5 py-0.2 rounded {{ $statusColors[$ver->status] ?? 'bg-white/10 text-white/50' }}">
                                                {{ $ver->status }}
                                            </span>
                                            @if($ver->release_date)
                                                <span class="text-xs text-white/30">{{ $ver->release_date->format('M d, Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('dashboard.api_management.versions.destroy', [$api, $ver]) }}"
                                        onsubmit="return confirm('Delete version?')" class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400/60 hover:text-red-400 transition-colors p-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
        </div>
    </div>
</div>

{{-- â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ MODALS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<div id="modalEditApi" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="document.getElementById('modalEditApi').classList.add('hidden')"></div>
    <div class="relative max-w-lg w-full glass rounded-2xl border border-white/10 p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold" style="color: var(--text-primary)">Edit API</h3>
            <button onclick="document.getElementById('modalEditApi').classList.add('hidden')"
                class="text-white/40 hover:text-white transition-colors text-xl leading-none">âœ•</button>
        </div>
        <form method="POST" action="{{ route('dashboard.api_management.update', $api) }}" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Name *</label>
                <input type="text" name="name" value="{{ $api->name }}" required
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-violet-500 text-sm">
            </div>
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Base URL</label>
                <input type="url" name="base_url" value="{{ $api->base_url }}" placeholder="https://api.example.com/v1"
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-violet-500 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Version</label>
                    <input type="text" name="version" value="{{ $api->version }}"
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-violet-500 text-sm">
                </div>
                <div>
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-violet-500 text-sm">
                        <option value="draft" @selected($api->status === 'draft')>Draft</option>
                        <option value="active" @selected($api->status === 'active')>Active</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modalEditApi').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg text-xs font-semibold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg text-xs font-semibold bg-violet-500/20 text-violet-300 border border-violet-500/30 hover:bg-violet-500/30 transition-all">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Add Endpoint Modal --}}
<div id="modalAddEndpoint" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="document.getElementById('modalAddEndpoint').classList.add('hidden')"></div>
    <div class="relative max-w-lg w-full glass rounded-2xl border border-white/10 p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold" style="color: var(--text-primary)">Add Endpoint</h3>
            <button type="button" onclick="document.getElementById('modalAddEndpoint').classList.add('hidden')"
                class="text-white/40 hover:text-white transition-colors text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('dashboard.api_management.endpoints.store', $api) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Method *</label>
                    <select name="method" required
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-violet-500 text-sm">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="PATCH">PATCH</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
                <div>
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Status *</label>
                    <select name="status" required
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-violet-500 text-sm">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="deprecated">Deprecated</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Path *</label>
                    <input type="text" name="path" required placeholder="/users/{id}"
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-violet-500 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Name *</label>
                    <input type="text" name="name" required placeholder="Get user by ID"
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-violet-500 text-sm">
                </div>
                @if($api->collections->isNotEmpty())
                <div class="col-span-2">
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Collection</label>
                    <select name="collection_id"
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-violet-500 text-sm">
                        <option value="">No collection</option>
                        @foreach($api->collections as $col)
                            <option value="{{ $col->id }}">{{ $col->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-span-2">
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional description..."
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-violet-500 text-sm resize-none"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-1">
                <button type="button" onclick="document.getElementById('modalAddEndpoint').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg text-xs font-semibold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg text-xs font-semibold bg-violet-500/20 text-violet-300 border border-violet-500/30 hover:bg-violet-500/30 transition-all">
                    Add Endpoint
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Add Collection Modal --}}
<div id="modalAddCollection" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="document.getElementById('modalAddCollection').classList.add('hidden')"></div>
    <div class="relative max-w-md w-full glass rounded-2xl border border-white/10 p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold" style="color: var(--text-primary)">New Collection</h3>
            <button type="button" onclick="document.getElementById('modalAddCollection').classList.add('hidden')"
                class="text-white/40 hover:text-white transition-colors text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('dashboard.api_management.collections.store', $api) }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Name *</label>
                <input type="text" name="name" required placeholder="Authentication"
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Optional description..."
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-indigo-500 text-sm resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-1">
                <button type="button" onclick="document.getElementById('modalAddCollection').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg text-xs font-semibold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all">
                    Create Collection
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Add Environment Modal --}}
<div id="modalAddEnvironment" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="document.getElementById('modalAddEnvironment').classList.add('hidden')"></div>
    <div class="relative max-w-md w-full glass rounded-2xl border border-white/10 p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold" style="color: var(--text-primary)">Add Environment</h3>
            <button type="button" onclick="document.getElementById('modalAddEnvironment').classList.add('hidden')"
                class="text-white/40 hover:text-white transition-colors text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('dashboard.api_management.environments.store', $api) }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Name *</label>
                <input type="text" name="name" required placeholder="Production"
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-blue-500 text-sm">
            </div>
            <div>
                <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Base URL</label>
                <input type="url" name="base_url" placeholder="https://api.example.com/v1"
                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-blue-500 text-sm">
            </div>
            <div class="flex justify-end gap-3 pt-1">
                <button type="button" onclick="document.getElementById('modalAddEnvironment').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg text-xs font-semibold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-500/30 hover:bg-blue-500/30 transition-all">
                    Add Environment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Add Version Modal --}}
<div id="modalAddVersion" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="document.getElementById('modalAddVersion').classList.add('hidden')"></div>
    <div class="relative max-w-md w-full glass rounded-2xl border border-white/10 p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold" style="color: var(--text-primary)">Tag Release Version</h3>
            <button type="button" onclick="document.getElementById('modalAddVersion').classList.add('hidden')"
                class="text-white/40 hover:text-white transition-colors text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('dashboard.api_management.versions.store', $api) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Version *</label>
                    <input type="text" name="version" required placeholder="1.0.0"
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-pink-500 text-sm">
                </div>
                <div>
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Status *</label>
                    <select name="status" required
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-pink-500 text-sm">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="deprecated">Deprecated</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="text-white/60 text-xs uppercase tracking-wide block mb-1">Release Date</label>
                    <input type="date" name="release_date"
                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-pink-500 text-sm">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-1">
                <button type="button" onclick="document.getElementById('modalAddVersion').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg text-xs font-semibold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg text-xs font-semibold bg-pink-500/20 text-pink-300 border border-pink-500/30 hover:bg-pink-500/30 transition-all">
                    Tag Version
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
