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
    
    .input-cyber { 
        background: rgba(0, 0, 0, 0.3); 
        border: 1px solid rgba(255, 255, 255, 0.1); 
        color: #fff; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .input-cyber:focus { 
        border-color: #3b82f6; 
        outline: none; 
        background: rgba(0, 0, 0, 0.5);
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.1);
    }

    .retro-card { 
        background: rgba(255, 255, 255, 0.03); 
        border: 1px solid rgba(255, 255, 255, 0.06); 
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        animation: slideIn 0.4s ease-out backwards;
    }
    .retro-card:hover { 
        background: rgba(255, 255, 255, 0.08); 
        transform: translateX(4px); 
        border-color: rgba(59, 130, 246, 0.4); 
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .btn-cyber {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        font-weight: 900;
        letter-spacing: 0.1em;
    }
    .btn-cyber:active { transform: scale(0.95); }

    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.3); border-radius: 10px; }

    .chart-container { position: relative; height: 250px; width: 100%; }

    /* ─── PAGE LOAD ─── */
    .release-page {
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 220ms ease, transform 220ms ease;
    }
    .release-page.is-ready { opacity: 1; transform: translateY(0); }

    /* ─── LIGHT MODE ─── */
    [data-theme="light"] .release-page {
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
        border-color: rgba(59,130,246,0.35);
    }
    /* header bar */
    [data-theme="light"] .bg-white\/\[0\.02\] {
        background: #ffffff !important;
        border-color: var(--lm-border) !important;
    }
    /* health panel gradient */
    [data-theme="light"] .from-blue-600\/5.to-purple-600\/5 {
        background: var(--lm-surface) !important;
    }
    [data-theme="light"] .bg-white\/\[0\.03\] {
        background: var(--lm-surface) !important;
    }
    /* progress bars track */
    [data-theme="light"] .bg-white\/5 {
        background: rgba(31,41,55,0.08) !important;
    }
    /* borders */
    [data-theme="light"] .border-white\/5 {
        border-color: var(--lm-border) !important;
    }
    /* text utilities */
    [data-theme="light"] .text-white { color: var(--lm-text) !important; }
    [data-theme="light"] .text-white\/90 { color: rgba(31,41,55,0.90) !important; }
    [data-theme="light"] .text-white\/40 { color: rgba(31,41,55,0.50) !important; }
    [data-theme="light"] .text-white\/30 { color: rgba(31,41,55,0.40) !important; }
    [data-theme="light"] .text-white\/20 { color: rgba(31,41,55,0.28) !important; }
    /* back button */
    [data-theme="light"] a.btn-cyber:hover {
        background: var(--lm-text) !important;
        color: #fff !important;
    }
    /* scrollbar */
    [data-theme="light"] .custom-scroll::-webkit-scrollbar-thumb { background: rgba(31,41,55,0.18); }
    /* card action icon buttons */
    [data-theme="light"] [data-action="edit"]:hover { background: rgba(59,130,246,0.10) !important; }
    [data-theme="light"] [data-action="delete"]:hover { background: rgba(239,68,68,0.10) !important; }

    @media (prefers-reduced-motion: reduce) {
        .release-page { transition: none !important; }
        .retro-card { animation: none !important; transition: none !important; }
    }
</style>

