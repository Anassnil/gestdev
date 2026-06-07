@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-[#01020a] text-white relative overflow-hidden" style="background-color: var(--bg-surface); color: var(--text-primary);">
        <div class="absolute top-[-10%] left-[-5%] w-96 h-96 bg-[#140152] rounded-full blur-[120px] opacity-40"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-96 h-96 bg-[#22007C] rounded-full blur-[120px] opacity-30"></div>

        <div class="pt-6 sm:pt-12 md:pt-20 px-2 sm:px-4 md:px-6">
            <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6">
                <!-- Sidebar - Hidden on mobile, visible on desktop -->
                <aside class="hidden md:block md:col-span-3">
                    <div class="glass rounded-2xl p-4 sticky top-24">
                        <button id="open-profile-modal-desktop" type="button" class="w-full flex items-center gap-3 mb-6 px-2 py-2 rounded-xl hover:bg-white/5 border border-transparent hover:border-white/10 transition-all text-left" aria-label="Open profile summary">
                            @if(Auth::user()?->avatar_url)
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-10 h-10 rounded-lg object-cover border border-white/15">
                            @else
                                <div class="w-10 h-10 bg-gradient-to-br from-[#0D00A4] to-[#22007C] rounded-lg flex items-center justify-center text-xs font-black tracking-wide text-white/90">
                                    {{ Auth::user()?->initials ?? 'U' }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-sm sm:text-base truncate">{{ Auth::user()->name ?? 'User' }}</div>
                                <div class="text-xs truncate" style="color: var(--text-muted);">{{ Auth::user()->email ?? '' }}</div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <nav class="space-y-1">
                            <a href="/dashboard" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Overview</a>
                            <a href="{{ route('dashboard.planning.index') }}" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Planning</a>
                            @if(isset($board))
                                <a href="{{ route('dashboard.planning.requirements', $board) }}" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Requirements</a>
                            @else
                                <a href="{{ route('dashboard.planning.index') }}" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Requirements</a>
                            @endif
                            @if(isset($board))
                                <a href="{{ route('dashboard.planning.project_tracking', $board) }}" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Project Tracking</a>
                            @else
                                <a href="{{ route('dashboard.planning.index') }}" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Project Tracking</a>
                            @endif
                            <!-- AI Model Management link removed per request -->
                            <a href="{{ route('dashboard.code_repository.index') }}" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Code Repository</a>
                            <a href="{{ route('dashboard.api_management.index') }}" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">API Management</a>
                            <a href="#" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Test Automation</a>
                            <a href="#" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Analytics</a>
                            <a href="{{ route('dashboard.people.index') }}" class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">
                                <span>Community</span>
                                <span id="sidebar-unread-badge" class="hidden text-[10px] font-black bg-blue-600 text-white rounded-full px-1.5 py-0.5 min-w-[1.1rem] text-center leading-none"></span>
                            </a>
                            <a href="{{ route('dashboard.settings') }}" class="block px-3 py-2 rounded-md hover:bg-white/5 text-sm transition-all" style="color: var(--text-secondary);">Settings</a>
                        </nav>

                        <!-- Theme Toggle -->
                        <div class="mt-4 flex items-center justify-between px-3 py-2">
                            <span class="text-xs font-semibold" style="color: var(--text-faint);">Theme</span>
                            <button onclick="toggleTheme()" class="theme-toggle" aria-label="Toggle theme">
                                <span class="toggle-thumb">
                                    <span id="theme-icon-dark" style="display:none;">🌙</span>
                                    <span id="theme-icon-light" style="display:none;">☀️</span>
                                </span>
                            </button>
                        </div>
                        <script>
                            (function(){
                                var t = document.documentElement.getAttribute('data-theme') || 'dark';
                                document.getElementById('theme-icon-' + t).style.display = 'inline';
                                // Observe changes
                                var obs = new MutationObserver(function() {
                                    var c = document.documentElement.getAttribute('data-theme');
                                    document.getElementById('theme-icon-dark').style.display = c === 'dark' ? 'inline' : 'none';
                                    document.getElementById('theme-icon-light').style.display = c === 'light' ? 'inline' : 'none';
                                });
                                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
                            })();
                        </script>

                        <div class="mt-4">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="w-full px-3 py-2 bg-[#0D00A4] rounded-md font-semibold text-white text-sm hover:bg-[#1a00d4] transition-all">Sign out</button>
                            </form>
                        </div>
                    </div>
                </aside>

                <!-- Main Content - Full width on mobile, col-span-9 on desktop -->
                <main class="col-span-1 md:col-span-9">
                    <!-- Mobile Header (visible only on mobile/tablet) -->
                    <div class="md:hidden mb-4">
                        <div class="glass rounded-2xl p-3 sm:p-4">
                            <div class="flex items-center justify-between mb-4">
                                <button id="open-profile-modal-mobile" type="button" class="flex items-center gap-2 text-left px-1 py-1 rounded-lg hover:bg-white/5 transition-all" aria-label="Open profile summary">
                                    @if(Auth::user()?->avatar_url)
                                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-lg object-cover border border-white/15">
                                    @else
                                        <div class="w-8 h-8 bg-gradient-to-br from-[#0D00A4] to-[#22007C] rounded-lg flex items-center justify-center text-[10px] font-black tracking-wide text-white/90">
                                            {{ Auth::user()?->initials ?? 'U' }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-sm">{{ Auth::user()->name ?? 'User' }}</div>
                                        <div class="text-xs" style="color: var(--text-muted);">{{ Auth::user()->email ?? '' }}</div>
                                    </div>
                                </button>
                                <button onclick="toggleTheme()" class="theme-toggle" aria-label="Toggle theme">
                                    <span class="toggle-thumb">
                                        <span id="mobile-theme-icon-dark" style="display:none;">🌙</span>
                                        <span id="mobile-theme-icon-light" style="display:none;">☀️</span>
                                    </span>
                                </button>
                            </div>
                            <script>
                                (function(){
                                    var t = document.documentElement.getAttribute('data-theme') || 'dark';
                                    document.getElementById('mobile-theme-icon-' + t).style.display = 'inline';
                                    // Observe changes
                                    var obs = new MutationObserver(function() {
                                        var c = document.documentElement.getAttribute('data-theme');
                                        document.getElementById('mobile-theme-icon-dark').style.display = c === 'dark' ? 'inline' : 'none';
                                        document.getElementById('mobile-theme-icon-light').style.display = c === 'light' ? 'inline' : 'none';
                                    });
                                    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
                                })();
                            </script>
                            <!-- Mobile Navigation -->
                            <nav class="grid grid-cols-2 gap-2">
                                <a href="/dashboard" class="px-2 py-1.5 rounded-md hover:bg-white/5 text-xs transition-all text-center" style="color: var(--text-secondary);">Overview</a>
                                <a href="{{ route('dashboard.planning.index') }}" class="px-2 py-1.5 rounded-md hover:bg-white/5 text-xs transition-all text-center" style="color: var(--text-secondary);">Planning</a>
                                @if(Route::has('ai.models.index'))
                                    <a href="{{ route('ai.models.index') }}" class="px-2 py-1.5 rounded-md hover:bg-white/5 text-xs transition-all text-center" style="color: var(--text-secondary);">AI Models</a>
                                @endif
                                <a href="{{ route('dashboard.people.index') }}" class="px-2 py-1.5 rounded-md hover:bg-white/5 text-xs transition-all text-center" style="color: var(--text-secondary);">Community</a>
                                <a href="{{ route('dashboard.settings') }}" class="px-2 py-1.5 rounded-md hover:bg-white/5 text-xs transition-all text-center" style="color: var(--text-secondary);">Settings</a>
                                <form method="POST" action="{{ route('logout') }}" class="contents">
                                    @csrf
                                    <button class="px-2 py-1.5 bg-[#0D00A4] rounded-md font-semibold text-white text-xs hover:bg-[#1a00d4] transition-all">Sign out</button>
                                </form>
                            </nav>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="flash-success mb-4 px-4 py-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-200 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="flash-error mb-4 px-4 py-3 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-200 text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 px-4 py-3 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-100 text-sm">
                            <p class="font-semibold mb-1">Please fix the following:</p>
                            <ul class="list-disc pl-5 space-y-0.5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('dashboard-content')
                </main>
            </div>
        </div>

        @auth
        <div id="profile-modal" class="hidden fixed inset-0 z-50">
            <div id="profile-modal-backdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
                <div class="w-full max-w-xl glass rounded-2xl border border-white/10 shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between">
                        <h2 class="text-lg font-bold">Your Profile</h2>
                        <button id="close-profile-modal" type="button" class="p-2 rounded-lg hover:bg-white/10 text-white/60 hover:text-white transition-all" aria-label="Close profile popup">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="flex items-center gap-3">
                            @if(Auth::user()?->avatar_url)
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-14 h-14 rounded-xl object-cover border border-white/15">
                            @else
                                <div class="w-14 h-14 bg-gradient-to-br from-[#0D00A4] to-[#22007C] rounded-xl flex items-center justify-center text-sm font-black tracking-wide text-white/90">
                                    {{ Auth::user()?->initials ?? 'U' }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="font-extrabold text-lg truncate">{{ Auth::user()->name ?? 'User' }}</div>
                                <div class="text-sm text-white/60 truncate">{{ Auth::user()->email ?? '' }}</div>
                                @if(Auth::user()?->position)
                                    <div class="text-xs text-blue-300 mt-0.5">{{ Auth::user()->position }}</div>
                                @endif
                            </div>
                        </div>

                        @if(Auth::user()?->bio)
                            <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-3">
                                <p class="text-xs font-semibold text-white/50 mb-1">Bio</p>
                                <p class="text-sm text-white/80 leading-relaxed">{{ Auth::user()->bio }}</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-3">
                                <p class="text-xs font-semibold text-white/50 mb-1">Role</p>
                                <p class="text-sm text-white/80">{{ Auth::user()?->position ?: 'Not set yet' }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-3">
                                <p class="text-xs font-semibold text-white/50 mb-1">Member Since</p>
                                <p class="text-sm text-white/80">{{ Auth::user()?->created_at?->format('F Y') ?: '-' }}</p>
                            </div>
                        </div>

                        @if(!empty(Auth::user()?->tech_stack))
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
                            <div>
                                <p class="text-xs font-semibold text-white/50 mb-2">Tech Stack</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(Auth::user()->tech_stack as $idx => $tech)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs border {{ $tagColors[$idx % count($tagColors)] }}">{{ $tech }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-2 pt-2">
                            <a href="{{ route('dashboard.settings') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-all">Open Settings</a>
                            <a href="{{ route('dashboard.people.show', Auth::user()) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-white/90 font-semibold transition-all">View Full Profile</a>
                            @if(Auth::user()?->github_url)
                                <a href="{{ Auth::user()->github_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-white/80 transition-all">GitHub</a>
                            @endif
                            @if(Auth::user()?->linkedin_url)
                                <a href="{{ Auth::user()->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-white/80 transition-all">LinkedIn</a>
                            @endif
                            @if(Auth::user()?->twitter_url)
                                <a href="{{ Auth::user()->twitter_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-white/80 transition-all">Twitter/X</a>
                            @endif
                            @if(Auth::user()?->instagram_url)
                                <a href="{{ Auth::user()->instagram_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-white/80 transition-all">Instagram</a>
                            @endif
                            @if(Auth::user()?->facebook_url)
                                <a href="{{ Auth::user()->facebook_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-white/80 transition-all">Facebook</a>
                            @endif
                            @if(Auth::user()?->website_url)
                                <a href="{{ Auth::user()->website_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-white/80 transition-all">Website</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endauth
    </div>

    {{-- Allow pages to push custom scripts (charts, interactivity) --}}
    @yield('scripts')

    @auth
    <script>
    (function () {
        const modal = document.getElementById('profile-modal');
        const backdrop = document.getElementById('profile-modal-backdrop');
        const closeBtn = document.getElementById('close-profile-modal');
        const openDesktop = document.getElementById('open-profile-modal-desktop');
        const openMobile = document.getElementById('open-profile-modal-mobile');

        if (!modal) return;

        function openModal() {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        openDesktop?.addEventListener('click', openModal);
        openMobile?.addEventListener('click', openModal);
        closeBtn?.addEventListener('click', closeModal);
        backdrop?.addEventListener('click', closeModal);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
    </script>
    @endauth

    {{-- ═══════════════════ FLOATING CHAT WIDGET ═══════════════════ --}}
    @auth
    {{-- FAB button --}}
    <button id="fcw-fab"
        aria-label="Open chat"
        class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 shadow-2xl flex items-center justify-center transition-all duration-200 hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-transparent">
        {{-- Chat icon (shown when closed) --}}
        <svg id="fcw-icon-open" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 16V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h11l4 4v-4z"/>
        </svg>
        {{-- Close icon (shown when open) --}}
        <svg id="fcw-icon-close" class="w-6 h-6 text-white hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        {{-- Unread dot --}}
        <span id="fcw-fab-badge" class="hidden absolute -top-1 -right-1 min-w-[1.1rem] h-[1.1rem] px-1 bg-red-500 text-white text-[9px] font-black rounded-full flex items-center justify-center leading-none"></span>
    </button>

    {{-- Floating chat panel --}}
    <div id="fcw-panel"
        class="fixed z-40 hidden flex-col rounded-2xl border border-white/10 shadow-2xl overflow-hidden"
        style="width:360px; height:480px; bottom:5.5rem; right:1.5rem; background: rgba(10,10,30,0.92); backdrop-filter: blur(18px);">

        {{-- Drag handle / header --}}
        <div id="fcw-header"
            class="flex items-center justify-between px-4 py-3 border-b border-white/10 cursor-grab select-none shrink-0"
            style="background: rgba(13,0,164,0.25);">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 16V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h11l4 4v-4z"/>
                </svg>
                <span id="fcw-title" class="font-bold text-sm text-white">Messages</span>
            </div>
            <div class="flex items-center gap-1">
                {{-- Back button (chat → inbox) --}}
                <button id="fcw-back" class="hidden p-1.5 rounded-lg hover:bg-white/10 text-white/50 hover:text-white transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                {{-- Open in full page --}}
                <a id="fcw-expand" href="{{ route('dashboard.people.inbox') }}" target="_self"
                   class="p-1.5 rounded-lg hover:bg-white/10 text-white/50 hover:text-white transition-all" title="Open full page">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
                {{-- Close --}}
                <button id="fcw-close" class="p-1.5 rounded-lg hover:bg-white/10 text-white/50 hover:text-white transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Inbox view --}}
        <div id="fcw-inbox" class="flex-1 overflow-y-auto">
            <div id="fcw-inbox-list" class="divide-y divide-white/5"></div>
            <div id="fcw-inbox-empty" class="hidden flex flex-col items-center justify-center h-32 text-white/30 gap-2">
                <p class="text-sm">No conversations yet</p>
                <a href="{{ route('dashboard.people.index') }}" class="text-xs text-blue-400 hover:text-blue-300">Browse community →</a>
            </div>
            <div id="fcw-inbox-loading" class="flex items-center justify-center h-20 text-white/30">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>
        </div>

        {{-- Chat view (hidden initially) --}}
        <div id="fcw-chat" class="hidden flex-col flex-1 overflow-hidden">
            {{-- Messages --}}
            <div id="fcw-messages" class="flex-1 overflow-y-auto p-3 space-y-2"></div>
            {{-- Send form --}}
            <form id="fcw-form" class="flex gap-2 p-3 border-t border-white/10 shrink-0">
                <input id="fcw-input" type="text" placeholder="Write a message…" autocomplete="off"
                    class="flex-1 px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-sm placeholder-white/30 focus:outline-none focus:border-blue-500">
                <button type="submit" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const csrf     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const fab      = document.getElementById('fcw-fab');
        const panel    = document.getElementById('fcw-panel');
        const header   = document.getElementById('fcw-header');
        const iconOpen = document.getElementById('fcw-icon-open');
        const iconClose= document.getElementById('fcw-icon-close');
        const fabBadge = document.getElementById('fcw-fab-badge');
        const sidebarBadge = document.getElementById('sidebar-unread-badge');

        // Views
        const inboxView   = document.getElementById('fcw-inbox');
        const inboxList   = document.getElementById('fcw-inbox-list');
        const inboxEmpty  = document.getElementById('fcw-inbox-empty');
        const inboxLoading= document.getElementById('fcw-inbox-loading');
        const chatView    = document.getElementById('fcw-chat');
        const messagesBox = document.getElementById('fcw-messages');
        const chatForm    = document.getElementById('fcw-form');
        const chatInput   = document.getElementById('fcw-input');
        const backBtn     = document.getElementById('fcw-back');
        const expandLink  = document.getElementById('fcw-expand');
        const closeBtn    = document.getElementById('fcw-close');
        const titleEl     = document.getElementById('fcw-title');

        let isOpen      = false;
        let activeUser  = null; // {id, name}
        let lastMsgId   = 0;
        let pollTimer   = null;

        // ── Open / close ────────────────────────────────────────────
        function openPanel() {
            isOpen = true;
            panel.classList.remove('hidden');
            panel.classList.add('flex');
            iconOpen.classList.add('hidden');
            iconClose.classList.remove('hidden');
            loadInbox();
        }

        function closePanel() {
            isOpen = false;
            panel.classList.add('hidden');
            panel.classList.remove('flex');
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
            stopPoll();
        }

        fab.addEventListener('click', () => isOpen ? closePanel() : openPanel());
        closeBtn.addEventListener('click', closePanel);

        // ── Inbox ────────────────────────────────────────────────────
        async function loadInbox() {
            showInboxView();
            inboxLoading.classList.remove('hidden');
            inboxList.innerHTML = '';
            inboxEmpty.classList.add('hidden');

            try {
                const res  = await fetch('/dashboard/people/inbox-json', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const data = await res.json();
                inboxLoading.classList.add('hidden');

                if (!data.threads || data.threads.length === 0) {
                    inboxEmpty.classList.remove('hidden');
                    return;
                }

                data.threads.forEach(t => {
                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'w-full flex items-center gap-3 px-4 py-3 hover:bg-white/5 transition-all text-left';
                    const avatarHtml = t.avatar
                        ? `<img src="${t.avatar}" class="w-9 h-9 rounded-xl object-cover border border-white/15 shrink-0">`
                        : `<div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-xs font-black text-white shrink-0">${escHtml(t.initials)}</div>`;
                    row.innerHTML = `
                        ${avatarHtml}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-white truncate">${escHtml(t.name)}</span>
                                <span class="text-[10px] text-white/30 shrink-0 ml-1">${escHtml(t.ago)}</span>
                            </div>
                            <div class="text-xs text-white/40 truncate mt-0.5">${escHtml(t.preview)}</div>
                        </div>
                        ${t.unread > 0 ? `<span class="shrink-0 min-w-[1.1rem] h-[1.1rem] px-1 bg-blue-600 text-white text-[9px] font-black rounded-full flex items-center justify-center">${t.unread}</span>` : ''}
                    `;
                    row.addEventListener('click', () => openChat(t.id, t.name));
                    inboxList.appendChild(row);
                });
            } catch(e) {
                inboxLoading.classList.add('hidden');
                inboxList.innerHTML = '<p class="text-xs text-white/30 text-center py-6">Could not load messages</p>';
            }
        }

        // ── Chat ─────────────────────────────────────────────────────
        async function openChat(userId, userName) {
            activeUser = { id: userId, name: userName };
            lastMsgId  = 0;

            showChatView(userName);
            messagesBox.innerHTML = '<div class="flex justify-center py-4"><svg class="w-5 h-5 animate-spin text-white/30" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></div>';
            expandLink.href = `/dashboard/people/${userId}/chat`;

            try {
                const res  = await fetch(`/dashboard/people/${userId}/poll?after=0`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const data = await res.json();
                messagesBox.innerHTML = '';
                (data.messages || []).forEach(m => { appendMsg(m); lastMsgId = Math.max(lastMsgId, m.id); });
                scrollBottom();
            } catch(e) {
                messagesBox.innerHTML = '<p class="text-xs text-white/30 text-center py-4">Could not load messages</p>';
            }

            startPoll();
        }

        backBtn.addEventListener('click', () => {
            stopPoll();
            activeUser = null;
            expandLink.href = '{{ route("dashboard.people.inbox") }}';
            loadInbox();
        });

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const body = chatInput.value.trim();
            if (!body || !activeUser) return;
            chatInput.value = '';

            try {
                const res  = await fetch(`/dashboard/people/${activeUser.id}/message`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ body }),
                });
                const data = await res.json();
                if (data.id) { appendMsg(data); lastMsgId = Math.max(lastMsgId, data.id); scrollBottom(); }
            } catch(e) {}
        });

        // Enter to send
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); chatForm.dispatchEvent(new Event('submit', { cancelable: true })); }
        });

        function startPoll() {
            stopPoll();
            pollTimer = setInterval(async () => {
                if (!activeUser) return;
                try {
                    const res  = await fetch(`/dashboard/people/${activeUser.id}/poll?after=${lastMsgId}`, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                    });
                    const data = await res.json();
                    let newMsgs = false;
                    (data.messages || []).forEach(m => {
                        if (!document.querySelector(`[data-fcw-id="${m.id}"]`)) {
                            appendMsg(m); lastMsgId = Math.max(lastMsgId, m.id); newMsgs = true;
                        }
                    });
                    if (newMsgs) scrollBottom();
                } catch(e) {}
            }, 3000);
        }

        function stopPoll() { clearInterval(pollTimer); pollTimer = null; }

        function appendMsg(m) {
            const div = document.createElement('div');
            div.className = `flex ${m.mine ? 'justify-end' : 'justify-start'}`;
            div.setAttribute('data-fcw-id', m.id);
            const time = new Date(m.created_at);
            const hhmm = time.getHours().toString().padStart(2,'0') + ':' + time.getMinutes().toString().padStart(2,'0');
            div.innerHTML = `
                <div class="max-w-[80%]">
                    <div class="px-3 py-2 rounded-xl text-sm leading-relaxed ${m.mine ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-white/10 text-white/90 rounded-bl-sm'}">${escHtml(m.body)}</div>
                    <div class="text-[9px] text-white/25 mt-0.5 ${m.mine ? 'text-right' : 'text-left'}">${hhmm}</div>
                </div>`;
            messagesBox.appendChild(div);
        }

        function scrollBottom() { messagesBox.scrollTop = messagesBox.scrollHeight; }

        // ── View helpers ─────────────────────────────────────────────
        function showInboxView() {
            inboxView.classList.remove('hidden');
            inboxView.classList.add('flex-1', 'overflow-y-auto');
            chatView.classList.add('hidden');
            chatView.classList.remove('flex');
            backBtn.classList.add('hidden');
            titleEl.textContent = 'Messages';
            expandLink.href = '{{ route("dashboard.people.inbox") }}';
        }

        function showChatView(name) {
            inboxView.classList.add('hidden');
            inboxView.classList.remove('flex-1', 'overflow-y-auto');
            chatView.classList.remove('hidden');
            chatView.classList.add('flex');
            backBtn.classList.remove('hidden');
            titleEl.textContent = name;
        }

        // ── Drag ─────────────────────────────────────────────────────
        let dragging = false, ox = 0, oy = 0;

        header.addEventListener('mousedown', (e) => {
            if (e.target.closest('button, a')) return;
            dragging = true;
            header.style.cursor = 'grabbing';
            const rect = panel.getBoundingClientRect();
            ox = e.clientX - rect.left;
            oy = e.clientY - rect.top;
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!dragging) return;
            let x = e.clientX - ox;
            let y = e.clientY - oy;
            // Clamp to viewport
            x = Math.max(0, Math.min(x, window.innerWidth  - panel.offsetWidth));
            y = Math.max(0, Math.min(y, window.innerHeight - panel.offsetHeight));
            panel.style.left   = x + 'px';
            panel.style.top    = y + 'px';
            panel.style.right  = 'auto';
            panel.style.bottom = 'auto';
        });

        document.addEventListener('mouseup', () => {
            dragging = false;
            header.style.cursor = 'grab';
        });

        // Touch drag support
        header.addEventListener('touchstart', (e) => {
            if (e.target.closest('button, a')) return;
            const t = e.touches[0];
            const rect = panel.getBoundingClientRect();
            ox = t.clientX - rect.left;
            oy = t.clientY - rect.top;
        }, { passive: true });

        header.addEventListener('touchmove', (e) => {
            const t = e.touches[0];
            let x = t.clientX - ox;
            let y = t.clientY - oy;
            x = Math.max(0, Math.min(x, window.innerWidth  - panel.offsetWidth));
            y = Math.max(0, Math.min(y, window.innerHeight - panel.offsetHeight));
            panel.style.left   = x + 'px';
            panel.style.top    = y + 'px';
            panel.style.right  = 'auto';
            panel.style.bottom = 'auto';
            e.preventDefault();
        }, { passive: false });

        // ── Unread badge (shared with sidebar poller) ─────────────────
        function refreshBadge() {
            fetch('/dashboard/people/unread', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            })
            .then(r => r.json())
            .then(data => {
                const count = data.count || 0;
                const label = count > 99 ? '99+' : (count > 0 ? String(count) : '');
                [fabBadge, sidebarBadge].forEach(el => {
                    if (!el) return;
                    if (count > 0) { el.textContent = label; el.classList.remove('hidden'); }
                    else           { el.classList.add('hidden'); }
                });
            })
            .catch(() => {});
        }

        refreshBadge();
        setInterval(refreshBadge, 30000);

        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
    })();
    </script>
    @endauth
@endsection
