@extends('layouts.dashboard')

@section('dashboard-content')
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('dashboard.planning._permission')

<style>
    :root {
        --lm-bg: #F8FAFC;
        --lm-surface: #F1F5F9;
        --lm-primary: #6366F1;
        --lm-border: rgba(31,41,55,0.10);
        --lm-text: #1F2937;
        --lm-muted: rgba(31,41,55,0.50);
    }

    .glass-panel { background: rgba(10, 11, 40, 0.5); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); }
    .label-cyber { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.25em; color: rgba(255, 255, 255, 0.4); }
    .heading-cyber { font-family: 'Inter', sans-serif; font-weight: 900; font-style: italic; text-transform: uppercase; letter-spacing: -0.02em; }
    
    .input-cyber { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; transition: all 0.2s; }
    .input-cyber:focus { border-color: #3b82f6; outline: none; background: rgba(0, 0, 0, 0.5); }

    .retro-card { 
        background: rgba(255, 255, 255, 0.03); 
        border: 1px solid rgba(255, 255, 255, 0.06); 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .quadrant { min-height: 260px; transition: all 0.3s; }
    .quad-do_first { border-top: 4px solid #ef4444 !important; }
    .quad-schedule { border-top: 4px solid #3b82f6 !important; }
    .quad-delegate { border-top: 4px solid #f59e0b !important; }
    .quad-later { border-top: 4px solid #64748b !important; }

    .progress-ring__circle {
        transition: stroke-dashoffset 0.5s ease-out;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
    }

    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.3); border-radius: 10px; }

    /* ─── PAGE LOAD ─── */
    .retro-page {
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 220ms ease, transform 220ms ease;
    }
    .retro-page.is-ready { opacity: 1; transform: translateY(0); }

    /* ─── LIGHT MODE ─── */
    [data-theme="light"] .retro-page {
        background: var(--lm-bg);
        color: var(--lm-text);
    }
    [data-theme="light"] .glass-panel {
        background: #ffffff;
        border-color: var(--lm-border);
        box-shadow: 0 8px 24px -12px rgba(31,41,55,0.14);
    }
    [data-theme="light"] .label-cyber { color: var(--lm-muted); }
    [data-theme="light"] .input-cyber {
        background: #ffffff;
        border-color: var(--lm-border);
        color: var(--lm-text);
    }
    [data-theme="light"] .input-cyber::placeholder { color: rgba(31,41,55,0.35); }
    [data-theme="light"] .input-cyber:focus {
        border-color: #3b82f6;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.10);
    }
    [data-theme="light"] .retro-card {
        background: var(--lm-surface);
        border-color: var(--lm-border);
    }
    [data-theme="light"] .retro-card:hover {
        background: #e8edf5;
        border-color: rgba(59,130,246,0.30);
    }
    [data-theme="light"] .quadrant {
        background: #ffffff;
        border-color: var(--lm-border);
    }
    /* stats section */
    [data-theme="light"] .bg-white\/\[0\.02\] {
        background: var(--lm-surface) !important;
    }
    [data-theme="light"] .border-white\/5 {
        border-color: var(--lm-border) !important;
    }
    [data-theme="light"] .circle.text-white\/5 { color: rgba(31,41,55,0.10) !important; }
    /* text utilities */
    [data-theme="light"] .text-white { color: var(--lm-text) !important; }
    [data-theme="light"] .text-white\/90 { color: rgba(31,41,55,0.90) !important; }
    [data-theme="light"] .text-white\/70 { color: rgba(31,41,55,0.70) !important; }
    [data-theme="light"] .text-white\/50 { color: rgba(31,41,55,0.52) !important; }
    [data-theme="light"] .text-white\/30 { color: rgba(31,41,55,0.40) !important; }
    [data-theme="light"] .text-white\/20 { color: rgba(31,41,55,0.28) !important; }
    /* header link button */
    [data-theme="light"] a.bg-blue-600 { background-color: #6366F1; color: #fff; }
    [data-theme="light"] a.hover\:bg-white:hover { background-color: var(--lm-text) !important; color: #fff !important; }
    /* submit button */
    [data-theme="light"] button[type="submit"] {
        background: var(--lm-text);
        color: #fff;
    }
    [data-theme="light"] button[type="submit"]:hover {
        background: #3b82f6;
        color: #fff;
    }
    /* finalize button */
    [data-theme="light"] .bg-blue-600 { background-color: #6366F1 !important; }
    [data-theme="light"] .hover\:bg-white:hover { background-color: var(--lm-text) !important; color: #fff !important; }
    /* scrollbar */
    [data-theme="light"] .custom-scroll::-webkit-scrollbar-thumb { background: rgba(31,41,55,0.18); }
    /* feedback count badge */
    [data-theme="light"] .bg-emerald-500\/10 { background: rgba(16,185,129,0.08) !important; }
    [data-theme="light"] .border-emerald-500\/20 { border-color: rgba(16,185,129,0.22) !important; }

    @media (prefers-reduced-motion: reduce) {
        .retro-page { transition: none !important; }
        .retro-card { transition: none !important; }
    }
</style>

<div id="retro-page" class="retro-page pt-6 px-8 pb-12 text-white min-h-screen">
    <div class="max-w-[1700px] mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <div>
                <nav class="label-cyber mb-1">Protocol // <span class="text-blue-400">Integrated Retrospective</span></nav>
                <h1 class="text-3xl heading-cyber uppercase">Mission Review — <span class="text-blue-500">{{ $board->name }}</span></h1>
            </div>
            <div class="flex items-center gap-3">
                @if(auth()->check() && $board->canEdit(auth()->user()))
                <button id="open-data-inject" class="bg-white text-black p-3 rounded-2xl hover:bg-indigo-500 hover:text-white transition-all shadow-xl shadow-indigo-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M12 4v16m8-8H4"/></svg>
                </button>
                @endif
                <a href="{{ route('dashboard.planning.show', $board) }}" class="flex items-center bg-blue-600 hover:bg-white hover:text-black px-5 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 mr-2"></i> Open Board
                </a>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6 items-stretch">
            <div class="col-span-12">
                <div class="glass-panel rounded-3xl p-6 h-full">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="label-cyber flex items-center gap-2 text-emerald-400"><i data-lucide="activity" class="w-4 h-4"></i> Live Feedback Stream</h3>
                        <span id="feedbackCount" class="bg-emerald-500/10 text-emerald-400 px-3 py-1 rounded-lg text-xs font-black border border-emerald-500/20">00 Items</span>
                    </div>
                    <div id="feedbackList" class="grid grid-cols-1 md:grid-cols-2 gap-3 custom-scroll overflow-y-auto max-h-[300px] pr-2"></div>
                </div>
            </div>
        </div>

        <div class="glass-panel rounded-3xl p-6">
            <h3 class="label-cyber mb-6 flex items-center gap-2 text-purple-400"><i data-lucide="move-3d" class="w-4 h-4"></i> Categorization Matrix</h3>
            
            <div class="grid grid-cols-1 lg:col-span-4 lg:grid-cols-4 gap-4 mb-8">
                @foreach(['do_first' => 'Do First', 'schedule' => 'Schedule', 'delegate' => 'Delegate', 'later' => 'Later'] as $key => $label)
                <div class="glass-panel rounded-2xl p-4 quadrant quad-{{ $key }}" id="quad-{{ $key }}" data-quadrant="{{ $key }}">
                    <div class="flex items-center justify-between mb-4 border-b border-white/5 pb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-white/50">{{ $label }}</span>
                        <span class="quad-count text-[9px] font-bold text-white/20">0</span>
                    </div>
                    <div class="quad-list custom-scroll min-h-[160px] space-y-2"></div>
                </div>
                @endforeach
            </div>

            <div class="mt-4 pt-6 border-t border-white/5 flex flex-col xl:flex-row gap-8 items-center bg-white/[0.02] p-6 rounded-2xl">
                
                <div class="relative flex-shrink-0 flex items-center justify-center">
                    <svg class="w-32 h-32">
                        <circle class="text-white/5" stroke-width="8" stroke="currentColor" fill="transparent" r="50" cx="64" cy="64"/>
                        <circle id="progressCircle" class="text-blue-500 progress-ring__circle" stroke-width="8" stroke-dasharray="314.159" stroke-dashoffset="314.159" stroke-linecap="round" stroke="currentColor" fill="transparent" r="50" cx="64" cy="64"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span id="diagPercent" class="text-2xl font-black italic">0%</span>
                        <span class="text-[8px] font-bold opacity-40 uppercase">Ready</span>
                    </div>
                </div>

                <div class="flex-grow grid grid-cols-2 md:grid-cols-4 gap-6 w-full">
                    <div class="space-y-1">
                        <div class="label-cyber opacity-30">Total Input</div>
                        <div id="statTotal" class="text-2xl font-black italic">0</div>
                    </div>
                    <div class="space-y-1 border-l border-white/5 pl-6">
                        <div class="label-cyber text-emerald-400">In Stream</div>
                        <div id="statStream" class="text-2xl font-black italic">0</div>
                    </div>
                    <div class="space-y-1 border-l border-white/5 pl-6">
                        <div class="label-cyber text-blue-400">Categorized</div>
                        <div id="statCategorized" class="text-2xl font-black italic text-blue-400">0</div>
                    </div>
                    <div class="space-y-1 border-l border-white/5 pl-6">
                        <div class="label-cyber text-purple-400">Health Index</div>
                        <div id="statHealth" class="text-2xl font-black italic">100%</div>
                    </div>
                </div>

                <div class="flex-shrink-0">
                    <button class="px-8 py-4 bg-blue-600 hover:bg-white hover:text-black rounded-2xl font-black text-[11px] uppercase tracking-widest transition-all">
                        Finalize Retrospective
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

@php
    $tasksPayload = $board->tasks->map(fn($t) => ['id'=>$t->id,'title'=>$t->title,'points'=>$t->points,'quadrant'=>$t->quadrant])->values();
@endphp

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    lucide.createIcons();
    const CAN_EDIT = window.CAN_EDIT === true || window.CAN_EDIT === 'true';
    const boardId = {{ $board->id }};
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let tasks = @json($tasksPayload);

    function updateDiagram(){
        const total = tasks.length;
        const categorized = tasks.filter(t => t.quadrant).length;
        const percent = total > 0 ? Math.round((categorized / total) * 100) : 0;
        
        // Update SVG Circle
        const circle = document.getElementById('progressCircle');
        const radius = circle.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;
        const offset = circumference - (percent / 100 * circumference);
        circle.style.strokeDashoffset = offset;

        // Update Text
        document.getElementById('diagPercent').textContent = percent + '%';
        document.getElementById('statTotal').textContent = total;
        document.getElementById('statStream').textContent = total - categorized;
        document.getElementById('statCategorized').textContent = categorized;
        
        // Calculate health (ratio of Do First / Schedule vs Delegate / Later)
        const priorityCount = tasks.filter(t => t.quadrant === 'do_first' || t.quadrant === 'schedule').length;
        const health = total > 0 ? Math.round((priorityCount / total) * 100) : 100;
        document.getElementById('statHealth').textContent = health + '%';
    }

    function renderFeedback(){
        const list = document.getElementById('feedbackList');
        list.innerHTML = '';
        const feedback = tasks.filter(t => !t.quadrant);
        document.getElementById('feedbackCount').textContent = `${String(feedback.length).padStart(2, '0')} Items`;
        
        feedback.forEach(t=>{
            const el = document.createElement('div');
            el.className = 'retro-card p-3 rounded-2xl flex items-center justify-between cursor-grab active:cursor-grabbing';
            el.dataset.id = t.id;
            el.innerHTML = `
                <div class="min-w-0 pr-3"><div class="text-[10px] font-black uppercase italic text-white/90 truncate">${t.title}</div></div>
                <div class="text-sm font-black italic text-blue-400">${t.points||0}</div>
            `;
            list.appendChild(el);
        });
    }

    function renderQuadrants(){
        ['do_first','schedule','delegate','later'].forEach(q => {
            const container = document.querySelector(`#quad-${q} .quad-list`);
            const qTasks = tasks.filter(t=> t.quadrant===q);
            container.innerHTML = '';
            document.querySelector(`#quad-${q} .quad-count`).textContent = qTasks.length;

            qTasks.forEach(t=>{
                const node = document.createElement('div');
                node.className = 'retro-card p-2 rounded-lg text-[9px] font-black uppercase truncate text-white/70';
                node.dataset.id = t.id;
                node.textContent = t.title;
                container.appendChild(node);
            });
        });
    }

    function renderAll(){ renderFeedback(); renderQuadrants(); updateDiagram(); }

    const sortConfig = { group: 'retro', animation: 200 };
    Sortable.create(document.getElementById('feedbackList'), { ...sortConfig });

    ['do_first','schedule','delegate','later'].forEach(q => {
        Sortable.create(document.querySelector(`#quad-${q} .quad-list`), {
            ...sortConfig,
            onAdd: async function(evt){
                const id = evt.item.dataset.id;
                tasks = tasks.map(t=> String(t.id)===String(id) ? {...t, quadrant: q} : t);
                renderAll();
                await fetch(`/dashboard/planning/${boardId}/tasks/${id}`,{ method:'PATCH', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token}, body: JSON.stringify({ quadrant: q }) });
            }
        });
    });

    const createFeedbackForm = document.getElementById('createFeedback');
    if(createFeedbackForm){
        createFeedbackForm.addEventListener('submit', async function(e){
            e.preventDefault();
            if(!CAN_EDIT) return alert('Insufficient permissions');
            const fd = new FormData(this);
            const res = await fetch(`/dashboard/planning/${boardId}/tasks`,{ method:'POST', headers: {'X-CSRF-TOKEN':token,'Accept':'application/json'}, body: new URLSearchParams(fd) });
            if(res.ok){ const data = await res.json(); tasks.unshift(data.task ?? data); this.reset(); renderAll(); }
        });
    }

    renderAll();

    // Data Inject modal
    const diModal = document.getElementById('data-inject-modal');
    const openDataBtn = document.getElementById('open-data-inject');
    const closeDataBtn = document.getElementById('close-data-inject');
    if(openDataBtn && diModal && CAN_EDIT) openDataBtn.onclick = () => diModal.classList.remove('hidden');
    if(closeDataBtn && diModal) closeDataBtn.onclick = () => diModal.classList.add('hidden');
    if(diModal) diModal.addEventListener('click', e => { if (e.target === diModal) diModal.classList.add('hidden'); });

    requestAnimationFrame(() => {
        const page = document.getElementById('retro-page');
        if (page) page.classList.add('is-ready');
    });
});
</script>
@endsection

@push('modals')
<div id="data-inject-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4" style="backdrop-filter:blur(12px);background:rgba(0,0,0,0.65);">
    <div id="data-inject-panel" class="relative w-full max-w-md rounded-[2rem] p-8 border border-white/10 shadow-2xl" style="background:rgba(10,11,40,0.92);backdrop-filter:blur(20px);">
        <div class="flex items-center justify-between mb-6">
            <h3 id="di-heading" style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:0.25em;color:rgba(96,165,250,1);" class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3"/></svg>
                Data Injection
            </h3>
            <button id="close-data-inject" style="padding:0.5rem;border-radius:0.75rem;color:rgba(255,255,255,0.2);background:transparent;border:none;cursor:pointer;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="createFeedback" class="space-y-4">
            <div class="space-y-1">
                <label style="font-size:9px;font-weight:700;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-left:0.5rem;display:block;">Headline</label>
                <input name="title" placeholder="ENTER EVENT..." required class="w-full px-4 py-3 rounded-xl input-cyber text-sm font-black uppercase italic" />
            </div>
            <div class="space-y-1">
                <label style="font-size:9px;font-weight:700;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-left:0.5rem;display:block;">Description</label>
                <textarea name="description" rows="2" placeholder="Context details..." class="w-full px-4 py-3 rounded-xl input-cyber text-sm"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label style="font-size:9px;font-weight:700;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-left:0.5rem;display:block;">Initial Pts</label>
                    <input name="points" type="number" value="0" class="w-full px-4 py-3 rounded-xl input-cyber font-black text-center" />
                </div>
                <div class="space-y-1">
                    <label style="font-size:9px;font-weight:700;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-left:0.5rem;display:block;">Priority</label>
                    <select name="priority" class="w-full px-4 py-3 rounded-xl input-cyber font-black text-[10px] uppercase">
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            <button class="w-full py-4 bg-white text-black hover:bg-blue-600 hover:text-white rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all mt-2" type="submit">Deploy Feedback</button>
        </form>
    </div>
</div>

<style>
[data-theme="light"] #data-inject-modal { background: rgba(0,0,0,0.35) !important; }
[data-theme="light"] #data-inject-panel {
    background: #ffffff !important;
    border-color: rgba(31,41,55,0.12) !important;
    box-shadow: 0 20px 60px -20px rgba(31,41,55,0.25) !important;
}
[data-theme="light"] #di-heading { color: #4F46E5 !important; }
[data-theme="light"] #data-inject-panel .input-cyber {
    background: #F1F5F9 !important;
    border-color: rgba(31,41,55,0.14) !important;
    color: #1F2937 !important;
}
[data-theme="light"] #data-inject-panel .input-cyber::placeholder { color: rgba(31,41,55,0.35) !important; }
[data-theme="light"] #data-inject-panel label { color: rgba(31,41,55,0.48) !important; }
[data-theme="light"] #close-data-inject { color: rgba(31,41,55,0.40) !important; }
[data-theme="light"] #close-data-inject:hover { color: #1F2937 !important; background: rgba(31,41,55,0.07) !important; }
[data-theme="light"] #open-data-inject { background: #ffffff !important; color: #1F2937 !important; box-shadow: 0 4px 14px rgba(31,41,55,0.12) !important; }
[data-theme="light"] #open-data-inject:hover { background: #6366f1 !important; color: #fff !important; }
</style>
@endpush