@extends('layouts.dashboard')

@push('styles')
<style>
    .profile-card {
        box-shadow: 0 10px 30px rgba(2, 6, 23, 0.12);
    }

    .profile-chip {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .profile-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.14);
    }

    [data-theme="light"] .social-ig:hover { color: #c13584 !important; }
    [data-theme="light"] .social-x:hover { color: #0ea5e9 !important; }
    [data-theme="light"] .social-fb:hover { color: #1877f2 !important; }
    [data-theme="light"] .social-gh:hover { color: #181717 !important; }
    [data-theme="light"] .social-li:hover { color: #0a66c2 !important; }
    [data-theme="light"] .social-web:hover { color: #10b981 !important; }
</style>
@endpush

@section('dashboard-content')
@php
    $sharedSkills = $sharedSkills ?? collect();
    $compatibilityScore = $compatibilityScore ?? 0;
    $totalMessages = $totalMessages ?? 0;
    $profileCompleteness = $profileCompleteness ?? 0;
@endphp

<div class="space-y-6 p-2 max-w-5xl">

    {{-- Back nav --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard.people.index') }}" class="p-2 rounded-lg hover:bg-white/10 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-extrabold tracking-tight">Profile</h1>
    </div>

    {{-- Hero card --}}
    <div class="glass rounded-2xl border border-white/10 overflow-hidden profile-card">
        {{-- Banner gradient --}}
        <div class="relative h-28 bg-gradient-to-r from-[#0D00A4]/60 via-[#22007C]/50 to-[#0D00A4]/30">
            <div class="absolute left-6 bottom-0 translate-y-1/2 z-10">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                         class="w-20 h-20 rounded-2xl object-cover border-4 border-white/90 shadow-lg shrink-0">
                @else
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-2xl font-black text-white/90 shrink-0 border-4 border-white/90 shadow-lg">
                        {{ $user->initials }}
                    </div>
                @endif
            </div>
        </div>

        <div class="px-6 pb-6 pt-12">
            <div class="grid grid-cols-1 xl:grid-cols-[1fr_260px] gap-6">
                <div>
                    @if(auth()->id() === $user->id)
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-[11px] text-white/50 mb-1">
                                <span>Profile completeness</span>
                                <span class="font-semibold">{{ $profileCompleteness }}%</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                                <div class="h-full bg-blue-500" style="width: {{ $profileCompleteness }}%"></div>
                            </div>
                        </div>
                    @endif

                    {{-- Name + position --}}
                    <div>
                        <h2 class="text-3xl font-extrabold leading-tight">{{ $user->name }}</h2>
                        @if($user->position)
                            <p class="text-blue-500 font-semibold text-base mt-0.5">{{ $user->position }}</p>
                        @endif
                        <p class="text-white/40 text-sm mt-1">Member since {{ $user->created_at->format('F Y') }}</p>
                    </div>

                    {{-- Bio --}}
                    @if($user->bio)
                        <p class="mt-4 text-white/70 text-sm leading-relaxed max-w-2xl">{{ $user->bio }}</p>
                    @endif

                    {{-- Stats --}}
                    <div class="mt-5 pt-4 border-t border-white/10 grid grid-cols-3 gap-3 max-w-md">
                        <div>
                            <p class="text-3xl font-black leading-none">{{ $totalMessages }}</p>
                            <p class="text-[11px] uppercase tracking-widest text-white/40 mt-1">Messages</p>
                        </div>
                        <div>
                            <p class="text-3xl font-black leading-none">{{ $sharedSkills->count() }}</p>
                            <p class="text-[11px] uppercase tracking-widest text-white/40 mt-1">Shared Skills</p>
                        </div>
                        <div>
                            <p class="text-3xl font-black leading-none text-blue-500">{{ $compatibilityScore }}%</p>
                            <p class="text-[11px] uppercase tracking-widest text-white/40 mt-1">Compatibility Score</p>
                        </div>
                    </div>

                    {{-- Tech stack tags --}}
                    @if(!empty($user->tech_stack))
                        @php
                            $tagColors = [
                                'bg-blue-600/20 text-blue-300 border-blue-500/30',
                                'bg-violet-600/20 text-violet-300 border-violet-500/30',
                                'bg-emerald-600/20 text-emerald-300 border-emerald-500/30',
                                'bg-amber-600/20 text-amber-300 border-amber-500/30',
                                'bg-rose-600/20 text-rose-300 border-rose-500/30',
                                'bg-cyan-600/20 text-cyan-300 border-cyan-500/30',
                            ];
                        @endphp
                        <div class="mt-5">
                            <p class="text-xs uppercase tracking-widest text-white/40 mb-2">Tech Stack</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->tech_stack as $idx => $tag)
                                    <span class="profile-chip inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $tagColors[$idx % count($tagColors)] }}">
                                        @if($sharedSkills->contains($tag))
                                            <span>★</span>
                                        @endif
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                            @if($sharedSkills->count() > 0)
                                <p class="text-[11px] mt-2 text-white/40">★ = skills you share</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Right panel: actions + ai insight --}}
                <div class="xl:pt-10 flex flex-col gap-3">
                    <div class="glass rounded-xl border border-white/10 p-3">
                        <p class="text-[11px] uppercase tracking-widest text-white/40 mb-2">Quick Actions</p>
                        <a href="{{ route('dashboard.people.chat', $user) }}"
                           class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-xl font-semibold text-sm transition-all mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 16V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h11l4 4v-4z"/>
                            </svg>
                            Message
                        </a>

                        <div class="flex flex-wrap gap-2">
                @if($user->instagram_url)
                    <a href="{{ $user->instagram_url }}" target="_blank" rel="noopener noreferrer" class="social-ig p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-white/60 hover:text-fuchsia-500">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7.75 2h8.5A5.75 5.75 0 0122 7.75v8.5A5.75 5.75 0 0116.25 22h-8.5A5.75 5.75 0 012 16.25v-8.5A5.75 5.75 0 017.75 2zm-.25 2A3.5 3.5 0 004 7.5v9A3.5 3.5 0 007.5 20h9a3.5 3.5 0 003.5-3.5v-9A3.5 3.5 0 0016.5 4h-9zm9.75 1.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zM12 7a5 5 0 110 10 5 5 0 010-10zm0 2a3 3 0 100 6 3 3 0 000-6z"/></svg>
                    </a>
                @endif
                @if($user->twitter_url)
                    <a href="{{ $user->twitter_url }}" target="_blank" rel="noopener noreferrer" class="social-x p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-white/60 hover:text-sky-500">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2H21l-6.56 7.497L22.154 22h-6.037l-4.728-6.185L5.97 22H3.21l7.017-8.018L2 2h6.19l4.274 5.649L18.244 2zm-1.06 18h1.673L7.285 3.896H5.49L17.184 20z"/></svg>
                    </a>
                @endif
                @if($user->facebook_url)
                    <a href="{{ $user->facebook_url }}" target="_blank" rel="noopener noreferrer" class="social-fb p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-white/60 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6H16.7V4.8c-.3 0-1.4-.1-2.6-.1-2.6 0-4.3 1.6-4.3 4.5V11H7v3h2.8v8h3.7z"/></svg>
                    </a>
                @endif
                @if($user->github_url)
                    <a href="{{ $user->github_url }}" target="_blank" rel="noopener noreferrer" class="social-gh p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-white/60 hover:text-[#181717]">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                    </a>
                @endif
                @if($user->linkedin_url)
                    <a href="{{ $user->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="social-li p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-white/60 hover:text-[#0A66C2]">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                @endif
                @if($user->website_url)
                    <a href="{{ $user->website_url }}" target="_blank" rel="noopener noreferrer" class="social-web p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-white/60 hover:text-emerald-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    </a>
                @endif
                        </div>
                    </div>

                    <div class="glass rounded-xl border border-blue-500/20 bg-blue-600/10 p-4">
                        <p class="text-[11px] uppercase tracking-widest text-blue-400 mb-2">AI Insight</p>
                        <h3 class="font-bold text-sm mb-1">
                            @if($compatibilityScore >= 75)
                                Great Collaboration Fit
                            @elseif($compatibilityScore >= 45)
                                Potential Match
                            @else
                                Different Strengths
                            @endif
                        </h3>
                        <p class="text-xs text-white/60 leading-relaxed">
                            @if($compatibilityScore >= 75)
                                Strong overlap in skills and profile signals. Good candidate for direct collaboration.
                            @elseif($compatibilityScore >= 45)
                                You have partial overlap. A chat could reveal complementary strengths.
                            @else
                                Distinct profiles can still create value through cross-domain collaboration.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Experience timeline --}}
        @if(!empty($user->experience))
            <div class="glass rounded-2xl border border-white/10 p-6">
                <h3 class="font-bold text-sm uppercase tracking-wider text-white/50 mb-5 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Experience
                </h3>
                <div class="space-y-5">
                    @foreach($user->experience ?? [] as $exp)
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                                @if(!$loop->last)
                                    <div class="w-px flex-1 bg-white/10 mt-1"></div>
                                @endif
                            </div>
                            <div class="pb-4 flex-1 min-w-0">
                                <p class="font-bold text-sm">{{ $exp['role'] ?? '' }}</p>
                                <p class="text-white/60 text-sm">{{ $exp['company'] ?? '' }}</p>
                                @if(!empty($exp['period']))
                                    <p class="text-white/30 text-xs mt-0.5">{{ $exp['period'] }}</p>
                                @endif
                                @if(!empty($exp['description']))
                                    <p class="text-white/50 text-xs mt-1.5 leading-relaxed">{{ $exp['description'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Education timeline --}}
        @if(!empty($user->education))
            <div class="glass rounded-2xl border border-white/10 p-6">
                <h3 class="font-bold text-sm uppercase tracking-wider text-white/50 mb-5 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>
                    Education
                </h3>
                <div class="space-y-5">
                    @foreach($user->education ?? [] as $edu)
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-violet-500 mt-1.5 shrink-0"></div>
                                @if(!$loop->last)
                                    <div class="w-px flex-1 bg-white/10 mt-1"></div>
                                @endif
                            </div>
                            <div class="pb-4 flex-1 min-w-0">
                                <p class="font-bold text-sm">{{ $edu['degree'] ?? '' }}</p>
                                <p class="text-white/60 text-sm">{{ $edu['institution'] ?? '' }}</p>
                                @if(!empty($edu['period']))
                                    <p class="text-white/30 text-xs mt-0.5">{{ $edu['period'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Recent conversation preview --}}
    @if($recentMessages->count() > 0)
        <div class="glass p-5 rounded-2xl border border-white/10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-sm uppercase tracking-wider text-white/50">Recent conversation</h3>
                <a href="{{ route('dashboard.people.chat', $user) }}" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">View all →</a>
            </div>
            <div class="space-y-2">
                @foreach($recentMessages as $msg)
                    @php $mine = $msg->sender_id === auth()->id(); @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[75%] px-4 py-2 rounded-xl text-sm {{ $mine ? 'bg-blue-600 text-white' : 'bg-white/10 text-white/90' }}">
                            {{ $msg->body }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