<div id="release-page" class="release-page pt-6 px-8 pb-12 text-white min-h-screen">
    <div class="max-w-[1700px] mx-auto space-y-6">
        
        <div class="flex items-center justify-between bg-white/[0.02] p-6 rounded-3xl border border-white/5">
            <div class="flex items-center gap-6">
                <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-[0_0_20px_rgba(59,130,246,0.4)]">
                    <i data-lucide="rocket" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <nav class="label-cyber mb-1">Release // <span class="text-blue-400">Feature Blueprint</span></nav>
                    <h1 class="text-3xl heading-cyber uppercase tracking-tight">Release Plan — <span class="text-blue-500">{{ $board->name }}</span></h1>
                </div>
            </div>
            <a href="{{ route('dashboard.planning.show', $board) }}" class="flex items-center gap-2 px-6 py-3 glass-panel rounded-2xl btn-cyber text-[10px] hover:bg-white hover:text-black">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Board
            </a>
        </div>

        <div class="grid grid-cols-12 gap-6 items-stretch">
            <div class="col-span-12 lg:col-span-4">
                <div class="glass-panel rounded-3xl p-6 border-t-4 border-blue-500 h-full flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="label-cyber text-blue-400 flex items-center gap-2"><i data-lucide="package" class="w-4 h-4"></i> 1. Superset Planning</h3>
                        @if(auth()->check() && $board->canEdit(auth()->user()))
                        <button id="open-superset-modal" class="bg-white text-black p-2.5 rounded-xl hover:bg-blue-500 hover:text-white transition-all shadow-lg shadow-blue-500/10 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M12 4v16m8-8H4"/></svg>
                        </button>
                        @endif
                    </div>

                    <div class="flex-grow space-y-2 custom-scroll overflow-y-auto max-h-[500px] pr-1">
                        <div class="label-cyber text-white/20 mb-3 ml-1">Active Supersets</div>
                        <div id="list-superset" class="space-y-2"></div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="glass-panel rounded-3xl p-6 border-t-4 border-emerald-500 h-full flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="label-cyber text-emerald-400 flex items-center gap-2"><i data-lucide="layers" class="w-4 h-4"></i> 2. Epic Breakdown</h3>
                        @if(auth()->check() && $board->canEdit(auth()->user()))
                        <button id="open-epic-modal" class="bg-white text-black p-2.5 rounded-xl hover:bg-emerald-500 hover:text-white transition-all shadow-lg shadow-emerald-500/10 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M12 4v16m8-8H4"/></svg>
                        </button>
                        @endif
                    </div>

                    <div class="flex-grow space-y-2 custom-scroll overflow-y-auto max-h-[500px] pr-1">
                        <div class="label-cyber text-white/20 mb-3 ml-1">Current Stories</div>
                        <div id="list-epic" class="space-y-2"></div>

                        <div class="label-cyber text-white/20 mt-4 mb-3 ml-1">Requirements</div>
                        <div id="list-requirement" class="space-y-2"></div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="glass-panel rounded-3xl p-6 border-t-4 border-purple-500 h-full flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="label-cyber text-purple-400 flex items-center gap-2"><i data-lucide="calendar" class="w-4 h-4"></i> 3. Deployment</h3>
                        @if(auth()->check() && $board->canEdit(auth()->user()))
                        <button id="open-schedule-modal" class="bg-white text-black p-2.5 rounded-xl hover:bg-purple-500 hover:text-white transition-all shadow-lg shadow-purple-500/10 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M12 4v16m8-8H4"/></svg>
                        </button>
                        @endif
                    </div>

                    <div class="flex-grow space-y-2 custom-scroll overflow-y-auto max-h-[500px] pr-1">
                        <div class="label-cyber text-white/20 mb-3 ml-1">Deadlines</div>
                        <div id="list-schedule" class="space-y-2"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-8">
                <div class="glass-panel rounded-3xl p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="label-cyber text-blue-400 mb-1">Architecture Analytics</h3>
                            <div class="text-[10px] text-white/40 font-black italic">Composition of the current release roadmap</div>
                        </div>
                        <div class="flex gap-4">
                            <div class="text-center">
                                <div class="text-lg font-black italic" id="totalItems">0</div>
                                <div class="text-[8px] opacity-30 uppercase font-black">Total Nodes</div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="releaseTimeline"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="glass-panel rounded-3xl p-8 h-full bg-gradient-to-br from-blue-600/5 to-purple-600/5">
                    <div class="flex items-center gap-2 mb-6">
                        <h3 class="label-cyber text-white/40">System Health</h3>
                        <button id="open-tip-modal" class="flex items-center justify-center w-4 h-4 rounded-full border border-blue-500/40 text-blue-400 hover:bg-blue-500/20 transition-all" style="background:transparent;cursor:pointer;flex-shrink:0;">
                            <i data-lucide="info" class="w-2.5 h-2.5"></i>
                        </button>
                    </div>
                    <div class="space-y-6">
                        @foreach(['Supersets' => 'bg-blue-500', 'Epics' => 'bg-emerald-500', 'Requirements' => 'bg-yellow-500', 'Milestones' => 'bg-purple-500'] as $label => $color)
                        <div class="space-y-2">
                            <div class="flex justify-between text-[10px] font-black uppercase italic">
                                <span>{{ $label }}</span>
                                <span id="percent-{{ strtolower($label) }}">0%</span>
                            </div>
                            <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                <div id="bar-{{ strtolower($label) }}" class="{{ $color }} h-full transition-all duration-1000 shadow-[0_0_10px_rgba(255,255,255,0.1)]" style="width: 0%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $releasesPayload = $releases->map(fn($r) => [
        'id' => $r->id, 'title' => $r->title, 'description' => $r->description,
        'type' => $r->type, 'version' => $r->version, 'target_date' => $r->target_date, 'position' => $r->position,
    ])->values();

    $requirementsPayload = ($requirements ?? collect())->map(fn($q) => [
        'id' => $q->id, 'title' => $q->title, 'description' => $q->description,
        'type' => 'requirement', 'priority' => $q->priority, 'estimate' => $q->estimate ?? null,
        'parent_id' => $q->parent_id, 'position' => $q->position, 'acceptance_criteria' => $q->acceptance_criteria ?? null,
    ])->values();

    $itemsPayload = $releasesPayload->concat($requirementsPayload)->values();
