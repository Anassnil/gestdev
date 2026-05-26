
<div id="diagram-hub-section" class="mt-12">
    {{-- debug CSS removed --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
        @if($showHeader ?? true)
            <div>
                <h2 class="text-3xl font-black text-white tracking-tighter uppercase italic">Diagram Hub</h2>
                <p class="label-hub text-blue-400/60 mt-1">Architecture Repository</p>
            </div>
        @endif
        
        @if($showControls ?? true)
            <div class="flex gap-3">
                @if(auth()->check() && $board->canEdit(auth()->user()))
                    <button id="diagram-create-inline" class="btn-hub px-6 py-3 bg-white text-black rounded-xl hover:bg-blue-500 hover:text-white transition-all">
                        Create Node
                    </button>
                @endif
                @if(auth()->check() && $board->canEdit(auth()->user()))
                    <button id="ai-open-btn" class="btn-hub px-4 py-2 bg-violet-600 text-white rounded-xl hover:bg-violet-500 transition-all">AI Suggestions</button>
                @endif
                <a href="https://www.planttext.com/" target="_blank" rel="noopener" class="btn-hub px-6 py-3 bg-gray-800 text-white border border-white/10 rounded-xl hover:bg-gray-900 flex items-center">
                    <!-- Inline PlantUML icon SVG to avoid cross-origin blocking -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2" stroke-width="1.5" />
                        <path d="M7 10v6" stroke-width="1.5" />
                        <path d="M17 10v6" stroke-width="1.5" />
                        <path d="M7 13h10" stroke-width="1.5" />
                    </svg>
                    <span>PlantUML</span>
                </a>
            </div>
        @endif
    </div>

    {{-- AI Suggestions moved to popup modal; opened via header button --}}

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 hub-grid">
        @forelse($diagrams as $index => $diagram)
            <div id="diagram-card-{{ $diagram->id }}" 
                 class="diagram-card p-5 hub-glass rounded-[2rem] group cursor-pointer" 
                 style="animation-delay: {{ $index * 0.1 }}s"
                 data-id="{{ $diagram->id }}" 
                 data-title="{{ htmlspecialchars($diagram->title, ENT_QUOTES) }}" 
                  data-type="{{ $diagram->type }}" 
                  data-code="{{ htmlspecialchars($diagram->code ?? '', ENT_QUOTES) }}" 
                  data-image="{{ $diagram->image }}"
                  data-description="{{ htmlspecialchars($diagram->description ?? '', ENT_QUOTES) }}">
                
                <div class="diagram-image-wrapper mb-4 bg-black/20 rounded-2xl h-44 overflow-hidden relative">
                    @if($diagram->image)
                        <img src="{{ asset('storage/'.$diagram->image) }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="{{ $diagram->title }}">
                    @else
                        <div class="w-full h-full flex items-center justify-center italic text-white/10 text-[10px] tracking-widest uppercase font-black">Null Visual</div>
                    @endif
                    <div class="absolute top-3 left-3">
                        <span class="px-2 py-1 bg-black/60 backdrop-blur-md text-[9px] font-black text-blue-400 border border-white/5 rounded-md uppercase">
                            {{ $diagram->type }}
                        </span>
                    </div>
                </div>

                <div class="px-1">
                    <div class="text-white font-bold text-lg leading-tight group-hover:text-blue-400 transition-colors uppercase italic truncate">{{ $diagram->title }}</div>
                    
                    <div class="mt-2 text-xs text-white/40">
                        <span>By: {{ $diagram->creator?->name ?? 'Unknown' }}</span>
                        <span class="mx-2">·</span>
                        <span>{{ $diagram->created_at->diffForHumans() }}</span>
                        @if($diagram->updated_at && $diagram->updated_at->gt($diagram->created_at))
                            <span class="mx-2">·</span>
                            <span>Updated: {{ $diagram->updater?->name ?? 'Unknown' }} {{ $diagram->updated_at->diffForHumans() }}</span>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all translate-y-2 group-hover:translate-y-0">
                        <button data-id="{{ $diagram->id }}" class="diagram-open btn-hub flex-1 py-2 bg-blue-600 text-white rounded-lg">View</button>
                        @if(auth()->check() && $board->canEdit(auth()->user()))
                            <button data-id="{{ $diagram->id }}" class="diagram-edit btn-hub p-2 bg-white/5 text-white/40 rounded-lg hover:text-white">Edit</button>
                            <button data-id="{{ $diagram->id }}" class="diagram-delete btn-hub p-2 text-red-500/40 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" /></svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center border-2 border-dashed border-white/5 rounded-[2rem]">
                <p class="label-hub">Awaiting Node Initialization</p>
            </div>
        @endforelse
    </div>

    <!-- Main Modal Container -->
    <div id="diagram-modal-inline" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 backdrop-blur-md bg-black/80">
        <div class="relative bg-[#0d0d0d] border border-white/10 rounded-[1.5rem] overflow-hidden w-full max-w-6xl shadow-[0_20px_50px_rgba(0,0,0,0.5)]">

            <!-- Header -->
            <div class="flex justify-between items-center px-8 py-5 border-b border-white/5 bg-[#141414]">
                <div>
                    <h3 id="diagram-modal-title-inline" class="text-xl font-bold text-white tracking-tight uppercase">
                        <span class="text-blue-500 mr-2">//</span>Initialize Diagram
                    </h3>
                    <p class="text-[11px] font-mono text-white/40 uppercase tracking-[0.2em] mt-0.5">Terminal / Logic Source Configuration</p>
                </div>
                <button type="button" id="diagram-close-x" class="group p-2 rounded-lg hover:bg-white/5 transition-all">
                    <svg class="w-6 h-6 text-white/20 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="diagram-form-inline" class="p-8">
                <input type="hidden" name="id" id="diagram-id-inline" />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

                    <!-- LEFT COLUMN: Metadata -->
                    <div class="col-span-1 space-y-6">
                        <div id="diagram-image-preview" class="cursor-zoom-in group relative aspect-video rounded-xl overflow-hidden bg-white/5 border border-white/10 transition-all hover:border-blue-500/50">
                            <img src="" id="diagram-image-preview-img" class="w-full h-full object-contain" alt="preview" />
                            <div id="diagram-image-input-wrap" class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <label for="diagram-image-inline" class="px-4 py-2 bg-white text-black rounded-lg cursor-pointer text-sm font-semibold">Change Image</label>
                                <input type="file" name="image" id="diagram-image-inline" accept="image/*" class="hidden" />
                            </div>
                            <button id="diagram-change-image-btn" type="button" class="absolute top-3 right-3 hidden bg-white/90 text-black px-3 py-1 rounded-md shadow text-xs">Change</button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">Project Identifier</label>
                                <input name="title" id="diagram-title-inline" type="text" placeholder="Diagram Name"
                                    class="w-full mt-1.5 bg-[#1a1a1a] border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors" />
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">System Type</label>
                                <input name="type" id="diagram-type-inline" type="text" placeholder="e.g. class, sequence, er"
                                    class="w-full mt-1.5 bg-[#1a1a1a] border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors" />
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">Description <span class="text-[10px] font-normal text-white/30">(optional)</span></label>
                                <textarea name="description" id="diagram-description-inline" rows="3" placeholder="Optional description"
                                    class="w-full mt-1.5 bg-[#1a1a1a] border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors text-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: VS Code Style Editor -->
                    <div class="col-span-2 flex flex-col">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">Logic Source Code</label>
                            <span class="text-[10px] font-mono text-white/30">UTF-8 • PlantUML</span>
                        </div>

                        <!-- Editor Container -->
                        <div id="diagram-editor-container" class="relative flex flex-col flex-grow min-h-[450px] rounded-xl border border-white/10 bg-white/5 overflow-hidden shadow-inner font-mono text-[13px] leading-relaxed">

                            <!-- Toolbar -->
                            <div class="flex items-center justify-between px-3 py-2 border-b border-white/10 bg-white/5">
                                <div class="flex items-center gap-3">
                                    <div class="bg-transparent px-3 py-1 text-white/80 flex items-center gap-2 text-xs rounded-md">
                                        <span class="text-blue-400">PU</span>
                                        <span class="text-[12px] font-black">diagram.puml</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button id="editor-toggle-wrap" type="button" class="text-xs px-3 py-1 bg-white/3 rounded-md text-white/80 hover:bg-white/5">Toggle Wrap</button>
                                    <button id="editor-copy-btn" type="button" class="text-xs px-3 py-1 bg-white/3 rounded-md text-white/80 hover:bg-white/5">Copy</button>
                                    <button id="editor-theme-toggle" type="button" class="text-xs px-3 py-1 bg-white/3 rounded-md text-white/80 hover:bg-white/5">Light Mode</button>
                                </div>
                            </div>

                            <!-- Editor Body -->
                            <div class="flex-1 min-h-0 flex">
                                <!-- Line numbers removed -->

                                <!-- Textarea -->
                                <textarea name="code" id="diagram-code-inline"
                                    class="w-full h-full px-4 py-4 bg-transparent outline-none resize-none text-[#ce9178] caret-blue-500 placeholder:text-white/10 text-[13px] leading-[1.75rem] overflow-auto min-h-0" 
                                    style="overflow:auto; height:100%;"
                                    spellcheck="false"
                                    placeholder="// Paste or write your PlantUML here..."></textarea>

                                <!-- Syntax highlight preview (hidden by default). Use flex so it can scroll in view mode -->
                                <pre id="diagram-code-preview" class="hidden flex-1 overflow-auto px-4 py-4 m-0 bg-transparent"><code id="diagram-code-preview-code" class="language-javascript text-[13px] leading-[1.75rem]"></code></pre>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="mt-8 flex justify-end items-center gap-6">
                            <button type="button" id="diagram-cancel-inline" class="text-xs font-bold text-white/30 hover:text-white uppercase tracking-widest transition-colors">Abort</button>
                            <button type="submit" id="diagram-save-inline" class="px-10 py-4 bg-blue-600 hover:bg-blue-500 text-white text-xs font-black uppercase tracking-[0.15em] rounded-full transition-all shadow-[0_10px_20px_rgba(37,99,235,0.2)] active:scale-95">
                                Sync Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- AI Chat Modal -->
    <div id="ai-suggest-modal" class="hidden fixed inset-0 z-[150] flex items-center justify-center p-4 modal-blur bg-black/80">
        <div class="relative w-full max-w-4xl shadow-2xl flex flex-col rounded-[2rem] overflow-hidden border border-white/8"
             style="background:#0D0F1E; max-height:90vh;">

            <!-- ── Header ── -->
            <div class="flex items-center justify-between px-6 py-4 border-b shrink-0"
                 style="background:rgba(99,102,241,0.08); border-color:rgba(255,255,255,0.06);">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                         style="background:linear-gradient(135deg,#6366F1,#8B5CF6)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">AI Diagram Assistant</h3>
                        <p class="text-[10px] text-white/35 tracking-wide">Describe a system — get PlantUML instantly</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="ai-clear-btn" title="Clear chat"
                        class="p-2 rounded-xl text-white/25 hover:text-white/60 hover:bg-white/5 transition-all text-xs font-black uppercase tracking-widest">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button id="ai-close-btn" type="button"
                        class="p-2 rounded-xl text-white/25 hover:text-white transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- ── Body: sidebar + chat ── -->
            <div class="flex flex-1 min-h-0 overflow-hidden">

                <!-- Left sidebar: diagram type + quick prompts -->
                <div class="w-52 shrink-0 flex flex-col border-r overflow-y-auto"
                     style="background:rgba(5,7,20,0.6); border-color:rgba(255,255,255,0.05);">

                    <div class="px-4 pt-4 pb-2">
                        <p class="text-[9px] font-black uppercase tracking-[0.18em] text-white/25 mb-2">Diagram Type</p>
                        <div class="space-y-1" id="ai-type-selector">
                            <button class="ai-type-btn w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition-all active-type"
                                    data-type="class">
                                <span class="text-[10px] mr-1.5">⬡</span> Class Diagram
                            </button>
                            <button class="ai-type-btn w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition-all"
                                    data-type="sequence">
                                <span class="text-[10px] mr-1.5">↔</span> Sequence
                            </button>
                            <button class="ai-type-btn w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition-all"
                                    data-type="usecase">
                                <span class="text-[10px] mr-1.5">◎</span> Use Case
                            </button>
                            <button class="ai-type-btn w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition-all"
                                    data-type="er">
                                <span class="text-[10px] mr-1.5">⊞</span> ER Diagram
                            </button>
                            <button class="ai-type-btn w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition-all"
                                    data-type="activity">
                                <span class="text-[10px] mr-1.5">▶</span> Activity
                            </button>
                            <button class="ai-type-btn w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition-all"
                                    data-type="state">
                                <span class="text-[10px] mr-1.5">◈</span> State Machine
                            </button>
                            <button class="ai-type-btn w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition-all"
                                    data-type="component">
                                <span class="text-[10px] mr-1.5">⬡</span> Component
                            </button>
                        </div>
                    </div>

                    <div class="px-4 pt-4 pb-4 border-t mt-2" style="border-color:rgba(255,255,255,0.05);">
                        <p class="text-[9px] font-black uppercase tracking-[0.18em] text-white/25 mb-2">Quick Prompts</p>
                        <div class="space-y-1.5" id="ai-quick-prompts">
                            <!-- populated by JS based on selected type -->
                        </div>
                    </div>
                </div>

                <!-- Chat area -->
                <div class="flex-1 flex flex-col min-h-0 min-w-0">

                    <!-- Messages -->
                    <div id="ai-chat-messages" class="flex-1 overflow-y-auto px-5 py-4 space-y-4"
                         style="scrollbar-width:thin; scrollbar-color:rgba(99,102,241,0.3) transparent;">
                        <div id="ai-chat-empty" class="flex flex-col items-center justify-center h-full py-14 text-center">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4"
                                 style="background:rgba(99,102,241,0.12);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-white/40 mb-1">Ready to generate</p>
                            <p class="text-xs text-white/20 max-w-[240px] leading-relaxed">Choose a diagram type and describe your system. Use quick prompts for inspiration.</p>
                        </div>
                    </div>

                    <!-- Input bar -->
                    <div class="px-5 pb-4 pt-3 border-t shrink-0" style="border-color:rgba(255,255,255,0.06);">
                        <!-- Type badge -->
                        <div class="flex items-center gap-2 mb-2.5">
                            <span id="ai-active-type-badge"
                                  class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border"
                                  style="color:#818cf8; background:rgba(99,102,241,0.12); border-color:rgba(99,102,241,0.25);">
                                ⬡ Class Diagram
                            </span>
                            <span id="ai-refine-toggle"
                                  title="Toggle refine mode — sends the last generated code as context"
                                  class="hidden text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border cursor-pointer transition-all select-none"
                                  style="color:rgba(255,255,255,0.35); border-color:rgba(255,255,255,0.12);">
                                ✦ Refine mode
                            </span>
                        </div>
                        <div class="flex gap-2.5 items-end">
                            <textarea id="ai-prompt" rows="2"
                                placeholder="e.g. 'e-commerce platform with payments and orders…'"
                                class="flex-1 rounded-2xl px-4 py-3 text-sm text-white placeholder-white/25 outline-none transition-all resize-none"
                                style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.09); font-size:13px; line-height:1.5;"></textarea>
                            <button id="ai-suggest-btn"
                                class="shrink-0 rounded-2xl px-5 py-3 font-black text-xs uppercase tracking-widest text-white flex items-center gap-2 transition-all"
                                style="background:linear-gradient(135deg,#6366F1,#8B5CF6); box-shadow:0 6px 20px rgba(99,102,241,0.30);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Send
                            </button>
                        </div>
                        <div class="flex justify-between items-center mt-2.5">
                            <button id="ai-cancel-btn"
                                class="text-white/20 hover:text-white/50 text-[10px] uppercase tracking-widest font-bold transition-colors">Close</button>
                            <button id="ai-use-btn"
                                class="hidden px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest text-white transition-all"
                                style="background:rgba(16,185,129,0.85); box-shadow:0 4px 14px rgba(16,185,129,0.22);">
                                ↗ Use in Create
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="diagram-lightbox" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-8 bg-black/95 cursor-zoom-out">
        <img src="" id="lightbox-img" class="max-w-full max-h-full rounded-xl shadow-2xl" />
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    if(window.__diagramHubInit){
        console.warn('DiagramHub script already initialized; skipping duplicate init.');
        return;
    }
    window.__diagramHubInit = true;
    const DIAGRAMS_BASE = "{{ route('dashboard.planning.diagrams.index', $board) }}";
    const CSRF = "{{ csrf_token() }}";
    const AI_GENERATE_URL = "{{ route('ai.generateUML') }}";
    const modal = document.getElementById('diagram-modal-inline');
    const lightbox = document.getElementById('diagram-lightbox');
    const aiModal = document.getElementById('ai-suggest-modal');
    const aiOpenBtn = document.getElementById('ai-open-btn');
    const aiCloseBtn = document.getElementById('ai-close-btn');
    const aiCancelBtn = document.getElementById('ai-cancel-btn');

    // Ensure Prism is present and allow dynamic theme switching
    let _prismThemeLink = null;
    function setPrismTheme(dark = true){
        try{
            if(!_prismThemeLink){
                _prismThemeLink = document.createElement('link');
                _prismThemeLink.rel = 'stylesheet';
                document.head.appendChild(_prismThemeLink);
            }
            _prismThemeLink.href = dark
                ? 'https://cdnjs.cloudflare.com/ajax/libs/prism-themes/1.9.0/prism-one-dark.min.css'
                : 'https://cdnjs.cloudflare.com/ajax/libs/prism-themes/1.9.0/prism-solarizedlight.min.css';
        }catch(e){ console.warn('prism theme swap failed', e); }
    }

    if(!window.Prism){
        setPrismTheme(true);
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js';
        document.head.appendChild(script);
    } else {
        setPrismTheme(true);
    }

    // Inject light-mode editor CSS for the inline editor toggle
    try{
        const _editorLightStyle = document.createElement('style');
        _editorLightStyle.textContent = `
            #diagram-editor-container.editor-light{ background:#ffffff; color:#0b1220; border-color:rgba(0,0,0,0.08); }
            #diagram-editor-container.editor-light .border-r{ border-color:rgba(0,0,0,0.04); }
            #diagram-editor-container.editor-light textarea{ color:#0b1220; background:transparent; }
            #diagram-editor-container.editor-light .px-4{ color:#0b1220; }
        `;
        document.head.appendChild(_editorLightStyle);
    }catch(e){ /* ignore */ }

    // (line numbers removed) remove related scrollbar-hiding CSS

    function openModal(mode = 'view', data = {}) {
        const isView = mode === 'view';
        
        document.getElementById('diagram-id-inline').value = data.id || '';
        document.getElementById('diagram-title-inline').value = data.title || '';
        document.getElementById('diagram-type-inline').value = data.type || '';
        document.getElementById('diagram-code-inline').value = data.code || '';
        document.getElementById('diagram-description-inline').value = data.description || '';
        
        const previewImg = document.getElementById('diagram-image-preview-img');
        const codeInput = document.getElementById('diagram-code-inline');
        const codePreview = document.getElementById('diagram-code-preview');
        const codeEl = document.getElementById('diagram-code-preview-code');
        const saveBtn = document.getElementById('diagram-save-inline');
        const imgInputWrap = document.getElementById('diagram-image-input-wrap');
        const changeLabel = document.querySelector('label[for="diagram-image-inline"]');
        const changeBtn = document.getElementById('diagram-change-image-btn');

        previewImg.src = data.image ? `/storage/${data.image}` : '';
        
        if(isView) {
            codeInput.classList.add('hidden');
            codePreview.classList.remove('hidden');
            codeEl.textContent = data.code || '// Empty Node';
            if(window.Prism) Prism.highlightElement(codeEl);
            saveBtn.classList.add('hidden');
            imgInputWrap.classList.add('hidden');
            // hide explicit change button in view mode
            if(changeBtn) changeBtn.classList.add('hidden');
            if(changeLabel) changeLabel.textContent = 'Add Image';
            document.querySelectorAll('#diagram-form-inline input').forEach(i => i.readOnly = true);
        } else {
            codeInput.classList.remove('hidden');
            codePreview.classList.add('hidden');
            saveBtn.classList.remove('hidden');
            imgInputWrap.classList.remove('hidden');
            // show explicit change button in edit/create mode and update labels
            if(changeBtn) changeBtn.classList.remove('hidden');
            // For create mode we prefer 'Add Image', for edit 'Change Image'
            if(mode === 'create') {
                if(changeLabel) changeLabel.textContent = 'Add Image';
                if(changeBtn) changeBtn.textContent = 'Add Image';
            } else {
                if(changeLabel) changeLabel.textContent = 'Change Image';
                if(changeBtn) changeBtn.textContent = 'Change';
            }
            document.querySelectorAll('#diagram-form-inline input').forEach(i => i.readOnly = false);
        }

        // line numbers removed — no dynamic numbering needed

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => modal.classList.add('modal-active'), 10);
    }

    // AI modal is intentionally minimal: advanced controls removed

    // Standard Actions
    document.addEventListener('click', (e) => {
        const openBtn = e.target.closest('.diagram-open') || e.target.closest('.diagram-card');
        if(openBtn && !e.target.closest('button.diagram-edit') && !e.target.closest('button.diagram-delete')) {
            const id = openBtn.getAttribute('data-id');
            const dataCard = document.getElementById(`diagram-card-${id}`);
            openModal('view', {
                id,
                title: dataCard.getAttribute('data-title'),
                type: dataCard.getAttribute('data-type'),
                code: dataCard.getAttribute('data-code'),
                image: dataCard.getAttribute('data-image')
            });
        }
        
        const editBtn = e.target.closest('.diagram-edit');
        if(editBtn) {
            const id = editBtn.getAttribute('data-id');
            const dataCard = document.getElementById(`diagram-card-${id}`);
            openModal('edit', {
                id,
                title: dataCard.getAttribute('data-title'),
                type: dataCard.getAttribute('data-type'),
                code: dataCard.getAttribute('data-code'),
                image: dataCard.getAttribute('data-image')
            });
        }
    });

    // Delete handler: send DELETE to server and remove card on success
    document.addEventListener('click', (e) => {
        const delBtn = e.target.closest('.diagram-delete');
        if(!delBtn) return;
        e.stopPropagation();
        console.trace('diagram delete clicked');
        const id = delBtn.getAttribute('data-id');
        if(!id) return;
        if(!confirm('Delete this diagram? This cannot be undone.')) return;
        delBtn.disabled = true;
        fetch(`${DIAGRAMS_BASE}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if(!res.ok) {
                console.error('Delete request returned non-OK:', res.status, res.statusText);
                return res.text().then(t => { console.error('Delete response body:', t); throw new Error('Delete failed: ' + res.status); });
            }
            return res.json();
        })
        .then(payload => {
            const card = document.getElementById(`diagram-card-${id}`);
            if(card){
                card.style.transition = 'opacity 0.25s, transform 0.25s';
                card.style.opacity = '0';
                card.style.transform = 'translateY(8px)';
                setTimeout(() => card.remove(), 280);
            } else {
                location.reload();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Delete failed — check the console');
            delBtn.disabled = false;
        });
    });

    // ── AI Chat System ──────────────────────────────────────────────
    const aiBtn = document.getElementById('ai-suggest-btn');
    const aiPromptEl = document.getElementById('ai-prompt');
    const aiMessages = document.getElementById('ai-chat-messages');
    const aiEmpty = document.getElementById('ai-chat-empty');
    const aiUseBtn = document.getElementById('ai-use-btn');
    const aiClearBtn = document.getElementById('ai-clear-btn');
    const aiRefineToggle = document.getElementById('ai-refine-toggle');
    const aiTypeBadge = document.getElementById('ai-active-type-badge');
    const AI_HISTORY_URL = "{{ route('ai.history') }}";
    const AI_STREAM_URL  = "{{ route('ai.generateUMLStream') }}";

    let lastPlantUml = '';
    let historyLoaded = false;
    let selectedDiagramType = 'class';
    let refineMode = false;
    let isSubmitting = false; // prevent duplicate form submissions

    // ── Diagram type selector ───────────────────────────────────────
    const TYPE_META = {
        class:     { label: '⬡ Class Diagram',  prompts: ['E-commerce platform', 'Blog with users & posts', 'Hospital management system', 'SaaS multi-tenant app'] },
        sequence:  { label: '↔ Sequence',        prompts: ['User login flow', 'Payment processing', 'API request/response cycle', 'Microservice communication'] },
        usecase:   { label: '◎ Use Case',         prompts: ['Online shopping', 'Social media platform', 'Banking application', 'HR management system'] },
        er:        { label: '⊞ ER Diagram',       prompts: ['E-commerce database', 'Project management DB', 'Healthcare records', 'University system'] },
        activity:  { label: '▶ Activity',         prompts: ['Checkout process', 'User registration', 'Order fulfillment', 'CI/CD pipeline'] },
        state:     { label: '◈ State Machine',    prompts: ['Order lifecycle', 'Ticket status flow', 'User account states', 'Payment states'] },
        component: { label: '⬡ Component',        prompts: ['Microservices arch', 'Frontend architecture', 'Data pipeline', 'IoT system layers'] },
    };

    function setDiagramType(type) {
        selectedDiagramType = type;
        document.querySelectorAll('.ai-type-btn').forEach(b => {
            b.classList.toggle('active-type', b.dataset.type === type);
        });
        const meta = TYPE_META[type] || TYPE_META.class;
        aiTypeBadge.textContent = meta.label;

        // Render quick prompts
        const qp = document.getElementById('ai-quick-prompts');
        qp.innerHTML = '';
        (meta.prompts || []).forEach(p => {
            const btn = document.createElement('button');
            btn.className = 'w-full text-left px-3 py-2 rounded-lg text-[11px] text-white/45 hover:text-white/80 hover:bg-white/5 transition-all leading-snug';
            btn.textContent = p;
            btn.addEventListener('click', () => {
                aiPromptEl.value = p;
                aiPromptEl.focus();
            });
            qp.appendChild(btn);
        });
    }

    // Editor toolbar actions: theme toggle, copy, wrap
    const editorContainer = document.getElementById('diagram-editor-container');
    const editorThemeToggle = document.getElementById('editor-theme-toggle');
    const editorCopyBtn = document.getElementById('editor-copy-btn');
    const editorToggleWrap = document.getElementById('editor-toggle-wrap');
    const codeInputEl = document.getElementById('diagram-code-inline');
    let editorIsLight = false;
    let wrapEnabled = true;

    function updateEditorThemeButton(){
        if(editorIsLight) editorThemeToggle.textContent = 'Dark Mode';
        else editorThemeToggle.textContent = 'Light Mode';
    }

    // initialize editor theme button label
    try{ updateEditorThemeButton(); }catch(e){}

    editorThemeToggle && editorThemeToggle.addEventListener('click', () => {
        editorIsLight = !editorIsLight;
        editorContainer.classList.toggle('editor-light', editorIsLight);
        setPrismTheme(!editorIsLight ? true : false);
        updateEditorThemeButton();
    });

    editorCopyBtn && editorCopyBtn.addEventListener('click', () => {
        try {
            navigator.clipboard.writeText(codeInputEl.value || '');
            editorCopyBtn.textContent = 'Copied';
            setTimeout(() => editorCopyBtn.textContent = 'Copy', 1200);
        } catch(e){
            alert('Copy failed — please select and copy manually');
        }
    });

    editorToggleWrap && editorToggleWrap.addEventListener('click', () => {
        wrapEnabled = !wrapEnabled;
        codeInputEl.style.whiteSpace = wrapEnabled ? 'pre-wrap' : 'pre';
        editorToggleWrap.textContent = wrapEnabled ? 'Toggle Wrap' : 'No Wrap';
    });

    // Sync preview and textarea scrolling (line numbers removed)
    try{
        const previewEl = document.getElementById('diagram-code-preview');
        if(codeInputEl && previewEl){
            codeInputEl.addEventListener('scroll', () => { previewEl.scrollTop = codeInputEl.scrollTop; });
            previewEl.addEventListener('scroll', () => { codeInputEl.scrollTop = previewEl.scrollTop; });
        }
    }catch(e){ console.warn('scroll sync failed', e); }

    // Line numbering removed — no dynamic numbering needed

    document.getElementById('ai-type-selector').addEventListener('click', e => {
        const btn = e.target.closest('.ai-type-btn');
        if (btn) setDiagramType(btn.dataset.type);
    });

    // initialise
    setDiagramType('class');

    // ── Refine mode toggle ──────────────────────────────────────────
    if (aiRefineToggle) {
        aiRefineToggle.addEventListener('click', () => {
            refineMode = !refineMode;
            aiRefineToggle.style.color = refineMode ? '#a5b4fc' : 'rgba(255,255,255,0.35)';
            aiRefineToggle.style.borderColor = refineMode ? 'rgba(99,102,241,0.5)' : 'rgba(255,255,255,0.12)';
            aiRefineToggle.title = refineMode ? 'Refine mode ON — will send last code as context' : 'Toggle refine mode';
        });
    }

    // ── Clear chat ──────────────────────────────────────────────────
    if (aiClearBtn) {
        aiClearBtn.addEventListener('click', () => {
            // remove all message wrappers (everything except the empty state)
            [...aiMessages.children].forEach(c => { if (c.id !== 'ai-chat-empty') c.remove(); });
            aiEmpty.classList.remove('hidden');
            lastPlantUml = '';
            if (aiUseBtn) aiUseBtn.classList.add('hidden');
            if (aiRefineToggle) aiRefineToggle.classList.add('hidden');
        });
    }

    function escHtml(s) {
        const d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }

    function addMessage(role, content, meta) {
        if (aiEmpty) aiEmpty.classList.add('hidden');
        const wrap = document.createElement('div');
        wrap.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';

        const bubble = document.createElement('div');

        if (role === 'user') {
            bubble.className = 'max-w-[80%] rounded-2xl rounded-br-md px-4 py-3';
            bubble.style.cssText = 'background:rgba(99,102,241,0.18); border:1px solid rgba(99,102,241,0.28);';
            bubble.innerHTML = '<p class="text-sm text-indigo-200 whitespace-pre-wrap" style="font-size:13px;">' + escHtml(content) + '</p>';
        } else if (role === 'progress') {
            bubble.id = 'ai-progress-bubble';
            bubble.className = 'max-w-[85%] rounded-2xl rounded-bl-md px-4 py-3';
            bubble.style.cssText = 'background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07);';
            bubble.innerHTML = '<div id="ai-step-list" class="space-y-2 min-w-[220px]"></div>';
        } else {
            // AI response
            bubble.className = 'w-full max-w-[90%]';
            let html = '';
            if (content) {
                html += '<div class="rounded-2xl overflow-hidden" style="background:#071017; border:1px solid rgba(255,255,255,0.07);">';
                html += '<div class="flex items-center justify-between px-4 py-2" style="background:rgba(255,255,255,0.04); border-bottom:1px solid rgba(255,255,255,0.06);">';
                html += '<span class="text-[10px] font-black uppercase tracking-widest" style="color:rgba(165,180,252,0.7);">PlantUML · ' + (TYPE_META[selectedDiagramType]?.label || 'Diagram') + '</span>';
                html += '<button class="ai-copy-btn text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-lg transition-all" style="color:#a5b4fc; background:rgba(99,102,241,0.18); border:1px solid rgba(99,102,241,0.30);" data-code="' + content.replace(/"/g, '&quot;') + '">Copy</button>';
                html += '</div>';
                html += '<pre class="overflow-auto p-4 text-sm font-mono" style="color:#93c5fd; max-height:280px; font-size:12px; line-height:1.6;">' + escHtml(content) + '</pre>';
                html += '</div>';
            } else {
                html += '<p class="text-sm" style="color:#f87171;">No PlantUML generated. Ensure LLM_ENABLED=true in .env</p>';
            }
            if (meta) {
                const parts = [];
                if (meta.status) parts.push(meta.status);
                if (meta.duration_ms) parts.push(meta.duration_ms + 'ms');
                if (meta.retries > 0) parts.push(meta.retries + ' retries');
                if (parts.length) html += '<p class="text-[10px] mt-1.5 pl-1" style="color:rgba(255,255,255,0.18);">' + escHtml(parts.join(' · ')) + '</p>';
            }
            bubble.innerHTML = html;
        }

        wrap.appendChild(bubble);
        aiMessages.appendChild(wrap);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        return wrap;
    }

    // Copy button handler (delegated)
    aiMessages.addEventListener('click', e => {
        const btn = e.target.closest('.ai-copy-btn');
        if (!btn) return;
        const code = btn.getAttribute('data-code') || '';
        navigator.clipboard.writeText(code).then(() => {
            btn.textContent = '✓ Copied';
            btn.style.color = '#6ee7b7';
            setTimeout(() => { btn.textContent = 'Copy'; btn.style.color = 'rgba(255,255,255,0.35)'; }, 1800);
        }).catch(() => {
            // fallback
            const ta = document.createElement('textarea');
            ta.value = code; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select(); document.execCommand('copy');
            document.body.removeChild(ta);
            btn.textContent = '✓ Copied'; setTimeout(() => { btn.textContent = 'Copy'; }, 1800);
        });
    });

    function removeProgress() {
        const b = document.getElementById('ai-progress-bubble');
        if (b && b.parentElement) b.parentElement.remove();
    }

    const STEP_ICONS = {
        analyzing:  { icon: '🔍', label: 'Analyzing prompt…' },
        generating: { icon: '⚙️', label: 'Generating diagram…' },
        optimizing: { icon: '✨', label: 'Optimizing output…' },
        retrying:   { icon: '🔄', label: 'Refining…' },
        exploring:  { icon: '🧭', label: 'Exploring alternatives…' },
        cached:     { icon: '⚡', label: 'Loaded from cache' },
        complete:   { icon: '✅', label: 'Done' },
    };

    function updateStepIndicator(step, message) {
        const list = document.getElementById('ai-step-list');
        if (!list) return;
        list.querySelectorAll('[data-step-status="active"]').forEach(el => {
            el.setAttribute('data-step-status', 'done');
            const dot = el.querySelector('.step-dot');
            if (dot) { dot.textContent = '✓'; dot.className = 'step-dot text-green-400 text-xs w-5 text-center shrink-0'; }
            const txt = el.querySelector('.step-text');
            if (txt) txt.className = 'step-text text-xs text-white/30';
        });
        if (step === 'complete') return;
        const info = STEP_ICONS[step] || { icon: '●', label: message };
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';
        row.setAttribute('data-step-status', 'active');
        row.innerHTML = '<span class="step-dot text-blue-400 text-xs w-5 text-center shrink-0 animate-pulse">' + info.icon + '</span>'
            + '<span class="step-text text-xs text-white/60">' + escHtml(message || info.label) + '</span>';
        list.appendChild(row);
        aiMessages.scrollTop = aiMessages.scrollHeight;
    }

    function sendAiMessage() {
        const prompt = (aiPromptEl.value || '').trim();
        if (!prompt) return;

        let fullPrompt = prompt;
        if (refineMode && lastPlantUml) {
            fullPrompt = 'Refine the following diagram based on this request: ' + prompt
                + '\n\nExisting diagram:\n' + lastPlantUml;
        }

        addMessage('user', refineMode ? '✦ [Refine] ' + prompt : prompt);
        aiPromptEl.value = '';
        addMessage('progress', '');
        aiBtn.disabled = true;
        aiBtn.style.opacity = '0.6';
        updateStepIndicator('analyzing', 'Analyzing your prompt…');

        const payload = {
            input: fullPrompt,
            board_id: "{{ $board->id }}",
            diagram_type: selectedDiagramType,
            temperature: 0.3,
            top_p: 0.95,
            style: 'precise',
            search_radius: 2,
            max_tokens: 3000,
        };

        fetch(AI_STREAM_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) throw new Error('Stream request failed');
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            function processChunk() {
                return reader.read().then(({ done, value }) => {
                    if (done) {
                        if (!lastPlantUml) { removeProgress(); addMessage('ai', '', {}); }
                        aiBtn.disabled = false; aiBtn.style.opacity = '';
                        return;
                    }
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop();
                    let currentEvent = '';
                    for (const line of lines) {
                        if (line.startsWith('event: ')) {
                            currentEvent = line.substring(7).trim();
                        } else if (line.startsWith('data: ')) {
                            const raw = line.substring(6);
                            try {
                                const data = JSON.parse(raw);
                                if (currentEvent === 'progress') {
                                    updateStepIndicator(data.step, data.message);
                                } else if (currentEvent === 'complete') {
                                    removeProgress();
                                    let plant = (data.plantuml || '').trim();
                                    if (plant) {
                                        try { const ta = document.createElement('textarea'); ta.innerHTML = plant; plant = ta.value; } catch(e){}
                                        lastPlantUml = plant.trim();
                                    }
                                    addMessage('ai', lastPlantUml || '', {});
                                    if (lastPlantUml) {
                                        if (aiUseBtn) aiUseBtn.classList.remove('hidden');
                                        if (aiRefineToggle) aiRefineToggle.classList.remove('hidden');
                                    }
                                    aiBtn.disabled = false; aiBtn.style.opacity = '';
                                } else if (currentEvent === 'error') {
                                    removeProgress();
                                    addMessage('ai', '');
                                    aiBtn.disabled = false; aiBtn.style.opacity = '';
                                }
                            } catch(e) {}
                            currentEvent = '';
                        }
                    }
                    return processChunk();
                });
            }
            return processChunk();
        })
        .catch(err => {
            removeProgress();
            addMessage('ai', '');
            aiBtn.disabled = false; aiBtn.style.opacity = '';
        });
    }

    function loadHistory() {
        if (historyLoaded) return;
        historyLoaded = true;
        fetch(AI_HISTORY_URL + '?type=uml&limit=20', { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!data.history || !data.history.length) return;
                const items = data.history.reverse();
                items.forEach(h => {
                    addMessage('user', h.input);
                    let output = h.output || '';
                    try {
                        const parsed = JSON.parse(output);
                        if (Array.isArray(parsed) && parsed[0]) output = parsed[0];
                        else if (parsed.plantuml) output = parsed.plantuml;
                    } catch(e) {}
                    addMessage('ai', output, { status: h.status, duration_ms: h.duration_ms, retries: h.retries });
                    if (output) lastPlantUml = output;
                });
                if (lastPlantUml) {
                    if (aiUseBtn) aiUseBtn.classList.remove('hidden');
                    if (aiRefineToggle) aiRefineToggle.classList.remove('hidden');
                }
            })
            .catch(err => console.warn('History load failed:', err));
    }

    if (aiBtn) {
        aiBtn.addEventListener('click', function(e) { e.preventDefault(); sendAiMessage(); });
    }
    if (aiPromptEl) {
        aiPromptEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAiMessage(); }
        });
    }

    if(aiOpenBtn && aiModal){
        aiOpenBtn.addEventListener('click', function(e){ e.preventDefault(); aiModal.classList.remove('hidden'); aiModal.classList.add('flex'); loadHistory(); });
    }
    if(aiCloseBtn){ aiCloseBtn.addEventListener('click', function(){ aiModal.classList.add('hidden'); aiModal.classList.remove('flex'); }); }
    if(aiCancelBtn){ aiCancelBtn.addEventListener('click', function(){ aiModal.classList.add('hidden'); aiModal.classList.remove('flex'); }); }

    if(aiUseBtn){
        aiUseBtn.addEventListener('click', function(){
            if(!lastPlantUml || !lastPlantUml.trim()) { return; }
            openModal('create', { id: '', title: 'AI Diagram', type: selectedDiagramType, code: lastPlantUml, image: '' });
            if(aiModal){ aiModal.classList.add('hidden'); aiModal.classList.remove('flex'); }
        });
    }

    // Backdrop click to close AI modal
    if(aiModal) {
        aiModal.addEventListener('click', e => { if (e.target === aiModal) { aiModal.classList.add('hidden'); aiModal.classList.remove('flex'); } });
    }

    // Lightbox Functionality: only open when image-input is not visible (view mode)
    (function(){
        const previewEl = document.getElementById('diagram-image-preview');
        if(!previewEl) return;
        previewEl.onclick = (e) => {
            const imgInputWrap = document.getElementById('diagram-image-input-wrap');
            if(imgInputWrap && !imgInputWrap.classList.contains('hidden')) {
                // input visible (edit mode) -> don't open lightbox; let file input handle clicks
                return;
            }
            const imgEl = document.getElementById('diagram-image-preview-img');
            const src = imgEl ? imgEl.src : null;
            if(src && !src.endsWith('/')) {
                const lbImg = document.getElementById('lightbox-img');
                if(lbImg) lbImg.src = src;
                if(lightbox) {
                    lightbox.classList.remove('hidden');
                    setTimeout(() => lightbox.classList.add('active'), 10);
                }
            }
        };
    })();

    if(lightbox) {
        lightbox.onclick = () => {
            lightbox.classList.remove('active');
            setTimeout(() => lightbox.classList.add('hidden'), 400);
        };
    }

    // File input change -> preview selected image immediately
    const imageInput = document.getElementById('diagram-image-inline');
    if(imageInput){
        imageInput.addEventListener('change', function(){
            if(this.files && this.files[0]){
                const url = URL.createObjectURL(this.files[0]);
                const previewImg = document.getElementById('diagram-image-preview-img');
                previewImg.src = url;
            }
        });

        // Some browsers block file dialog when input is `display:none` or wrapped; provide explicit trigger
        const changeLabel = document.querySelector('label[for="diagram-image-inline"]');
        if(changeLabel){
            changeLabel.addEventListener('click', function(e){
                // ensure input is enabled
                if(imageInput.disabled) imageInput.disabled = false;
                try { imageInput.click(); } catch(err){ /* ignore */ }
            });
        }

        // Also allow clicking the preview container in edit mode to open picker
        const previewWrap = document.getElementById('diagram-image-preview');
        if(previewWrap){
            previewWrap.addEventListener('click', function(e){
                const imgInputWrap = document.getElementById('diagram-image-input-wrap');
                if(imgInputWrap && imgInputWrap.classList.contains('hidden')) return; // view mode: don't trigger
                if(imageInput.disabled) imageInput.disabled = false;
                try { imageInput.click(); } catch(err){}
            });
        }

        // Explicit change button handler
        const changeBtn = document.getElementById('diagram-change-image-btn');
        if(changeBtn){
            changeBtn.addEventListener('click', function(e){
                e.preventDefault();
                if(imageInput.disabled) imageInput.disabled = false;
                try { imageInput.click(); } catch(err){}
            });
        }
    }

    const closeX = document.getElementById('diagram-close-x');
    if(closeX){
        closeX.onclick = () => {
            if(modal) modal.classList.remove('modal-active');
            setTimeout(() => { if(modal) modal.classList.add('hidden'); }, 300);
        };
    }

    const cancelBtn = document.getElementById('diagram-cancel-inline');
    if(cancelBtn && closeX){
        cancelBtn.onclick = () => closeX.click();
    }

    const createBtn = document.getElementById('diagram-create-inline');
    if(createBtn){
        createBtn.onclick = () => openModal('create', {});
    }
    // PlantUML logo fallback: if external image fails, show inline SVG
    (function(){
        const img = document.getElementById('plantuml-logo-img');
        const fallback = document.getElementById('plantuml-logo-fallback');
        if(!img) return;
        function showFallback(){ if(img) img.classList.add('hidden'); if(fallback) fallback.classList.remove('hidden'); }
        img.addEventListener('error', showFallback);
        // in case it's cached but invalid
        if(img.complete && img.naturalWidth === 0) showFallback();
    })();

    // Form submission: upload fields + optional image
    const formEl = document.getElementById('diagram-form-inline');
    if(formEl){
        formEl.addEventListener('submit', function(e){
            e.preventDefault();
            console.trace('diagram form submit');
            if(isSubmitting) { console.warn('submit ignored, already submitting'); return; } // already in progress
            isSubmitting = true;

            const btn = document.getElementById('diagram-save-inline');
            if(btn) { btn.disabled = true; btn.textContent = btn.getAttribute('data-loading-text') || 'Syncing...'; }

            const id = document.getElementById('diagram-id-inline').value;
            const fd = new FormData(formEl);
            let url = DIAGRAMS_BASE;
            if(id){ fd.append('_method','PATCH'); url = `${DIAGRAMS_BASE}/${id}`; }

            // log minimal payload keys for debugging duplicate submissions
            try { console.log('diagram submit payload keys:', Array.from(fd.keys())); } catch(e){}
            fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd })
                .then(res => {
                    if(!res.ok) throw new Error('Save failed');
                    return res.json();
                })
                .then(payload => {
                    // close modal and reload to reflect changes
                    document.getElementById('diagram-close-x').click();
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('Save failed — check the console');
                    if(btn) { btn.disabled = false; btn.textContent = 'Sync Changes'; }
                    isSubmitting = false;
                });
        });
    }

    requestAnimationFrame(() => {
        const page = document.getElementById('diagrams-page');
        if (page) page.classList.add('is-ready');
    });
});
</script>
@endsection