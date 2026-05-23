@extends('layouts.dashboard')

@section('dashboard-content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // Ensure payloads exist (defensive - may be provided earlier)
        if (!isset($tasksPayload)) {
            $tasksPayload = $board->tasks->map(function($t){
                return [
                    'id' => $t->id,
                    'title' => $t->title,
                    'points' => $t->points,
                    'priority' => $t->priority,
                    'status' => $t->status,
                    'sprint_id' => $t->sprint_id ?? null,
                    'assignee' => $t->assignee?->only(['id','name']) ?? null,
                    'created_at' => $t->created_at?->toDateTimeString(),
                    'updated_at' => $t->updated_at?->toDateTimeString(),
                ];
            })->values();
        }

        if (!isset($devsPayload)) {
            $devsPayload = (isset($developers) ? $developers : collect())->map(function($d){ return ['id'=>$d->id,'name'=>$d->name]; })->values();
        }

        if (!isset($sprintsPayload)) {
            $sprintsPayload = (isset($sprints) ? $sprints : collect())->map(function($s){ return ['id'=>$s->id,'name'=>$s->name]; })->values();
        }
    @endphp

    <style>
        /* Modern Cyber-Container Layout */
        .dashboard-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
            gap: 20px;
            padding: 24px;
            min-height: calc(100vh - 120px);
        }

        .glass-module {
            background: rgba(10, 11, 40, 0.5);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            display: flex;
            flex-direction: column;
        }

        /* Tactical Header Styling */
        .telemetry-header {
            grid-column: 1 / -1;
            padding: 20px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .heading-cyber { font-family: 'Inter', sans-serif; font-weight: 900; font-style: italic; text-transform: uppercase; letter-spacing: -0.02em; }
        .label-cyber { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.25em; color: rgba(255, 255, 255, 0.4); }

        /* Board Structure */
        .board-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            align-items: start;
        }

        .column-module {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 20px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            height: 400px;
        }

        .card-list {
            flex-grow: 1;
            overflow-y: auto;
            padding-right: 4px;
        }

        .card-list::-webkit-scrollbar { width: 4px; }
        .card-list::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.3); border-radius: 10px; }

        /* Card Design */
        .task-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: grab;
        }

        .task-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .priority-indicator { width: 4px; border-radius: 2px; height: 20px; }
        
        /* Inputs */
        .cyber-input {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            width: 100%;
            transition: border-color 0.2s;
        }
        .cyber-input:focus { border-color: #3b82f6; outline: none; }

        /* ─── PAGE LOAD SMOOTHNESS ─── */
        .sprint-board-page {
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 220ms ease, transform 220ms ease;
        }
        .sprint-board-page.is-ready { opacity: 1; transform: translateY(0); }

        /* ─── LIGHT MODE ─── */
        [data-theme="light"] .sprint-board-page {
            background: #F8FAFC;
        }

        [data-theme="light"] .glass-module {
            background: #ffffff;
            border-color: rgba(31,41,55,0.10);
            box-shadow: 0 8px 24px -12px rgba(31,41,55,0.14);
        }

        [data-theme="light"] .telemetry-header {
            border-left-color: #6366F1;
        }

        [data-theme="light"] .column-module {
            background: #F1F5F9;
            border: 1px solid rgba(31,41,55,0.08);
        }

        [data-theme="light"] .column-module.border-x {
            border-color: rgba(31,41,55,0.08) !important;
        }

        [data-theme="light"] .task-card {
            background: #ffffff;
            border-color: rgba(31,41,55,0.10);
        }
        [data-theme="light"] .task-card:hover {
            background: #F8FAFC;
            border-color: rgba(59,130,246,0.40);
            box-shadow: 0 8px 20px -10px rgba(59,130,246,0.18);
        }

        [data-theme="light"] .cyber-input {
            background: #ffffff;
            border-color: rgba(31,41,55,0.14);
            color: #1F2937;
        }
        [data-theme="light"] .cyber-input::placeholder { color: rgba(31,41,55,0.35); }
        [data-theme="light"] .cyber-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.10);
        }

        /* inline card inputs */
        [data-theme="light"] [data-field="title"] {
            color: #1F2937 !important;
        }
        [data-theme="light"] [data-field="assignee"],
        [data-theme="light"] [data-field="sprint"] {
            color: rgba(31,41,55,0.70) !important;
            background: rgba(31,41,55,0.04) !important;
            border-color: rgba(31,41,55,0.10) !important;
        }

        /* velocity bar */
        [data-theme="light"] .bg-black\/30 {
            background: rgba(31,41,55,0.05) !important;
        }
        [data-theme="light"] .border-white\/5 {
            border-color: rgba(31,41,55,0.08) !important;
        }
        [data-theme="light"] .w-px.bg-white\/10 {
            background: rgba(31,41,55,0.12) !important;
        }

        /* text utilities */
        [data-theme="light"] .text-white { color: #1F2937 !important; }
        [data-theme="light"] .text-white\/90 { color: rgba(31,41,55,0.90) !important; }
        [data-theme="light"] .text-white\/80 { color: rgba(31,41,55,0.80) !important; }
        [data-theme="light"] .text-white\/40 { color: rgba(31,41,55,0.50) !important; }
        [data-theme="light"] .text-white\/30 { color: rgba(31,41,55,0.40) !important; }
        [data-theme="light"] .label-cyber { color: rgba(31,41,55,0.46); }

        /* info protocol box */
        [data-theme="light"] .bg-white\/5 {
            background: rgba(31,41,55,0.05) !important;
        }

        /* scrollbar */
        [data-theme="light"] .card-list::-webkit-scrollbar-thumb {
            background: rgba(31,41,55,0.18);
        }

        /* create button */
        [data-theme="light"] button[type="submit"].bg-blue-600 {
            background: #6366F1;
        }
        [data-theme="light"] button[type="submit"].bg-blue-600:hover {
            background: #1F2937;
            color: #fff;
        }

        @media (prefers-reduced-motion: reduce) {
            .sprint-board-page { transition: none !important; }
            .task-card { transition: none !important; }
        }
    </style>

    <div id="sprint-board-page" class="sprint-board-page dashboard-wrapper text-white">
        
        <header class="glass-module telemetry-header border-l-4 border-l-blue-600">
            <div>
                <nav class="label-cyber mb-1">Navigation // <span class="text-blue-400">{{ $board->name }}</span></nav>
                <h1 class="text-3xl heading-cyber">Sprint Command</h1>
            </div>

            <div class="flex items-center gap-6">
                <div class="flex gap-4 bg-black/30 rounded-2xl px-6 py-2 border border-white/5">
                    <div class="text-center">
                        <span class="label-cyber text-[8px] block opacity-50">Backlog</span>
                        <span id="velTodo" class="font-black text-slate-400 italic">0</span>
                    </div>
                    <div class="w-px h-8 bg-white/10"></div>
                    <div class="text-center">
                        <span class="label-cyber text-[8px] block text-blue-400/50">Active</span>
                        <span id="velDoing" class="font-black text-blue-400 italic">0</span>
                    </div>
                    <div class="w-px h-8 bg-white/10"></div>
                    <div class="text-center">
                        <span class="label-cyber text-[8px] block text-emerald-400/50">Merged</span>
                        <span id="velDone" class="font-black text-emerald-400 italic">0</span>
                    </div>
                </div>

                <div class="flex gap-2 items-center">
                    <select id="filterSprint" class="cyber-input py-2 text-[10px] font-bold uppercase w-40">
                            <option value="all">Global Sprints</option>
                            <option value="none">No Sprint</option>
                        @foreach($sprints as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                    </select>
                    <select id="filterAssignee" class="cyber-input py-2 text-[10px] font-bold uppercase w-40">
                        <option value="all">All Agents</option>
                        @foreach($developers as $d) <option value="{{ $d->id }}">{{ $d->name }}</option> @endforeach
                    </select>
                    <button id="open-task-inject" class="bg-white text-black p-3 rounded-2xl hover:bg-indigo-500 hover:text-white transition-all shadow-xl shadow-indigo-500/10 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <a href="{{ route('dashboard.planning.show', $board) }}" class="flex items-center gap-2 px-5 py-2 glass-module rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-white hover:text-black transition-all" style="border:1px solid rgba(255,255,255,0.06);">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Board
                    </a>
                </div>
            </div>
        </header>

        <main class="board-container">
            <div class="column-module">
                <div class="flex justify-between items-center mb-4 px-2">
                    <span class="label-cyber text-slate-400">Backlog</span>
                    <span id="count-todo" class="text-[10px] font-black opacity-20 italic">00</span>
                </div>
                <div id="col-todo" class="card-list" data-status="todo"></div>
            </div>

            <div class="column-module border-x border-white/5">
                <div class="flex justify-between items-center mb-4 px-2">
                    <span class="label-cyber text-blue-400">Processing</span>
                    <span id="count-doing" class="text-[10px] font-black opacity-20 italic">00</span>
                </div>
                <div id="col-in_progress" class="card-list" data-status="in_progress"></div>
            </div>

            <div class="column-module">
                <div class="flex justify-between items-center mb-4 px-2">
                    <span class="label-cyber text-emerald-400">Resolved</span>
                    <span id="count-done" class="text-[10px] font-black opacity-20 italic">00</span>
                </div>
                <div id="col-done" class="card-list" data-status="done"></div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const boardId = {{ $board->id }};
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let tasks = @json($tasksPayload);
        const developers = @json($devsPayload);
        const sprints = @json($sprintsPayload);

        const cols = {
            todo: document.getElementById('col-todo'),
            in_progress: document.getElementById('col-in_progress'),
            done: document.getElementById('col-done')
        };

        let activeSprint = 'all';
        let activeAssignee = 'all';

        function render(){
            Object.values(cols).forEach(c=>c.innerHTML='');
            const filtered = tasks.filter(t => (
                activeSprint==='all' ||
                (activeSprint==='none' && (!t.sprint_id || String(t.sprint_id) === '')) ||
                String(t.sprint_id)===String(activeSprint)
            ) && (activeAssignee==='all' || (t.assignee && String(t.assignee.id)===String(activeAssignee))));
            
            const stats = { todo: 0, in_progress: 0, done: 0 };

            filtered.forEach((t, idx)=>{
                const card = document.createElement('div');
                card.className = 'task-card group';
                card.dataset.id = t.id;
                
                const prioColor = t.priority === 'high' ? 'bg-red-500' : (t.priority === 'medium' ? 'bg-blue-500' : 'bg-slate-500');

                card.innerHTML = `
                    <div class="flex gap-4">
                        <div class="priority-indicator ${prioColor} mt-1"></div>
                        <div class="flex-1">
                            <input data-field="title" value="${escapeHtml(t.title)}" 
                                   class="w-full bg-transparent text-xs font-black uppercase italic text-white/90 border-b border-transparent focus:border-blue-500/30 mb-3 outline-none" />
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 rounded-full ${prioColor}"></div>
                                        <span class="text-[9px] font-bold text-white/30 uppercase tracking-tighter">${t.points || 0} Pts</span>
                                    </div>
                                    <select data-field="assignee" class="bg-transparent text-[9px] font-black text-white/40 uppercase outline-none cursor-pointer">
                                        ${renderAssigneeOptions(t.assignee)}
                                    </select>
                                </div>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button data-action="save" class="text-blue-500 hover:text-white transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3"/></svg></button>
                                    <button data-action="delete" class="text-red-500 hover:text-white transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7" stroke-width="3"/></svg></button>
                                </div>
                            </div>

                            <div class="mt-2">
                                <span class="label-cyber text-[7px] mb-2 block opacity-40">Sprint</span>
                                <select data-field="sprint" class="w-full bg-transparent text-[10px] font-bold outline-none cursor-pointer text-white/80 rounded border border-white/5 p-2">${renderSprintOptions(t.sprint_id)}</select>
                            </div>
                        </div>
                    </div>
                `;

                card.querySelector('[data-action="save"]').addEventListener('click', ()=> saveCard(card));
                card.querySelector('[data-action="delete"]').addEventListener('click', ()=> deleteCard(card));

                const target = cols[t.status] ?? cols.todo;
                target.appendChild(card);
                stats[t.status]++;
            });

            document.getElementById('count-todo').textContent = String(stats.todo).padStart(2, '0');
            document.getElementById('count-doing').textContent = String(stats.in_progress).padStart(2, '0');
            document.getElementById('count-done').textContent = String(stats.done).padStart(2, '0');
            computeVelocity(filtered);
        }

        function escapeHtml(s){ return String(s||'').replace(/[&<>\"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
        function renderAssigneeOptions(selected){
            let html = '<option value="">UNASSIGNED</option>';
            developers.forEach(d => { html += `<option value="${d.id}" ${selected && String(d.id)===String(selected.id)?'selected':''}>${d.name.toUpperCase()}</option>`; });
            return html;
        }

        function renderSprintOptions(selected){
            let html = '<option value="">Unassigned</option>';
            sprints.forEach(s => { html += `<option value="${s.id}" ${String(s.id)===String(selected)?'selected':''}>${escapeHtml(s.name)}</option>`; });
            return html;
        }

        async function saveCard(card){
            const id = card.dataset.id;
            const payload = {
                title: card.querySelector('[data-field="title"]').value.trim(),
                priority: card.querySelector('[data-field="priority"]')?.value || null,
                assignee_id: card.querySelector('[data-field="assignee"]')?.value || null,
                sprint_id: card.querySelector('[data-field="sprint"]')?.value || null,
            };
            const res = await fetch(`/dashboard/planning/${boardId}/tasks/${id}`,{ method: 'PATCH', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'}, body: JSON.stringify(payload) });
            if(res.ok){ const data = await res.json(); const returned = data.task ?? data; tasks = tasks.map(t=> String(t.id)===String(returned.id) ? Object.assign({}, t, returned) : t); render(); }
        }

        async function deleteCard(card){
            if(!confirm('ERASE DATA UNIT?')) return;
            const id = card.dataset.id;
            const res = await fetch(`/dashboard/planning/${boardId}/tasks/${id}`,{ method:'DELETE', headers: {'X-CSRF-TOKEN':token} });
            if(res.ok){ tasks = tasks.filter(t=>String(t.id)!==String(id)); render(); }
        }

        function computeVelocity(list){
            const vel = { todo: 0, doing: 0, done: 0 };
            list.forEach(t => {
                const pts = Number(t.points) || 0;
                if(t.status==='todo') vel.todo += pts;
                else if(t.status==='in_progress') vel.doing += pts;
                else if(t.status==='done') vel.done += pts;
            });
            document.getElementById('velTodo').textContent = vel.todo;
            document.getElementById('velDoing').textContent = vel.doing;
            document.getElementById('velDone').textContent = vel.done;
        }

        Object.values(cols).forEach(colEl => {
            Sortable.create(colEl, {
                group: 'kanban',
                animation: 250,
                onAdd: async function(evt){
                    const id = evt.item.dataset.id;
                    const status = evt.to.dataset.status;
                    await fetch(`/dashboard/planning/${id}/move`,{ method:'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token}, body: JSON.stringify({ status: status, position: evt.newIndex + 1 }) });
                    tasks = tasks.map(t => String(t.id)===String(id) ? Object.assign({}, t, { status: status }) : t);
                    render();
                }
            });
        });

        document.getElementById('createForm').addEventListener('submit', async function(e){
            e.preventDefault();
            const fd = new FormData(this);
            const res = await fetch(`/dashboard/planning/${boardId}/tasks`,{ method:'POST', headers: {'X-CSRF-TOKEN':token,'Accept':'application/json'}, body: new URLSearchParams(fd) });
            if(res.ok){ const data = await res.json(); tasks.unshift(data.task ?? data); this.reset(); render(); }
        });

        document.getElementById('filterSprint').addEventListener('change', function(){ activeSprint = this.value; render(); });
        document.getElementById('filterAssignee').addEventListener('change', function(){ activeAssignee = this.value; render(); });

        render();

        // Task Inject modal
        const tiModal = document.getElementById('task-inject-modal');
        document.getElementById('open-task-inject').onclick = () => tiModal.classList.remove('hidden');
        document.getElementById('close-task-inject').onclick = () => tiModal.classList.add('hidden');
        tiModal.addEventListener('click', e => { if (e.target === tiModal) tiModal.classList.add('hidden'); });

        requestAnimationFrame(() => {
            const page = document.getElementById('sprint-board-page');
            if (page) page.classList.add('is-ready');
        });
    });
    </script>
@endsection

@push('modals')
<div id="task-inject-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4" style="backdrop-filter:blur(12px);background:rgba(0,0,0,0.65);">
    <div id="task-inject-panel" class="relative w-full max-w-md rounded-[2rem] p-8 border border-white/10 shadow-2xl" style="background:rgba(10,11,40,0.92);backdrop-filter:blur(20px);">
        <div class="flex items-center justify-between mb-6">
            <h3 id="ti-heading" class="flex items-center gap-2" style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:0.25em;color:rgba(96,165,250,1);">
                <span style="display:inline-block;width:8px;height:8px;background:#3b82f6;border-radius:50%;animation:pulse 2s infinite;"></span>
                Task Injection
            </h3>
            <button id="close-task-inject" style="padding:0.5rem;border-radius:0.75rem;color:rgba(255,255,255,0.2);background:transparent;border:none;cursor:pointer;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="createForm" class="space-y-4">
            <input name="title" placeholder="UNIT TITLE..." required class="cyber-input font-black italic uppercase" />
            <div class="grid grid-cols-2 gap-3">
                <input name="points" type="number" placeholder="POINTS" class="cyber-input font-bold" />
                <select name="priority" class="cyber-input font-bold">
                    <option value="medium">MEDIUM</option>
                    <option value="low">LOW</option>
                    <option value="high">HIGH</option>
                </select>
            </div>
            <select name="sprint_id" class="cyber-input font-bold">
                <option value="">NO SPRINT</option>
                @foreach($sprints as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
            </select>
            <button type="submit" class="w-full py-4 bg-blue-600 hover:bg-white text-white hover:text-black font-black uppercase text-xs tracking-widest rounded-xl transition-all active:scale-95">
                Create Task
            </button>
        </form>
        <div id="ti-protocol" class="mt-6 p-4 rounded-2xl" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.05);">
            <span style="font-size:8px;font-weight:900;text-transform:uppercase;letter-spacing:0.25em;color:rgba(255,255,255,0.4);display:block;margin-bottom:0.5rem;">Protocol</span>
            <p id="ti-protocol-text" style="font-size:10px;color:rgba(255,255,255,0.3);line-height:1.6;text-transform:uppercase;letter-spacing:0.05em;">Drag units between sectors to update status. Changes propagate through the uplink in real-time.</p>
        </div>
    </div>
</div>

<style>
[data-theme="light"] #task-inject-modal { background: rgba(0,0,0,0.35) !important; }
[data-theme="light"] #task-inject-panel {
    background: #ffffff !important;
    border-color: rgba(31,41,55,0.12) !important;
    box-shadow: 0 20px 60px -20px rgba(31,41,55,0.25) !important;
}
[data-theme="light"] #ti-heading { color: #4F46E5 !important; }
[data-theme="light"] #task-inject-panel .cyber-input {
    background: #F1F5F9 !important;
    border-color: rgba(31,41,55,0.14) !important;
    color: #1F2937 !important;
}
[data-theme="light"] #task-inject-panel .cyber-input::placeholder { color: rgba(31,41,55,0.35) !important; }
[data-theme="light"] #ti-protocol { background: rgba(31,41,55,0.05) !important; border-color: rgba(31,41,55,0.10) !important; }
[data-theme="light"] #ti-protocol span { color: rgba(31,41,55,0.46) !important; }
[data-theme="light"] #ti-protocol-text { color: rgba(31,41,55,0.50) !important; }
[data-theme="light"] #close-task-inject { color: rgba(31,41,55,0.40) !important; }
[data-theme="light"] #close-task-inject:hover { color: #1F2937 !important; background: rgba(31,41,55,0.07) !important; }
[data-theme="light"] #open-task-inject { background: #ffffff !important; color: #1F2937 !important; }
[data-theme="light"] #open-task-inject:hover { background: #6366f1 !important; color: #fff !important; }
</style>
@endpush