@endphp

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    lucide.createIcons();
    const CAN_EDIT = window.CAN_EDIT === true || window.CAN_EDIT === 'true';
    const boardId = {{ $board->id }};
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let releases = @json($itemsPayload);
    let boardTasks = @json($board->tasks->map(fn($t)=>['id'=>$t->id,'title'=>$t->title])->values());

    function renderLists(){
        ['superset', 'epic', 'requirement', 'schedule'].forEach(type => {
            const container = document.getElementById(`list-${type}`);
            container.innerHTML = '';
            
            releases.filter(r => r.type === type).sort((a,b) => (a.position||0) - (b.position||0)).forEach((r, idx) => {
                const el = document.createElement('div');
                el.className = 'retro-card p-4 rounded-2xl flex items-center justify-between group cursor-grab active:cursor-grabbing';
                el.dataset.id = r.id;
                el.dataset.type = r.type;
                el.style.animationDelay = `${idx * 0.05}s`;
                
                el.innerHTML = `
                    <div class="flex-grow min-w-0 pr-4">
                        <div class="text-[11px] font-black uppercase italic tracking-tight text-white/90 truncate">${r.title}</div>
                        <div class="flex items-center gap-3 mt-1 opacity-40">
                            ${r.version ? `<span class="text-[9px] font-mono">${r.version}</span>` : ''}
                            ${r.priority ? `<span class="text-[9px] font-mono">${r.priority.toUpperCase()}</span>` : ''}
                            ${r.estimate ? `<span class="text-[9px] font-mono">${r.estimate}pt</span>` : ''}
                            ${r.target_date ? `<span class="text-[9px] flex items-center gap-1"><i data-lucide="clock" class="w-2.5 h-2.5"></i> ${r.target_date}</span>` : ''}
                        </div>
                    </div>
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                        <button data-action="edit" class="p-2 hover:bg-blue-500/20 text-blue-400 rounded-lg transition-colors"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i></button>
                        ${r.type === 'requirement' ? `<button data-action="attach-task" class="p-2 hover:bg-indigo-500/20 text-indigo-300 rounded-lg"><i data-lucide="link" class="w-3.5 h-3.5"></i></button>` : ''}
                        ${r.type === 'requirement' ? `<button data-action="accept" class="p-2 hover:bg-emerald-500/20 text-emerald-300 rounded-lg"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i></button>` : ''}
                        <button data-action="delete" class="p-2 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                    </div>
                `;
                container.appendChild(el);
            });
        });
        attachListButtons();
        updateAnalytics();
        lucide.createIcons();
    }

    function updateAnalytics(){
        const counts = { superset:0, epic:0, requirement:0, schedule:0 };
        releases.forEach(r => counts[r.type]++);
        const total = releases.length || 1;

        document.getElementById('totalItems').textContent = releases.length;
        
        // Update bars
        const labelMap = { superset: 'supersets', epic: 'epics', requirement: 'requirements', schedule: 'milestones' };
        Object.keys(counts).forEach(k => {
            const p = Math.round((counts[k] / total) * 100);
            const label = labelMap[k] || (k + 's');
            if(document.getElementById(`percent-${label}`)) {
                document.getElementById(`percent-${label}`).textContent = p + '%';
                document.getElementById(`bar-${label}`).style.width = p + '%';
            }
        });

        // Chart Update
        const data = [counts.superset, counts.epic, counts.requirement, counts.schedule];
        if(!window.distChart){
            const ctx = document.getElementById('releaseTimeline').getContext('2d');
            window.distChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['SUPERSETS', 'EPICS', 'REQUIREMENTS', 'MILESTONES'],
                    datasets: [{
                        data: data,
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#a855f7'],
                        borderRadius: 12,
                        barThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { display: false, grid: { display: false } },
                        x: { ticks: { color: 'rgba(255,255,255,0.4)', font: { family: 'Inter', weight: '900', size: 9 } }, grid: { display: false } }
                    }
                }
            });
        } else {
            window.distChart.data.datasets[0].data = data;
            window.distChart.update();
        }
    }

    function attachListButtons(){
        if(!CAN_EDIT) return; // viewers shouldn't have interactive controls
        document.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.onclick = async function(){
                const el = this.closest('[data-id]');
                const id = el.dataset.id;
                const type = el.dataset.type;
                if(!confirm('TERMINATE NODE?')) return;
                const url = type === 'requirement' ? `/dashboard/planning/${boardId}/requirements/items/${id}` : `/dashboard/planning/${boardId}/release/items/${id}`;
                await fetch(url,{ method:'DELETE', headers:{'X-CSRF-TOKEN':token} });
                releases = releases.filter(r => String(r.id) !== String(id));
                renderLists();
            };
        });
        document.querySelectorAll('[data-action="edit"]').forEach(btn => {
            btn.onclick = function(){
                const el = this.closest('[data-id]');
                const id = el.dataset.id;
                const type = el.dataset.type;
                const item = releases.find(r => String(r.id) === String(id));
                const newTitle = prompt('EDIT NODE TITLE', item.title);
                if(!newTitle) return;
                const url = type === 'requirement' ? `/dashboard/planning/${boardId}/requirements/items/${id}` : `/dashboard/planning/${boardId}/release/items/${id}`;
                fetch(url,{ 
                    method:'PATCH', 
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token}, 
                    body: JSON.stringify({ title: newTitle })
                }).then(r => r.json()).then(j => { 
                    const updated = type === 'requirement' ? j.requirement : j.release;
                    Object.assign(item, updated); renderLists(); 
                });
            };
        });

        // attach task to requirement
        document.querySelectorAll('[data-action="attach-task"]').forEach(btn => {
            btn.onclick = async function(){
                const el = this.closest('[data-id]');
                const id = el.dataset.id;
                const item = releases.find(r => String(r.id) === String(id));
                // simple prompt pick: list available tasks with ids
                const list = boardTasks.map(t => `${t.id}: ${t.title}`).join('\n');
                const taskId = prompt('Attach task to requirement (enter task id)\n\n' + list);
                if(!taskId) return;
                const res = await fetch(`/dashboard/planning/${boardId}/requirements/items/${id}/tasks`,{ 
                    method:'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token}, body: JSON.stringify({ task_id: taskId })
                });
                if(res.ok){ const j = await res.json(); Object.assign(item, { tasks: j.requirement.tasks }); renderLists(); }
            };
        });

        // add acceptance criteria
        document.querySelectorAll('[data-action="accept"]').forEach(btn => {
            btn.onclick = function(){
                const el = this.closest('[data-id]');
                const id = el.dataset.id;
                const item = releases.find(r => String(r.id) === String(id));
                const ac = prompt('Enter acceptance criteria', item.acceptance_criteria || '');
                if(ac === null) return;
                fetch(`/dashboard/planning/${boardId}/requirements/items/${id}`,{ 
                    method:'PATCH', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token}, body: JSON.stringify({ acceptance_criteria: ac })
                }).then(r => r.json()).then(j => { Object.assign(item, j.requirement); renderLists(); });
            };
        });
    }

    // Sortable (guarded)
    ['superset','epic','requirement','schedule'].forEach(type => {
        const el = document.getElementById(`list-${type}`);
        if(!el) return;
        Sortable.create(el,{
            group: 'release', animation: 250, ghostClass: 'opacity-10',
            onAdd: async function(evt){
                const id = evt.item.dataset.id;
                const moveUrl = type === 'requirement' ? `/dashboard/planning/${boardId}/requirements/items/${id}/move` : `/dashboard/planning/${boardId}/release/items/${id}/move`;
                await fetch(moveUrl,{ 
                    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token}, 
                    body: JSON.stringify({ type, position: evt.newIndex }) 
                });
                releases = releases.map(r => String(r.id) === String(id) ? Object.assign({}, r, { type, position: evt.newIndex }) : r);
                renderLists();
            },
            onUpdate: function(evt){
                const items = Array.from(evt.to.children).map((c,i) => c.dataset.id);
                items.forEach((id,i) => { const it = releases.find(r => String(r.id) === String(id)); if(it) it.position = i; });
                updateAnalytics();
            }
        });
    });

    // Form Handlers (guarded)
    ['createRelease', 'createEpic', 'createRequirement', 'createSchedule'].forEach(id => {
        const form = document.getElementById(id);
        if(!form) return;
        form.addEventListener('submit', async function(e){
            e.preventDefault();
            const fd = new FormData(this);
            // route by form id (requirements use separate controller)
            let url = `/dashboard/planning/${boardId}/release/items`;
            if(this.id === 'createRequirement') url = `/dashboard/planning/${boardId}/requirements/items`;
            const res = await fetch(url,{ 
                method:'POST', headers: {'X-CSRF-TOKEN':token,'Accept':'application/json'}, body: new URLSearchParams(fd) 
            });
            if(res.ok){ const j = await res.json(); if(this.id === 'createRequirement'){ releases.push(j.requirement); } else { releases.push(j.release); } renderLists(); this.reset(); }
        });
    });

    renderLists();

    // Deployment Tip modal (guarded)
    const tipModal = document.getElementById('tip-modal');
    const openTipBtn = document.getElementById('open-tip-modal');
    const closeTipBtn = document.getElementById('close-tip-modal');
    if(openTipBtn && tipModal) openTipBtn.addEventListener('click', () => tipModal.classList.remove('hidden'));
    if(closeTipBtn && tipModal) closeTipBtn.addEventListener('click', () => tipModal.classList.add('hidden'));
    if(tipModal) tipModal.addEventListener('click', e => { if (e.target === tipModal) tipModal.classList.add('hidden'); });

    // Release modals wiring
    [
        ['open-superset-modal', 'close-superset-modal', 'superset-modal'],
        ['open-epic-modal',     'close-epic-modal',     'epic-modal'],
        ['open-schedule-modal', 'close-schedule-modal', 'schedule-modal'],
    ].forEach(([openId, closeId, modalId]) => {
        const m = document.getElementById(modalId);
        const openEl = document.getElementById(openId);
        const closeEl = document.getElementById(closeId);
        if(openEl && m) openEl.addEventListener('click', () => m.classList.remove('hidden'));
        if(closeEl && m) closeEl.addEventListener('click', () => m.classList.add('hidden'));
        if(m) m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); });
    });

    requestAnimationFrame(() => {
        const page = document.getElementById('release-page');
        if (page) page.classList.add('is-ready');
    });
});
</script>
@endsection

@push('modals')
{{-- Modal: Deployment Tip --}}
<div id="tip-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4" style="backdrop-filter:blur(12px);background:rgba(0,0,0,0.65);">
    <div id="tip-panel" class="relative w-full max-w-sm rounded-[2rem] p-8 border border-blue-500/20 shadow-2xl" style="background:rgba(10,11,40,0.92);backdrop-filter:blur(20px);">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3 text-blue-400">
                <div class="w-8 h-8 rounded-xl bg-blue-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:0.2em;">Deployment Tip</span>
            </div>
            <button id="close-tip-modal" class="release-close-btn" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p style="font-size:12px;color:rgba(255,255,255,0.55);line-height:1.75;font-style:italic;">Drag nodes between columns to reclassify them instantly. Changes are synchronized across your team's mission control.</p>
    </div>
</div>

{{-- Modal 1: Superset Planning --}}
<div id="superset-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4" style="backdrop-filter:blur(12px);background:rgba(0,0,0,0.65);">
    <div id="superset-panel" class="relative w-full max-w-md rounded-[2rem] p-8 border border-white/10 shadow-2xl" style="background:rgba(10,11,40,0.92);backdrop-filter:blur(20px);">
        <div class="flex items-center justify-between mb-6">
            <h3 class="label-cyber text-blue-400 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/></svg>
                Superset Planning
            </h3>
            <button id="close-superset-modal" class="release-close-btn" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="createRelease" class="space-y-3">
            <div class="relative">
                <input name="title" placeholder="RELEASE TITLE (e.g. v2.0)" required class="w-full pl-4 pr-4 py-3.5 rounded-2xl input-cyber text-xs font-black uppercase italic" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input name="version" placeholder="SEMVER" class="w-full px-4 py-3 rounded-xl input-cyber text-[10px] font-mono" />
                <input type="date" name="target_date" class="w-full px-4 py-3 rounded-xl input-cyber text-[10px]" />
            </div>
            <textarea name="description" rows="2" placeholder="Objectives & high-level goals..." class="w-full px-4 py-3 rounded-xl input-cyber text-[10px]"></textarea>
            <input type="hidden" name="type" value="superset" />
            <button class="w-full py-3.5 bg-blue-600 text-white rounded-2xl btn-cyber text-[10px] hover:bg-white hover:text-black transition-all">Inject Superset</button>
        </form>
    </div>
