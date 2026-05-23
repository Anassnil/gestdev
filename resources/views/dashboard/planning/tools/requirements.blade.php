@extends('layouts.dashboard')

@section('dashboard-content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    :root {
        --lm-bg: #F8FAFC;
        --lm-surface: #F1F5F9;
        --lm-primary: #6366F1;
        --accent-glow: rgba(99, 102, 241, 0.15);
        --matrix-border: rgba(255, 255, 255, 0.05);
    }

    /* ─── ATMOSPHERIC BASE ─── */
    .req-page {
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.4s ease-out;
        background: #020205;
        background-image: 
            radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 100% 100%, rgba(6, 182, 212, 0.03) 0%, transparent 50%);
    }
    .req-page.is-ready { opacity: 1; transform: translateY(0); }

    /* ─── GLASS PANEL & MATRIX ─── */
    .glass-panel { 
        background: linear-gradient(145deg, rgba(30, 32, 70, 0.2) 0%, rgba(10, 11, 40, 0.4) 100%);
        backdrop-filter: blur(12px); 
        border: 1px solid var(--matrix-border);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .matrix-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        min-height: 700px;
    }

    .matrix-column {
        display: flex;
        flex-direction: column;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.01);
        border: 1px solid rgba(255, 255, 255, 0.03);
        padding: 1.5rem;
    }

    /* ─── REQUIREMENT CARDS ─── */
    .retro-card { 
        background: rgba(255, 255, 255, 0.03); 
        border: 1px solid rgba(255, 255, 255, 0.06); 
        border-radius: 16px;
        padding: 1.25rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        margin-bottom: 1rem;
    }

    .retro-card:hover { 
        background: rgba(255, 255, 255, 0.08); 
        border-color: rgba(99, 102, 241, 0.4);
        transform: translateY(-3px);
        box-shadow: 0 15px 30px -10px rgba(0,0,0,0.6);
    }

    .priority-indicator {
        position: absolute;
        left: 0; top: 15%; bottom: 15%;
        width: 3px;
        border-radius: 0 4px 4px 0;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    .pulse-blue { background: #6366f1; box-shadow: 0 0 8px rgba(99, 102, 241, 0.6); }
    .pulse-amber { background: #f59e0b; box-shadow: 0 0 8px rgba(245, 158, 11, 0.6); }

    .tag-pill {
        font-size: 7px;
        font-weight: 900;
        padding: 2px 6px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
    }

    /* ─── TYPOGRAPHY ─── */
    .heading-cyber { font-family: 'Inter', sans-serif; font-weight: 900; font-style: italic; text-transform: uppercase; letter-spacing: -0.03em; }
    .label-cyber { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.2em; color: rgba(255, 255, 255, 0.4); }

    .custom-scroll {
        max-height: 60vh;
        overflow-y: auto;
        padding-right: 10px;
    }
    .custom-scroll::-webkit-scrollbar { width: 3px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }

    /* ─── LIGHT MODE ─── */
    [data-theme="light"] .req-page {
        background: #F8FAFC;
        background-image:
            radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.06) 0%, transparent 50%),
            radial-gradient(circle at 100% 100%, rgba(6, 182, 212, 0.04) 0%, transparent 50%);
        color: #1F2937;
    }

    [data-theme="light"] .glass-panel {
        background: linear-gradient(145deg, #ffffff 0%, #F1F5F9 100%);
        border-color: rgba(31, 41, 55, 0.10);
        box-shadow: 0 12px 28px -16px rgba(31, 41, 55, 0.16);
    }

    [data-theme="light"] .matrix-column {
        background: #ffffff;
        border-color: rgba(31, 41, 55, 0.08);
    }

    [data-theme="light"] .retro-card {
        background: #ffffff;
        border-color: rgba(31, 41, 55, 0.10);
        box-shadow: 0 4px 12px -6px rgba(31, 41, 55, 0.12);
    }
    [data-theme="light"] .retro-card:hover {
        background: #F1F5F9;
        border-color: rgba(99, 102, 241, 0.35);
        box-shadow: 0 12px 24px -10px rgba(99, 102, 241, 0.18);
    }

    [data-theme="light"] .label-cyber { color: rgba(31, 41, 55, 0.50); }

    [data-theme="light"] .tag-pill {
        background: #EEF2FF;
        border-color: rgba(99, 102, 241, 0.22);
        color: #6366F1;
    }

    [data-theme="light"] .custom-scroll::-webkit-scrollbar-thumb {
        background: rgba(31, 41, 55, 0.18);
    }

    /* text utilities */
    [data-theme="light"] .text-white { color: #1F2937 !important; }
    [data-theme="light"] .text-white\/80 { color: rgba(31,41,55,0.85) !important; }
    [data-theme="light"] .text-white\/70 { color: rgba(31,41,55,0.72) !important; }
    [data-theme="light"] .text-white\/40,
    [data-theme="light"] .text-white\/50 { color: rgba(31,41,55,0.50) !important; }
    [data-theme="light"] .text-white\/20,
    [data-theme="light"] .text-white\/10 { color: rgba(31,41,55,0.28) !important; }
    [data-theme="light"] .text-indigo-400,
    [data-theme="light"] .text-indigo-500 { color: #6366F1 !important; }

    /* bg utilities */
    [data-theme="light"] .bg-black\/40 {
        background: rgba(31,41,55,0.05) !important;
    }
    [data-theme="light"] .bg-black\/90 {
        background: rgba(31,41,55,0.25) !important;
    }

    /* border utilities */
    [data-theme="light"] .border-white\/10,
    [data-theme="light"] .border-white\/5 {
        border-color: rgba(31,41,55,0.10) !important;
    }

    /* form inputs inside modal */
    [data-theme="light"] input,
    [data-theme="light"] textarea,
    [data-theme="light"] select {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.14) !important;
        color: #1F2937 !important;
    }
    [data-theme="light"] input::placeholder,
    [data-theme="light"] textarea::placeholder {
        color: rgba(31,41,55,0.35) !important;
    }
    [data-theme="light"] input:focus,
    [data-theme="light"] textarea:focus,
    [data-theme="light"] select:focus {
        border-color: #6366F1 !important;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.10) !important;
        outline: none !important;
    }

    /* header buttons */
    [data-theme="light"] .bg-indigo-600 { background-color: #6366F1 !important; }
    [data-theme="light"] .hover\:bg-indigo-500:hover { background-color: #575be8 !important; }

    /* ─── MODAL (sprint_planning-style) ─── */
    .req-modal-overlay {
        position: fixed; inset: 0;
        background: rgba(2, 6, 23, 0.52);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        display: flex; align-items: center; justify-content: center;
        padding: 1.5rem;
        z-index: 50;
    }
    .req-modal-panel {
        background: #10142a;
        border-radius: 2.5rem;
        border: 1px solid rgba(255,255,255,0.12);
        border-top: 2px solid rgba(99,102,241,0.5);
        padding: 2.5rem;
        width: 100%; max-width: 56rem;
        box-shadow: 0 22px 38px -16px rgba(0,0,0,0.6);
    }
    .req-modal-cancel-btn {
        font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.2em;
        color: rgba(255,255,255,0.4); padding: 8px 16px; border-radius: 10px;
        background: rgba(255,255,255,0.06); transition: all 0.2s ease;
    }
    .req-modal-cancel-btn:hover { color: #fff; background: rgba(255,255,255,0.10); }
    .req-modal-confirm-btn {
        background: #6366F1; color: #fff; padding: 12px 2.5rem;
        border-radius: 12px; font-size: 10px; font-weight: 900;
        text-transform: uppercase; letter-spacing: 0.1em; transition: all 0.2s ease;
        box-shadow: 0 6px 20px rgba(99,102,241,0.25);
    }
    .req-modal-confirm-btn:hover { background: #5558e8; }
    [data-theme="light"] .req-modal-overlay { background: rgba(31,41,55,0.28); }
    [data-theme="light"] .req-modal-panel {
        background: #F8FAFC;
        border-color: rgba(31,41,55,0.12);
        border-top-color: rgba(99,102,241,0.35);
        box-shadow: 0 22px 38px -20px rgba(31,41,55,0.28);
    }
    [data-theme="light"] .req-modal-cancel-btn { color: rgba(31,41,55,0.55); background: rgba(31,41,55,0.06); }
    [data-theme="light"] .req-modal-cancel-btn:hover { color: #1F2937; background: rgba(31,41,55,0.10); }

    @media (prefers-reduced-motion: reduce) {
        .req-page { transition: none !important; }
        .retro-card { transition: none !important; }
    }
</style>

<div id="req-page" class="req-page pt-8 px-10 pb-12 min-h-screen text-white">
    <div class="max-w-[1700px] mx-auto space-y-10">
        
        <!-- HEADER -->
        <header class="flex justify-between items-end">
            <div>
                <nav class="label-cyber mb-2">Requirements // <span class="text-indigo-400">Logic Matrix</span></nav>
                <h1 class="text-4xl heading-cyber text-white">System <span class="text-indigo-500">Blueprint</span> — {{ $board->name }}</h1>
            </div>
            <div class="flex gap-3">
                <button id="openCreateRequirementModal" class="px-8 py-3 bg-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-white hover:text-black transition-all shadow-xl shadow-indigo-600/10">
                    + New Requirement
                </button>
                <a href="{{ route('dashboard.planning.show', $board) }}" class="px-8 py-3 glass-panel rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-white hover:text-black transition-all">
                    Exit to Control
                </a>
            </div>
        </header>

        <!-- MATRIX CONTENT -->
        <div class="matrix-grid">
            @foreach(['functional' => 'Functional Specs', 'non-functional' => 'System Quality', 'business' => 'Business Values', 'technical' => 'Technical Debt'] as $key => $label)
            <div class="matrix-column glass-panel">
                <div class="flex justify-between items-center mb-8 border-b border-white/5 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 shadow-[0_0_8px_#6366f1]"></div>
                        <h3 class="label-cyber text-white/80">{{ $label }}</h3>
                    </div>
                    <span id="count-{{ $key }}" class="text-[10px] font-mono text-white/20">00</span>
                </div>

                <div id="list-{{ $key }}" class="custom-scroll space-y-4">
                    <!-- Cards Injected via JS -->
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- MODAL -->
<div id="createRequirementModal" class="req-modal-overlay" style="display:none">
    <div class="req-modal-panel">
        <h2 class="heading-cyber text-2xl mb-8 text-white">Initialize Requirement</h2>
        <form id="createRequirement" class="grid grid-cols-12 gap-6">
            @csrf
            <div class="col-span-12 lg:col-span-8">
                <label class="label-cyber block mb-2">Requirement Title</label>
                <input name="title" required class="w-full bg-black/40 border border-white/10 rounded-xl px-5 py-4 text-sm text-white focus:border-indigo-500 outline-none transition-all" placeholder="e.g., Secure OAuth Flow">
            </div>
            <div class="col-span-12 lg:col-span-4">
                <label class="label-cyber block mb-2">Category</label>
                <select name="type" class="w-full bg-black/40 border border-white/10 rounded-xl px-5 py-4 text-xs font-bold uppercase outline-none text-white">
                    <option value="functional">Functional</option>
                    <option value="non-functional">Non-Functional</option>
                    <option value="business">Business</option>
                    <option value="technical">Technical</option>
                </select>
            </div>
            <div class="col-span-12">
                <label class="label-cyber block mb-2">Scope Description</label>
                <textarea name="description" rows="4" class="w-full bg-black/40 border border-white/10 rounded-xl px-5 py-4 text-sm text-white/70 outline-none resize-none"></textarea>
            </div>
            <div class="col-span-12 flex justify-end gap-4 mt-4">
                <button type="button" onclick="closeModal()" class="req-modal-cancel-btn">Cancel</button>
                <button type="submit" class="req-modal-confirm-btn">Commit to Matrix</button>
            </div>
        </form>
    </div>
</div>

@php
    $requirementsPayload = $requirements->map(fn($r) => [
        'id'                  => $r->id,
        'title'               => $r->title,
        'description'         => $r->description,
        'type'                => $r->type,
        'priority'            => $r->priority,
        'status'              => $r->status ?? null,
        'acceptance_criteria' => $r->acceptance_criteria ?? null,
        'tags'                => is_array($r->tags) ? $r->tags : json_decode($r->tags ?? '[]'),
        'position'            => $r->position,
        'created_at'          => $r->created_at?->toDateTimeString(),
    ])->values();
@endphp

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    let requirements = @json($requirementsPayload);
    const boardId = {{ $board->id }};
    const token = "{{ csrf_token() }}";

    function renderMatrix() {
        const types = ['functional', 'non-functional', 'business', 'technical'];
        
        types.forEach(type => {
            const container = document.getElementById(`list-${type}`);
            const filtered = requirements.filter(r => r.type === type);
            
            document.getElementById(`count-${type}`).textContent = String(filtered.length).padStart(2, '0');
            container.innerHTML = '';

            filtered.forEach(r => {
                const pColors = { critical: '#ef4444', high: '#f59e0b', medium: '#6366f1', low: '#64748b' };
                const card = document.createElement('div');
                card.className = 'retro-card group';
                
                card.innerHTML = `
                    <div class="priority-indicator" style="background: ${pColors[r.priority] || '#64748b'}"></div>
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-2">
                            <span class="status-dot ${r.status === 'approved' ? 'pulse-blue' : 'pulse-amber'}"></span>
                            <span class="label-cyber !text-[7px] text-white/40">${r.status || 'Draft'}</span>
                        </div>
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                            <button onclick="deleteReq(${r.id})" class="text-white/20 hover:text-red-400"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </div>
                    <h4 class="text-sm font-bold text-white mb-2">${r.title}</h4>
                    <p class="text-[11px] text-white/40 line-clamp-2 leading-relaxed mb-4">${r.description || ''}</p>
                    <div class="flex flex-wrap gap-2">
                        ${(r.tags || []).map(t => `<span class="tag-pill">${t}</span>`).join('')}
                    </div>
                `;
                container.appendChild(card);
            });
        });
        lucide.createIcons();
    }

    // Modal Logic (sprint_planning pattern: style.display, no class toggling)
    function closeModal() { document.getElementById('createRequirementModal').style.display = 'none'; }
    document.getElementById('openCreateRequirementModal').onclick = () => { document.getElementById('createRequirementModal').style.display = 'flex'; };
    document.getElementById('createRequirementModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });

    // Submit Logic
    document.getElementById('createRequirement').onsubmit = async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const res = await fetch(`/dashboard/planning/${boardId}/requirements/items`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: fd
        });
        if(res.ok) {
            const data = await res.json();
            requirements.push(data.requirement);
            renderMatrix();
            closeModal();
            this.reset();
        }
    };

    window.onload = () => {
        renderMatrix();
        document.getElementById('req-page').classList.add('is-ready');
    };
</script>
@endsection