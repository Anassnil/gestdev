@extends('layouts.app')

@section('content')
<style>
    /* Rebranded GestDev Mesh Background */
    .bg-gestdev-login {
        background-color: #02010A;
        background-image: 
            radial-gradient(at 0% 0%, hsla(243, 98%, 16%, 0.4) 0px, transparent 50%),
            radial-gradient(at 100% 0%, hsla(245, 100%, 32%, 0.3) 0px, transparent 50%),
            radial-gradient(at 100% 100%, hsla(245, 100%, 15%, 0.4) 0px, transparent 50%),
            radial-gradient(at 0% 100%, hsla(245, 100%, 10%, 0.5) 0px, transparent 50%);
        animation: mesh-move 15s ease infinite alternate;
    }

    @keyframes mesh-move {
        0% { background-position: 0% 0%; }
        100% { background-position: 100% 100%; }
    }

    .glass {
        background: rgba(4, 5, 46, 0.45);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .input-glow:focus {
        border-color: #0D00A4;
        box-shadow: 0 0 20px rgba(13, 0, 164, 0.3);
        background: rgba(2, 1, 10, 0.8);
    }

    .fade-in-up {
        animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Light mode overrides for login */
    [data-theme="light"] .bg-gestdev-login {
        background-color: #f4f6fb;
        background-image:
            radial-gradient(at 0% 0%, rgba(13,0,164,0.06) 0px, transparent 50%),
            radial-gradient(at 100% 0%, rgba(34,0,124,0.05) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(20,1,82,0.04) 0px, transparent 50%),
            radial-gradient(at 0% 100%, rgba(4,5,46,0.03) 0px, transparent 50%);
    }
    [data-theme="light"] .glass {
        background: rgba(255, 255, 255, 0.85);
        border-color: rgba(13, 0, 164, 0.1);
    }
    [data-theme="light"] .input-glow:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 20px rgba(79, 70, 229, 0.15);
        background: #f0f1f8;
    }
</style>

<div class="bg-gestdev-login min-h-screen relative">
    <!-- Theme toggle (top-right corner) -->
    <div class="absolute top-6 right-6 z-50">
        <button onclick="toggleTheme()" class="theme-toggle" aria-label="Toggle theme">
            <span class="toggle-thumb">
                <span class="login-theme-icon"></span>
            </span>
        </button>
        <script>
            (function(){
                var el = document.querySelector('.login-theme-icon');
                if(el) el.textContent = (document.documentElement.getAttribute('data-theme') === 'light') ? '☀️' : '🌙';
                var obs = new MutationObserver(function() {
                    if(el) el.textContent = (document.documentElement.getAttribute('data-theme') === 'light') ? '☀️' : '🌙';
                });
                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
            })();
        </script>
    </div>
    <main class="flex items-center justify-center px-6 pt-20 pb-20">
        <div class="w-full max-w-md fade-in-up">
            <div class="glass rounded-[2.5rem] p-10 shadow-2xl relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-[#140152] blur-[80px] opacity-50"></div>
                
                <div class="relative z-10">
                    <div class="mb-8">
                        <h2 class="text-3xl font-black text-white mb-2 tracking-tight uppercase italic">Welcome back</h2>
                        <p class="text-white/40 text-sm">Resume your project orchestration.</p>
                    </div>

                    @if($errors->any())
                        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-xs text-red-300">
                            <ul class="list-disc pl-4">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        <div class="group">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30 mb-2 ml-1">Work Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required 
                                class="w-full px-5 py-4 rounded-2xl bg-[#02010A]/50 border border-white/10 text-white input-glow transition-all duration-300 placeholder-white/10"
                                placeholder="name@company.com">
                        </div>

                        <div class="group">
                            <div class="flex justify-between items-center mb-2 ml-1">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30">Access Key</label>
                                <a href="#" class="text-[10px] font-black uppercase tracking-[0.1em] text-[#0D00A4] hover:text-white transition">Forgot?</a>
                            </div>
                            <input type="password" name="password" required 
                                class="w-full px-5 py-4 rounded-2xl bg-[#02010A]/50 border border-white/10 text-white input-glow transition-all duration-300"
                                placeholder="••••••••">
                        </div>

                        <div class="flex items-center px-1">
                            <label class="flex items-center gap-3 cursor-pointer group/check">
                                <div class="relative flex items-center">
                                    <input type="checkbox" name="remember" class="peer appearance-none w-5 h-5 rounded-lg border border-white/10 bg-[#02010A]/50 checked:bg-[#0D00A4] checked:border-[#0D00A4] transition-all cursor-pointer">
                                    <svg class="absolute w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none left-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-[0.1em] text-white/30 group-hover/check:text-white transition">Keep me logged in</span>
                            </label>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full py-4 bg-white text-[#02010A] font-black uppercase tracking-widest text-sm rounded-2xl hover:bg-blue-50 transform hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl">
                                Initialize Session
                            </button>
                        </div>
                    </form>

                    <div class="mt-10 flex items-center justify-center space-x-2 text-[10px] font-black uppercase tracking-[0.1em]">
                        <span class="text-white/20">New to the ecosystem?</span>
                        <a href="{{ route('register.form') }}" class="text-white hover:text-[#0D00A4] transition underline decoration-white/10 underline-offset-8">Create account</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection