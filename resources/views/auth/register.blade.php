@extends('layouts.app')

@section('content')
<style>
    /* Dynamic Mesh Background */
    .bg-gestdev-animated {
        background-color: #02010A;
        background-image: 
            radial-gradient(at 10% 20%, hsla(243, 98%, 16%, 0.4) 0px, transparent 50%),
            radial-gradient(at 90% 10%, hsla(245, 100%, 32%, 0.3) 0px, transparent 50%),
            radial-gradient(at 80% 90%, hsla(245, 100%, 15%, 0.4) 0px, transparent 50%),
            radial-gradient(at 20% 80%, hsla(245, 100%, 10%, 0.5) 0px, transparent 50%);
        animation: mesh-move 20s ease infinite alternate;
    }

    @keyframes mesh-move {
        0% { background-position: 0% 0%; }
        100% { background-position: 100% 100%; }
    }

    .glass {
        background: rgba(4, 5, 46, 0.4);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* Staggered Animations */
    .fade-up { opacity: 0; transform: translateY(20px); animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }

    /* Input Focus */
    .input-glow:focus { border-color: #0D00A4; box-shadow: 0 0 20px rgba(13, 0, 164, 0.3); }

    /* Light mode overrides for register */
    [data-theme="light"] .bg-gestdev-animated {
        background-color: #f4f6fb;
        background-image:
            radial-gradient(at 10% 20%, rgba(13,0,164,0.06) 0px, transparent 50%),
            radial-gradient(at 90% 10%, rgba(34,0,124,0.05) 0px, transparent 50%),
            radial-gradient(at 80% 90%, rgba(20,1,82,0.04) 0px, transparent 50%),
            radial-gradient(at 20% 80%, rgba(4,5,46,0.03) 0px, transparent 50%);
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

<div class="bg-gestdev-animated min-h-screen text-white relative">
    <!-- Theme toggle (top-right corner) -->
    <div class="absolute top-6 right-6 z-50">
        <button onclick="toggleTheme()" class="theme-toggle" aria-label="Toggle theme">
            <span class="toggle-thumb">
                <span class="reg-theme-icon"></span>
            </span>
        </button>
        <script>
            (function(){
                var el = document.querySelector('.reg-theme-icon');
                if(el) el.textContent = (document.documentElement.getAttribute('data-theme') === 'light') ? '☀️' : '🌙';
                var obs = new MutationObserver(function() {
                    if(el) el.textContent = (document.documentElement.getAttribute('data-theme') === 'light') ? '☀️' : '🌙';
                });
                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
            })();
        </script>
    </div>
    <main class="flex items-center justify-center px-6 pt-20 pb-20">
        <div class="w-full max-w-lg">
            <div class="glass rounded-[3rem] p-10 md:p-12 shadow-2xl relative overflow-hidden fade-up">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-[#0D00A4] blur-[120px] opacity-20"></div>

                <div class="relative z-10 text-center mb-10">
                    <h2 class="text-3xl font-black tracking-tight uppercase italic mb-2">Initialize Account</h2>
                    <p class="text-white/40 text-sm font-medium tracking-wide">Join the GestDev developer ecosystem.</p>
                </div>

                @if($errors->any())
                    <div class="mb-8 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-xs text-red-300 fade-up">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="fade-up delay-1">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30 mb-3 ml-1">Full Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required 
                                class="w-full px-6 py-4 rounded-2xl bg-[#02010A]/50 border border-white/5 text-white input-glow transition-all duration-300 placeholder-white/10"
                                placeholder="Developer Name">
                        </div>
                        <div class="fade-up delay-1">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30 mb-3 ml-1">Work Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required 
                                class="w-full px-6 py-4 rounded-2xl bg-[#02010A]/50 border border-white/5 text-white input-glow transition-all duration-300 placeholder-white/10"
                                placeholder="dev@gestdev.io">
                        </div>
                    </div>

                    <div class="fade-up delay-2">
                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30 mb-3 ml-1">Security Key (Password)</label>
                        <input type="password" name="password" required 
                            class="w-full px-6 py-4 rounded-2xl bg-[#02010A]/50 border border-white/5 text-white input-glow transition-all duration-300"
                            placeholder="••••••••">
                    </div>

                    <div class="fade-up delay-3">
                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30 mb-3 ml-1">Confirm Security Key</label>
                        <input type="password" name="password_confirmation" required 
                            class="w-full px-6 py-4 rounded-2xl bg-[#02010A]/50 border border-white/5 text-white input-glow transition-all duration-300"
                            placeholder="••••••••">
                    </div>

                    <div class="pt-4 fade-up delay-4">
                        <button type="submit" class="w-full py-5 bg-white text-[#02010A] font-black rounded-2xl hover:scale-[1.01] transition-all shadow-xl shadow-white/5 active:scale-[0.98] uppercase tracking-widest text-sm">
                            Create Account
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-white/5 text-center fade-up delay-4">
                    <p class="text-sm text-white/40">
                        Already have access? 
                        <a href="{{ route('login.form') }}" class="text-white font-bold hover:underline decoration-[#0D00A4] underline-offset-4 transition">Sign In</a>
                    </p>
                </div>
            </div>
            
            <p class="mt-8 text-center text-[10px] text-white/20 uppercase tracking-[0.2em]">
                By registering, you accept our service agreement.
            </p>
        </div>
    </main>
</div>
@endsection