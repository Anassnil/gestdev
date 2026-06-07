@extends('layouts.app')

@section('content')
<style>
    /* Global Background & Smooth Scroll */
    html { scroll-behavior: smooth; }
    body { background-color: #02010A; }

    /* Animated Mesh Gradient */
    .bg-gestdev-main {
        background: radial-gradient(circle at 20% 30%, hsla(245, 100%, 15%, 0.5) 0%, transparent 40%),
                    radial-gradient(circle at 80% 70%, hsla(243, 98%, 10%, 0.4) 0%, transparent 40%);
    }

    /* Glassmorphism Refinement */
    .glass {
        background: rgba(4, 5, 46, 0.4);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* Input & Button Glows */
    .input-glow:focus {
        border-color: #0D00A4;
        box-shadow: 0 0 20px rgba(13, 0, 164, 0.3);
    }

    /* Animations */
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
    .animate-float { animation: float 5s ease-in-out infinite; }

    .hover-lift { transition: transform 0.3s cubic-bezier(0.17, 0.67, 0.83, 0.67); }
    .hover-lift:hover { transform: translateY(-5px); }

    /* Light mode overrides for home */
    [data-theme="light"] body { background-color: #f4f6fb !important; }
    [data-theme="light"] .bg-gestdev-main {
        background: radial-gradient(circle at 20% 30%, rgba(13,0,164,0.04) 0%, transparent 40%),
                    radial-gradient(circle at 80% 70%, rgba(4,5,46,0.03) 0%, transparent 40%);
    }
    [data-theme="light"] .glass {
        background: rgba(255, 255, 255, 0.8);
        border-color: rgba(13, 0, 164, 0.1);
    }
    [data-theme="light"] .input-glow:focus {
        background: #f0f1f8;
        box-shadow: 0 0 20px rgba(13, 0, 164, 0.12);
    }
</style>

<div class="bg-gestdev-main min-h-screen text-white">
    <nav class="fixed top-0 w-full z-50 px-6 py-5">
        <div class="max-w-screen-2xl mx-auto glass rounded-2xl px-6 py-3 flex items-center justify-between transition-all duration-500 hover:border-white/20">
            <div class="flex items-center space-x-2 group cursor-pointer" onclick="window.location='/'">
                <div class="w-9 h-9 bg-gradient-to-br from-[#0D00A4] to-[#22007C] rounded-xl rotate-12 group-hover:rotate-0 transition-all duration-500 shadow-[0_0_20px_rgba(13,0,164,0.4)] flex items-center justify-center font-bold text-lg">G</div>
                <span class="font-black text-2xl tracking-tighter uppercase italic">GestDev</span>
            </div>

            <div class="hidden md:flex items-center space-x-12 text-xs font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">
                <a href="#about" class="hover:text-white transition-all">About</a>
                <a href="#services" class="hover:text-white transition-all">Services</a>
                <a href="#contact" class="hover:text-white transition-all">Contact</a>
            </div>

            <div class="flex items-center space-x-6">
                <button onclick="toggleTheme()" class="theme-toggle" aria-label="Toggle theme">
                    <span class="toggle-thumb">
                        <span class="home-theme-icon"></span>
                    </span>
                </button>
                <script>
                    (function(){
                        var el = document.querySelector('.home-theme-icon');
                        if(el) el.textContent = (document.documentElement.getAttribute('data-theme') === 'light') ? '☀️' : '🌙';
                        var obs = new MutationObserver(function() {
                            if(el) el.textContent = (document.documentElement.getAttribute('data-theme') === 'light') ? '☀️' : '🌙';
                        });
                        obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
                    })();
                </script>
                <a href="{{ route('login.form') }}" class="text-sm font-bold hover:text-white transition" style="color: var(--text-secondary);">Login</a>
                <a href="{{ route('register.form') }}" class="bg-[#0D00A4] px-6 py-2.5 rounded-xl text-sm font-extrabold shadow-lg hover:bg-[#140152] transform hover:-translate-y-1 transition-all active:scale-95 border border-white/10 text-white">
                    Join Now
                </a>
            </div>
        </div>
    </nav>

    <header class="relative pt-48 pb-32 px-6 overflow-hidden text-center">
        <div class="max-w-5xl mx-auto relative z-10">
            <div class="inline-block px-4 py-1.5 mb-8 rounded-full border border-white/10 bg-white/5 text-[10px] uppercase tracking-[0.3em] text-blue-400 font-bold">
                The New Standard for SaaS Architecture
            </div>
            <h1 class="text-6xl md:text-9xl font-black tracking-tighter leading-[0.85] mb-10">
                Plan. Code. <br>
                <span class="bg-clip-text text-transparent bg-gradient-to-b from-white to-white/40">Scale Dev.</span>
            </h1>
            <p class="max-w-2xl mx-auto text-white/50 text-lg md:text-xl font-medium leading-relaxed mb-12">
                GestDev is the unified workspace for software architects. From initial structuring to AI integration and SaaS deployment—manage the entire lifecycle in one midnight-tuned interface.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-5">
                <a href="{{ route('register.form') }}" class="w-full sm:w-auto px-12 py-5 bg-white text-[#02010A] font-black rounded-2xl hover:scale-105 transition-all shadow-2xl">Start Your Project</a>
                <a href="#about" class="w-full sm:w-auto px-12 py-5 glass text-white font-bold rounded-2xl hover:bg-white/10 transition-all">Learn More</a>
            </div>
        </div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] bg-[#0D00A4] blur-[160px] opacity-20 pointer-events-none"></div>
    </header>

    <section id="about" class="py-32 px-6 border-t border-white/5">
        <div class="max-w-screen-2xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
                <div class="animate-float">
                    <div class="glass p-8 rounded-[3rem] border-white/10 relative">
                        <div class="absolute -top-6 -left-6 w-24 h-24 bg-[#0D00A4] rounded-full blur-3xl opacity-50"></div>
                        <div class="space-y-6">
                            <div class="h-4 w-3/4 bg-white/5 rounded-full"></div>
                            <div class="h-4 w-1/2 bg-[#0D00A4]/40 rounded-full"></div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="h-20 bg-white/5 rounded-2xl border border-white/5"></div>
                                <div class="h-20 bg-[#0D00A4]/20 rounded-2xl border border-[#0D00A4]/30"></div>
                                <div class="h-20 bg-white/5 rounded-2xl border border-white/5"></div>
                            </div>
                            <div class="h-32 w-full bg-gradient-to-br from-[#04052E] to-[#02010A] rounded-2xl border border-white/5 flex items-center justify-center text-white/20 font-mono text-xs uppercase tracking-widest">
                                [ Architecture Map ]
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-4xl md:text-5xl font-extrabold mb-8 leading-tight">Master the <span class="text-[#0D00A4]">Full Cycle</span> of Modern SaaS.</h2>
                    <p class="text-white/60 text-lg mb-10 leading-relaxed">
                        GestDev isn't just a task manager; it's a **Software Orchestration Engine**. We help you bridge the gap between abstract planning and concrete code execution.
                    </p>
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="mt-1 p-2 bg-[#0D00A4]/20 rounded-lg text-[#0D00A4]">✔</div>
                            <div>
                                <h4 class="font-bold text-white">AI-First Planning</h4>
                                <p class="text-sm text-white/40">Structure your AI models and data flows before a single line of code is written.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <div class="mt-1 p-2 bg-[#140152]/40 rounded-lg text-[#22007C]">✔</div>
                            <div>
                                <h4 class="font-bold text-white">Logic Structuring</h4>
                                <p class="text-sm text-white/40">Visual tools to map out your SaaS subscription logic and database schemas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-32 px-6 bg-[#04052E]/20">
        <div class="max-w-4xl mx-auto text-center mb-16">
            <h2 class="text-4xl font-black mb-4 uppercase tracking-tighter">Get in Touch</h2>
            <p class="text-white/40">Have a custom SaaS project? Let's build it on GestDev.</p>
        </div>
        <div class="max-w-3xl mx-auto glass rounded-[2.5rem] p-10 md:p-16 relative overflow-hidden">
             <form action="#" class="space-y-8 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="group">
                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30 mb-3 ml-1">Full Name</label>
                        <input type="text" placeholder="Dev Guru" class="w-full bg-[#02010A]/50 border border-white/5 rounded-2xl px-6 py-4 text-white focus:outline-none input-glow transition-all placeholder-white/10">
                    </div>
                    <div class="group">
                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30 mb-3 ml-1">Project Email</label>
                        <input type="email" placeholder="build@gestdev.io" class="w-full bg-[#02010A]/50 border border-white/5 rounded-2xl px-6 py-4 text-white focus:outline-none input-glow transition-all placeholder-white/10">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/30 mb-3 ml-1">Your Vision</label>
                    <textarea rows="4" placeholder="Briefly describe your SaaS or AI product..." class="w-full bg-[#02010A]/50 border border-white/5 rounded-2xl px-6 py-4 text-white focus:outline-none input-glow transition-all placeholder-white/10"></textarea>
                </div>
                <button class="w-full py-5 bg-white text-[#02010A] font-black rounded-2xl hover:shadow-[0_0_30px_rgba(255,255,255,0.2)] transform hover:scale-[1.01] transition-all uppercase tracking-widest text-sm">
                    Deploy Inquiry
                </button>
             </form>
        </div>
    </section>

    <footer class="pt-32 pb-12 px-6 border-t border-white/5 bg-[#02010A]">
        <div class="max-w-screen-2xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-start mb-24 space-y-12 md:space-y-0">
                <div class="max-w-xs">
                    <div class="flex items-center space-x-2 mb-6">
                        <div class="w-8 h-8 bg-[#0D00A4] rounded-lg rotate-12 flex items-center justify-center font-bold">G</div>
                        <span class="font-black text-2xl tracking-tighter italic uppercase">GestDev</span>
                    </div>
                    <p class="text-white/30 text-sm leading-relaxed mb-8">
                        The ultimate ecosystem for software developers and project owners to plan, build, and conquer.
                    </p>
                    <div class="flex space-x-4">
                        <div class="w-10 h-10 rounded-xl glass flex items-center justify-center hover:bg-[#0D00A4]/20 cursor-pointer transition-all">𝕏</div>
                        <div class="w-10 h-10 rounded-xl glass flex items-center justify-center hover:bg-[#0D00A4]/20 cursor-pointer transition-all">󰊤</div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-16">
                    <div>
                        <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6">Capabilities</h5>
                        <ul class="space-y-4 text-sm font-bold text-white/50">
                            <li><a href="#" class="hover:text-white transition">Architecture</a></li>
                            <li><a href="#" class="hover:text-white transition">SaaS Logic</a></li>
                            <!-- AI Models link removed from public footer -->
                        </ul>
                    </div>
                    <div>
                        <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6">Platform</h5>
                        <ul class="space-y-4 text-sm font-bold text-white/50">
                            <li><a href="#" class="hover:text-white transition">Changelog</a></li>
                            <li><a href="#" class="hover:text-white transition">API Docs</a></li>
                            <li><a href="#" class="hover:text-white transition">Pricing</a></li>
                        </ul>
                    </div>
                    <div class="hidden sm:block">
                        <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6">Legal</h5>
                        <ul class="space-y-4 text-sm font-bold text-white/50">
                            <li><a href="#" class="hover:text-white transition">Privacy</a></li>
                            <li><a href="#" class="hover:text-white transition">Security</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center text-[10px] font-black uppercase tracking-[0.4em] text-white/10">
                <p>© 2026 GESTDEV ECOSYSTEM. ALL SYSTEMS OPERATIONAL.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <span>Status: Healthy</span>
                    <span>Region: Global</span>
                </div>
            </div>
        </div>
    </footer>

</div>
@endsection