</div>

{{-- Modal 2: Epic Breakdown --}}
<div id="epic-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4" style="backdrop-filter:blur(12px);background:rgba(0,0,0,0.65);">
    <div id="epic-panel" class="relative w-full max-w-md rounded-[2rem] p-8 border border-white/10 shadow-2xl" style="background:rgba(10,11,40,0.92);backdrop-filter:blur(20px);">
        <div class="flex items-center justify-between mb-6">
            <h3 class="label-cyber text-emerald-400 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                Epic Breakdown
            </h3>
            <button id="close-epic-modal" class="release-close-btn" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="createEpic" class="space-y-3 mb-6">
            <input name="title" placeholder="EPIC OR STORY TITLE" required class="w-full px-4 py-3.5 rounded-2xl input-cyber text-xs font-black uppercase italic" />
            <select name="parent_version" class="w-full px-4 py-3.5 rounded-2xl input-cyber text-[10px] appearance-none uppercase font-black">
                <option value="">Link to Parent Release</option>
                @foreach($releases->where('type', 'superset') as $r)
                    <option value="{{ $r->version }}">{{ $r->title }} ({{ $r->version }})</option>
                @endforeach
            </select>
            <input type="hidden" name="type" value="epic" />
            <button class="w-full py-3.5 bg-emerald-600 text-white rounded-2xl btn-cyber text-[10px] hover:bg-white hover:text-black transition-all">Add Story Arc</button>
        </form>

        <div class="border-t border-white/10 pt-6">
            <div class="label-cyber text-yellow-400 mb-4">Add Requirement</div>
            <form id="createRequirement" class="space-y-3">
                <input name="title" placeholder="REQUIREMENT TITLE" required class="w-full px-4 py-3.5 rounded-2xl input-cyber text-xs font-black uppercase italic" />
                <textarea name="description" rows="2" placeholder="Short description or rationale..." class="w-full px-4 py-3 rounded-xl input-cyber text-[10px]"></textarea>
                <div class="grid grid-cols-2 gap-3">
                    <select name="priority" class="w-full px-4 py-3 rounded-xl input-cyber text-[10px] appearance-none uppercase font-black">
                        <option value="">Priority</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                    <input name="estimate" placeholder="Estimate (pts)" class="w-full px-4 py-3 rounded-xl input-cyber text-[10px]" />
                </div>
                <select name="parent_epic" class="w-full px-4 py-3.5 rounded-2xl input-cyber text-[10px] appearance-none uppercase font-black">
                    <option value="">Link to Parent Story</option>
                    @foreach($releases->where('type', 'epic') as $e)
                        <option value="{{ $e->id }}">{{ $e->title }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="type" value="requirement" />
                <button class="w-full py-3.5 bg-yellow-600 text-white rounded-2xl btn-cyber text-[10px] hover:bg-white hover:text-black transition-all">Add Requirement</button>
            </form>
        </div>
    </div>
</div>

{{-- Modal 3: Deployment --}}
<div id="schedule-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4" style="backdrop-filter:blur(12px);background:rgba(0,0,0,0.65);">
    <div id="schedule-panel" class="relative w-full max-w-md rounded-[2rem] p-8 border border-white/10 shadow-2xl" style="background:rgba(10,11,40,0.92);backdrop-filter:blur(20px);">
        <div class="flex items-center justify-between mb-6">
            <h3 class="label-cyber text-purple-400 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Deployment Milestone
            </h3>
            <button id="close-schedule-modal" class="release-close-btn" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="createSchedule" class="space-y-3">
            <input name="title" placeholder="MILESTONE / DEPLOY EVENT" required class="w-full px-4 py-3.5 rounded-2xl input-cyber text-xs font-black uppercase italic" />
            <input type="date" name="target_date" class="w-full px-4 py-3.5 rounded-2xl input-cyber text-[10px]" />
            <input type="hidden" name="type" value="schedule" />
            <button class="w-full py-3.5 bg-purple-600 text-white rounded-2xl btn-cyber text-[10px] hover:bg-white hover:text-black transition-all">Mark Milestone</button>
        </form>
    </div>
</div>

<style>
.release-close-btn {
    padding: 0.5rem; border-radius: 0.75rem; color: rgba(255,255,255,0.2);
    background: transparent; border: none; cursor: pointer;
}

/* Light mode — all 3 modals */
[data-theme="light"] #superset-modal,
[data-theme="light"] #epic-modal,
[data-theme="light"] #schedule-modal { background: rgba(0,0,0,0.35) !important; }

[data-theme="light"] #superset-panel,
[data-theme="light"] #epic-panel,
[data-theme="light"] #schedule-panel {
    background: #ffffff !important;
    border-color: rgba(31,41,55,0.12) !important;
    box-shadow: 0 20px 60px -20px rgba(31,41,55,0.25) !important;
}
[data-theme="light"] #superset-panel .input-cyber,
[data-theme="light"] #epic-panel .input-cyber,
[data-theme="light"] #schedule-panel .input-cyber {
    background: #F1F5F9 !important;
    border-color: rgba(31,41,55,0.14) !important;
    color: #1F2937 !important;
}
[data-theme="light"] #superset-panel .input-cyber::placeholder,
[data-theme="light"] #epic-panel .input-cyber::placeholder,
[data-theme="light"] #schedule-panel .input-cyber::placeholder { color: rgba(31,41,55,0.35) !important; }
[data-theme="light"] #epic-panel .border-white\/10 { border-color: rgba(31,41,55,0.10) !important; }
[data-theme="light"] .release-close-btn { color: rgba(31,41,55,0.40) !important; }
[data-theme="light"] .release-close-btn:hover { color: #1F2937 !important; background: rgba(31,41,55,0.07) !important; }
[data-theme="light"] #open-superset-modal { background: #ffffff !important; color: #1F2937 !important; }
[data-theme="light"] #open-superset-modal:hover { background: #3b82f6 !important; color: #fff !important; }
[data-theme="light"] #open-epic-modal { background: #ffffff !important; color: #1F2937 !important; }
[data-theme="light"] #open-epic-modal:hover { background: #10b981 !important; color: #fff !important; }
[data-theme="light"] #open-schedule-modal { background: #ffffff !important; color: #1F2937 !important; }
[data-theme="light"] #open-schedule-modal:hover { background: #a855f7 !important; color: #fff !important; }
[data-theme="light"] #open-tip-modal { border-color: rgba(99,102,241,0.3) !important; color: #6366f1 !important; }
[data-theme="light"] #tip-panel {
    background: #ffffff !important;
    border-color: rgba(99,102,241,0.20) !important;
    box-shadow: 0 20px 60px -20px rgba(31,41,55,0.20) !important;
}
[data-theme="light"] #tip-panel p { color: rgba(31,41,55,0.55) !important; }
</style>
@endpush