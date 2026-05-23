@extends('layouts.dashboard')

@section('dashboard-content')
<div class="space-y-8 p-2">
    <div>
        <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Account Settings</h1>
        <p class="text-white/50 mt-1">Manage your profile and security settings.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-300 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="glass p-6 rounded-2xl border border-white/10 xl:col-span-2">
            <h2 class="text-lg font-bold mb-4">Profile Photo</h2>

            <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-5">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-2xl object-cover border border-white/15">
                @else
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-2xl font-black tracking-wide text-white/90">
                        {{ $user->initials }}
                    </div>
                @endif
                <div class="text-sm text-white/60">Use a JPG, PNG, or WEBP image up to 5MB.</div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <form method="POST" action="{{ route('dashboard.settings.avatar.update') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <input
                        type="file"
                        name="avatar"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        required
                        class="w-full sm:w-auto px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm text-white file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-blue-600 file:text-white"
                    >
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-all">
                        Upload Photo
                    </button>
                </form>

                @if($user->avatar_path)
                    <form method="POST" action="{{ route('dashboard.settings.avatar.remove') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 rounded-lg border border-white/15 hover:bg-white/10 text-white/90 font-semibold transition-all">
                            Remove Photo
                        </button>
                    </form>
                @endif
            </div>
        </section>

        <section class="glass p-6 rounded-2xl border border-white/10">
            <h2 class="text-lg font-bold mb-4">Profile</h2>
            <form method="POST" action="{{ route('dashboard.settings.profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="text-xs uppercase tracking-wide text-white/60">Full Name</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500"
                    >
                </div>

                <div>
                    <label class="text-xs uppercase tracking-wide text-white/60">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500"
                    >
                </div>

                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-all">
                    Save Profile
                </button>
            </form>
        </section>

        <section class="glass p-6 rounded-2xl border border-white/10">
            <h2 class="text-lg font-bold mb-4">Security</h2>
            <form method="POST" action="{{ route('dashboard.settings.password.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="text-xs uppercase tracking-wide text-white/60">Current Password</label>
                    <input
                        type="password"
                        name="current_password"
                        required
                        class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500"
                    >
                </div>

                <div>
                    <label class="text-xs uppercase tracking-wide text-white/60">New Password</label>
                    <input
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500"
                    >
                </div>

                <div>
                    <label class="text-xs uppercase tracking-wide text-white/60">Confirm New Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        minlength="8"
                        class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white focus:outline-none focus:border-blue-500"
                    >
                </div>

                <button type="submit" class="px-4 py-2 rounded-lg bg-white text-[#02010A] font-semibold hover:bg-blue-50 transition-all">
                    Update Password
                </button>
            </form>
        </section>

        {{-- ═══════════════════════════ PROFESSIONAL PROFILE ═══════════════════════════ --}}
        <section class="glass p-6 rounded-2xl border border-white/10 xl:col-span-2" id="section-professional">
            <h2 class="text-lg font-bold mb-1">Professional Profile</h2>
            <p class="text-white/40 text-sm mb-5">Your position, skills, experience, and public links.</p>

            <form method="POST" action="{{ route('dashboard.settings.professional.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                {{-- Position + Bio --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-white/60">Position / Job Title</label>
                        <input
                            type="text"
                            name="position"
                            value="{{ old('position', $user->position) }}"
                            placeholder="e.g. Full Stack Developer"
                            list="position-suggestions"
                            class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500"
                        >
                        <datalist id="position-suggestions">
                            <option>Full Stack Developer</option>
                            <option>Frontend Developer</option>
                            <option>Backend Developer</option>
                            <option>Software Engineer</option>
                            <option>DevOps Engineer</option>
                            <option>ML Engineer</option>
                            <option>Data Scientist</option>
                            <option>Mobile Developer</option>
                            <option>Embedded Systems Engineer</option>
                            <option>Cloud Architect</option>
                            <option>Cybersecurity Engineer</option>
                            <option>QA Engineer</option>
                            <option>Technical Lead</option>
                            <option>Engineering Manager</option>
                            <option>CTO</option>
                        </datalist>
                    </div>

                    <div>
                        <label class="text-xs uppercase tracking-wide text-white/60">About / Bio</label>
                        <textarea
                            name="bio"
                            rows="3"
                            maxlength="1000"
                            placeholder="A short description about yourself…"
                            class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 resize-none text-sm"
                        >{{ old('bio', $user->bio) }}</textarea>
                    </div>
                </div>

                {{-- Tech Stack picker --}}
                <div>
                    <label class="text-xs uppercase tracking-wide text-white/60">Tech Stack</label>
                    {{-- Hidden input that holds comma-separated value for form POST --}}
                    <input type="hidden" name="tech_stack" id="tech-stack-hidden"
                           value="{{ old('tech_stack', implode(', ', $user->tech_stack ?? [])) }}">

                    {{-- Selected tags row --}}
                    <div id="tech-selected"
                         class="mt-2 min-h-[2.5rem] flex flex-wrap gap-2 p-3 rounded-xl bg-white/5 border border-white/10"
                         aria-label="Selected technologies">
                        <span id="tech-empty-hint" class="text-xs text-white/25 self-center">Click technologies below to add them…</span>
                    </div>

                    {{-- Search + toggle row --}}
                    <div class="flex gap-2 mt-3">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                            <input
                                type="text"
                                id="tech-search"
                                placeholder="Search or type a custom technology…"
                                autocomplete="off"
                                class="w-full pl-9 pr-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm"
                            >
                        </div>
                        <button type="button" id="tech-toggle-btn"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white/60 hover:text-white text-xs font-semibold transition-all">
                            <svg id="tech-toggle-icon" class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span id="tech-toggle-label">Browse</span>
                        </button>
                    </div>

                    {{-- Preset grid (collapsible) --}}
                    <div id="tech-presets-wrapper" class="overflow-hidden transition-all duration-300" style="max-height:0;">
                        <div id="tech-presets" class="pt-3 flex flex-wrap gap-2"></div>
                    </div>
                </div>

                {{-- Social Links --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-white/60">GitHub URL</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-white/30">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                            </span>
                            <input type="url" name="github_url" value="{{ old('github_url', $user->github_url) }}" placeholder="https://github.com/you" class="w-full pl-9 pr-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-white/60">LinkedIn URL</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-white/30">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </span>
                            <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $user->linkedin_url) }}" placeholder="https://linkedin.com/in/you" class="w-full pl-9 pr-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-white/60">Personal Website</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-white/30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                            </span>
                            <input type="url" name="website_url" value="{{ old('website_url', $user->website_url) }}" placeholder="https://yoursite.com" class="w-full pl-9 pr-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-white/60">Twitter / X URL</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-white/30">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2H21l-6.56 7.497L22.154 22h-6.037l-4.728-6.185L5.97 22H3.21l7.017-8.018L2 2h6.19l4.274 5.649L18.244 2zm-1.06 18h1.673L7.285 3.896H5.49L17.184 20z"/></svg>
                            </span>
                            <input type="url" name="twitter_url" value="{{ old('twitter_url', $user->twitter_url) }}" placeholder="https://x.com/you" class="w-full pl-9 pr-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-white/60">Instagram URL</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-white/30">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7.75 2h8.5A5.75 5.75 0 0122 7.75v8.5A5.75 5.75 0 0116.25 22h-8.5A5.75 5.75 0 012 16.25v-8.5A5.75 5.75 0 017.75 2zm-.25 2A3.5 3.5 0 004 7.5v9A3.5 3.5 0 007.5 20h9a3.5 3.5 0 003.5-3.5v-9A3.5 3.5 0 0016.5 4h-9zm9.75 1.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zM12 7a5 5 0 110 10 5 5 0 010-10zm0 2a3 3 0 100 6 3 3 0 000-6z"/></svg>
                            </span>
                            <input type="url" name="instagram_url" value="{{ old('instagram_url', $user->instagram_url) }}" placeholder="https://instagram.com/you" class="w-full pl-9 pr-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-white/60">Facebook URL</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-white/30">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6H16.7V4.8c-.3 0-1.4-.1-2.6-.1-2.6 0-4.3 1.6-4.3 4.5V11H7v3h2.8v8h3.7z"/></svg>
                            </span>
                            <input type="url" name="facebook_url" value="{{ old('facebook_url', $user->facebook_url) }}" placeholder="https://facebook.com/you" class="w-full pl-9 pr-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Experience --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-xs uppercase tracking-wide text-white/60">Experience</label>
                        <button type="button" onclick="addExperience()" class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Add entry
                        </button>
                    </div>
                    <div id="experience-list" class="space-y-3">
                        @foreach(old('experience', $user->experience ?? []) as $i => $exp)
                            <div class="experience-entry grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-xl bg-white/5 border border-white/10 relative">
                                <button type="button" onclick="this.closest('.experience-entry').remove()" class="absolute top-3 right-3 text-white/30 hover:text-red-400 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <input type="text" name="experience[{{ $i }}][role]" value="{{ $exp['role'] ?? '' }}" placeholder="Role / Position" required class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                                <input type="text" name="experience[{{ $i }}][company]" value="{{ $exp['company'] ?? '' }}" placeholder="Company / Organisation" required class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                                <input type="text" name="experience[{{ $i }}][period]" value="{{ $exp['period'] ?? '' }}" placeholder="Period (e.g. 2022 – Present)" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                                <input type="text" name="experience[{{ $i }}][description]" value="{{ $exp['description'] ?? '' }}" placeholder="Short description (optional)" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Education --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-xs uppercase tracking-wide text-white/60">Education</label>
                        <button type="button" onclick="addEducation()" class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Add entry
                        </button>
                    </div>
                    <div id="education-list" class="space-y-3">
                        @foreach(old('education', $user->education ?? []) as $i => $edu)
                            <div class="education-entry grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-xl bg-white/5 border border-white/10 relative">
                                <button type="button" onclick="this.closest('.education-entry').remove()" class="absolute top-3 right-3 text-white/30 hover:text-red-400 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <input type="text" name="education[{{ $i }}][institution]" value="{{ $edu['institution'] ?? '' }}" placeholder="Institution" required class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                                <input type="text" name="education[{{ $i }}][degree]" value="{{ $edu['degree'] ?? '' }}" placeholder="Degree / Certification" required class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                                <input type="text" name="education[{{ $i }}][period]" value="{{ $edu['period'] ?? '' }}" placeholder="Period (e.g. 2019 – 2023)" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-all">
                    Save Professional Profile
                </button>
            </form>
        </section>

        <section class="glass p-6 rounded-2xl border border-red-500/30 xl:col-span-2">
            <p class="text-sm text-white/60 mb-4">This action is permanent. Type DELETE and confirm your current password to continue.</p>

            <form method="POST" action="{{ route('dashboard.settings.account.destroy') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4" onsubmit="return confirm('This will permanently delete your account and related data. Continue?');">
                @csrf
                @method('DELETE')

                <div>
                    <label class="text-xs uppercase tracking-wide text-white/60">Current Password</label>
                    <input
                        type="password"
                        name="current_password"
                        required
                        class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-red-500/30 text-white focus:outline-none focus:border-red-400"
                    >
                </div>

                <div>
                    <label class="text-xs uppercase tracking-wide text-white/60">Type DELETE</label>
                    <input
                        type="text"
                        name="confirmation_text"
                        required
                        placeholder="DELETE"
                        class="mt-1 w-full px-3 py-2 rounded-lg bg-white/5 border border-red-500/30 text-white focus:outline-none focus:border-red-400"
                    >
                </div>

                <div class="md:self-end">
                    <button type="submit" class="w-full px-4 py-2 rounded-lg bg-red-600/30 border border-red-500/40 text-red-200 hover:bg-red-600/40 font-semibold transition-all">
                        Permanently Delete Account
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── Tech stack picker ───────────────────────────────────────────────────
(function () {
    const PRESETS = [
        // Languages
        'PHP','JavaScript','TypeScript','Python','Java','C#','C++','C','Rust','Go',
        'Ruby','Swift','Kotlin','Dart','Scala','R','Elixir','Haskell','Lua','Bash',
        // Frontend
        'HTML','CSS','React','Vue.js','Angular','Svelte','Next.js','Nuxt.js','Tailwind CSS',
        'Bootstrap','Alpine.js','Astro','Remix','Solid.js','Ember.js','jQuery',
        // Backend
        'Laravel','Symfony','Django','Flask','FastAPI','Express.js','NestJS','Spring Boot',
        'Rails','ASP.NET','Fiber','Echo','Gin','Actix','Phoenix',
        // Mobile
        'React Native','Flutter','Ionic','SwiftUI','Jetpack Compose','Xamarin',
        // Databases
        'MySQL','PostgreSQL','SQLite','MongoDB','Redis','Cassandra','DynamoDB',
        'MariaDB','Oracle','SQL Server','Firebase','Supabase','PlanetScale',
        // DevOps / Cloud
        'Docker','Kubernetes','Terraform','Ansible','Jenkins','GitHub Actions',
        'AWS','Azure','GCP','Heroku','Vercel','Netlify','Cloudflare',
        // Tools
        'Git','Linux','Nginx','Apache','GraphQL','REST API','WebSockets',
        'RabbitMQ','Kafka','Elasticsearch','Webpack','Vite','Prisma','Drizzle',
        // AI / Data
        'TensorFlow','PyTorch','scikit-learn','Pandas','NumPy','OpenCV',
        'Hugging Face','LangChain','OpenAI API','CUDA',
    ];

    const PALETTE = [
        ['bg-blue-600/25 text-blue-300 border-blue-500/30',   'bg-blue-600   text-white border-blue-500'],
        ['bg-violet-600/25 text-violet-300 border-violet-500/30', 'bg-violet-600 text-white border-violet-500'],
        ['bg-emerald-600/25 text-emerald-300 border-emerald-500/30','bg-emerald-600 text-white border-emerald-500'],
        ['bg-amber-600/25 text-amber-300 border-amber-500/30', 'bg-amber-600  text-white border-amber-500'],
        ['bg-rose-600/25 text-rose-300 border-rose-500/30',   'bg-rose-600   text-white border-rose-500'],
        ['bg-cyan-600/25 text-cyan-300 border-cyan-500/30',   'bg-cyan-600   text-white border-cyan-500'],
        ['bg-orange-600/25 text-orange-300 border-orange-500/30','bg-orange-600 text-white border-orange-500'],
        ['bg-pink-600/25 text-pink-300 border-pink-500/30',   'bg-pink-600   text-white border-pink-500'],
    ];

    const hiddenInput  = document.getElementById('tech-stack-hidden');
    const selectedBox  = document.getElementById('tech-selected');
    const emptyHint    = document.getElementById('tech-empty-hint');
    const searchInput  = document.getElementById('tech-search');
    const presetsBox      = document.getElementById('tech-presets');
    const presetsWrapper   = document.getElementById('tech-presets-wrapper');
    const toggleBtn        = document.getElementById('tech-toggle-btn');
    const toggleIcon       = document.getElementById('tech-toggle-icon');
    const toggleLabel      = document.getElementById('tech-toggle-label');
    let presetsOpen = false;

    function openPresets() {
        presetsOpen = true;
        presetsWrapper.style.maxHeight = presetsWrapper.scrollHeight + 'px';
        toggleIcon.style.transform = 'rotate(180deg)';
        toggleLabel.textContent = 'Hide';
    }

    function closePresets() {
        presetsOpen = false;
        presetsWrapper.style.maxHeight = '0';
        toggleIcon.style.transform = 'rotate(0deg)';
        toggleLabel.textContent = 'Browse';
    }

    toggleBtn.addEventListener('click', () => presetsOpen ? closePresets() : openPresets());

    // Current selection: ordered array
    let selected = hiddenInput.value.split(',').map(t => t.trim()).filter(Boolean);

    function colorFor(tag) {
        // Deterministic color based on tag string
        let h = 0;
        for (let i = 0; i < tag.length; i++) h = (h * 31 + tag.charCodeAt(i)) >>> 0;
        return PALETTE[h % PALETTE.length];
    }

    function syncHidden() {
        hiddenInput.value = selected.join(', ');
    }

    function renderSelected() {
        // Remove all tag chips (keep empty hint)
        [...selectedBox.querySelectorAll('.tech-chip')].forEach(el => el.remove());

        if (selected.length === 0) {
            emptyHint.classList.remove('hidden');
        } else {
            emptyHint.classList.add('hidden');
            selected.forEach(tag => {
                const [, activeCls] = colorFor(tag);
                const chip = document.createElement('span');
                chip.className = `tech-chip inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border cursor-pointer select-none transition-all ${activeCls}`;
                chip.title = 'Click to remove';
                chip.innerHTML = `${escHtml(tag)}<svg class="w-3 h-3 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`;
                chip.addEventListener('click', () => deselect(tag));
                selectedBox.insertBefore(chip, emptyHint);
            });
        }

        renderPresets(searchInput.value.trim().toLowerCase());
        syncHidden();
        // Resize wrapper whenever selection changes and panel is open
        if (presetsOpen) presetsWrapper.style.maxHeight = presetsWrapper.scrollHeight + 'px';
    }

    function select(tag) {
        if (!selected.includes(tag)) { selected.push(tag); renderSelected(); }
    }

    function deselect(tag) {
        selected = selected.filter(t => t !== tag);
        renderSelected();
    }

    function renderPresets(filter) {
        const show = PRESETS.filter(p => !filter || p.toLowerCase().includes(filter));
        presetsBox.innerHTML = '';
        show.forEach(tag => {
            const isActive = selected.includes(tag);
            const [idleCls, activeCls] = colorFor(tag);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border cursor-pointer select-none transition-all ${
                isActive ? activeCls : idleCls
            }`;
            btn.textContent = tag;
            btn.addEventListener('click', () => isActive ? deselect(tag) : select(tag));
            presetsBox.appendChild(btn);
        });
    }

    // Custom tech via search (Enter key)
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = searchInput.value.trim();
            if (val) { select(val); searchInput.value = ''; }
        }
    });

    searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();
        renderPresets(q);
        // Auto-open the panel when the user starts typing
        if (q && !presetsOpen) openPresets();
        // Expand to fit new content after re-render
        if (presetsOpen) presetsWrapper.style.maxHeight = presetsWrapper.scrollHeight + 'px';
    });

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Init
    renderSelected();
})();

// ── Dynamic Experience rows ──────────────────────────────────────────────
let expIdx = {{ count(old('experience', $user->experience ?? [])) }};
function addExperience() {
    const i = expIdx++;
    const list = document.getElementById('experience-list');
    const div = document.createElement('div');
    div.className = 'experience-entry grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-xl bg-white/5 border border-white/10 relative';
    div.innerHTML = `
        <button type="button" onclick="this.closest('.experience-entry').remove()" class="absolute top-3 right-3 text-white/30 hover:text-red-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <input type="text" name="experience[${i}][role]" placeholder="Role / Position" required class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
        <input type="text" name="experience[${i}][company]" placeholder="Company / Organisation" required class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
        <input type="text" name="experience[${i}][period]" placeholder="Period (e.g. 2022 – Present)" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
        <input type="text" name="experience[${i}][description]" placeholder="Short description (optional)" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
    `;
    list.appendChild(div);
    div.querySelector('input').focus();
}

// ── Dynamic Education rows ───────────────────────────────────────────────
let eduIdx = {{ count(old('education', $user->education ?? [])) }};
function addEducation() {
    const i = eduIdx++;
    const list = document.getElementById('education-list');
    const div = document.createElement('div');
    div.className = 'education-entry grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-xl bg-white/5 border border-white/10 relative';
    div.innerHTML = `
        <button type="button" onclick="this.closest('.education-entry').remove()" class="absolute top-3 right-3 text-white/30 hover:text-red-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <input type="text" name="education[${i}][institution]" placeholder="Institution" required class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
        <input type="text" name="education[${i}][degree]" placeholder="Degree / Certification" required class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
        <input type="text" name="education[${i}][period]" placeholder="Period (e.g. 2019 – 2023)" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/25 focus:outline-none focus:border-blue-500 text-sm">
    `;
    list.appendChild(div);
    div.querySelector('input').focus();
}
</script>
@endsection
