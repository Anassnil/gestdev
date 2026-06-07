@extends('layouts.dashboard')

@section('dashboard-content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('dashboard.planning._permission')

    <style>
        /* Premium Glass Morphism & Cyber UI */
        .glass-panel {
            background: rgba(13, 15, 70, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .heading-cyber { font-family: 'Inter', sans-serif; font-weight: 900; font-style: italic; text-transform: uppercase; letter-spacing: -0.05em; }
        .label-cyber { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.3em; color: rgba(255, 255, 255, 0.3); }

        /* Input Styling */
        .input-cyber {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        .input-cyber:focus { border-color: #3b82f6; background: rgba(59, 130, 246, 0.05); outline: none; box-shadow: 0 0 15px rgba(59, 130, 246, 0.2); }

        /* Task Node Animations */
        .task-node {
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            animation: slideIn 0.4s ease forwards;
            border-left: 4px solid transparent;
        }
        .task-node:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(10px);
            border-left-color: #3b82f6;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Sortable Placeholder */
        .sortable-ghost { opacity: 0.3; background: #3b82f6 !important; }
        .sortable-drag { cursor: grabbing; }

        /* Scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.2); border-radius: 10px; }
        
        /* page load smoothness */
        .backlog-page {
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 220ms ease, transform 220ms ease;
        }
        .backlog-page.is-ready { opacity: 1; transform: translateY(0); }

        .btn-action { transition: all 0.2s ease; cursor: pointer; }
        .btn-action:active { transform: scale(0.92); }

        /* ─── LIGHT MODE ─── */
        [data-theme="light"] .text-white { color: #1F2937 !important; }
        [data-theme="light"] .text-white\/90 { color: rgba(31,41,55,0.90) !important; }
        [data-theme="light"] .text-white\/60 { color: rgba(31,41,55,0.60) !important; }
        [data-theme="light"] .text-white\/40 { color: rgba(31,41,55,0.50) !important; }
        [data-theme="light"] .text-white\/30 { color: rgba(31,41,55,0.38) !important; }

        [data-theme="light"] .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            border-color: rgba(31, 41, 55, 0.10);
            box-shadow: 0 8px 24px -12px rgba(31, 41, 55, 0.16);
        }

        [data-theme="light"] .label-cyber { color: rgba(31,41,55,0.48); }

        [data-theme="light"] .input-cyber {
            background: #ffffff;
            border-color: rgba(31,41,55,0.14);
            color: #1F2937;
        }
        [data-theme="light"] .input-cyber::placeholder { color: rgba(31,41,55,0.35); }
        [data-theme="light"] .input-cyber:focus {
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.10);
        }

        /* page bg */
        [data-theme="light"] .pt-8.px-6 {
            background: #F8FAFC;
            min-height: 100vh;
        }

        /* task node */
        [data-theme="light"] .task-node {
            background: #ffffff;
            border-color: rgba(31,41,55,0.08);
        }
        [data-theme="light"] .task-node:hover {
            background: #F1F5F9;
            border-left-color: #3b82f6;
        }

        /* inline inputs inside task cards */
        [data-theme="light"] .task-node input,
        [data-theme="light"] .task-node select {
            color: #1F2937 !important;
        }
        [data-theme="light"] .task-node [data-field="title"] {
            color: #1F2937 !important;
        }
        [data-theme="light"] .bg-white\/5 {
            background: rgba(31,41,55,0.05) !important;
        }
        [data-theme="light"] .border-white\/10 {
            border-color: rgba(31,41,55,0.12) !important;
        }

        /* chart area */
        [data-theme="light"] .bg-\[\#070718\] {
            background: #F1F5F9 !important;
        }
        [data-theme="light"] #chartRangeSelect {
            background: #ffffff;
            color: #1F2937;
            border: 1px solid rgba(31,41,55,0.12);
        }

        /* info tip box */
        [data-theme="light"] .bg-blue-500\/5 {
            background: rgba(59,130,246,0.06) !important;
        }
        [data-theme="light"] .border-blue-500\/10 {
            border-color: rgba(59,130,246,0.18) !important;
        }

        /* Quick Inject modal – light mode */
        [data-theme="light"] #quick-inject-modal {
            background: rgba(0,0,0,0.35) !important;
        }
        [data-theme="light"] #quick-inject-modal > div {
            background: #ffffff !important;
            border-color: rgba(31,41,55,0.12) !important;
            box-shadow: 0 20px 60px -20px rgba(31,41,55,0.25) !important;
        }
        [data-theme="light"] #quick-inject-modal h3 {
            color: #4F46E5 !important;
        }
        [data-theme="light"] #quick-inject-modal label {
            color: rgba(31,41,55,0.48) !important;
        }
        [data-theme="light"] #quick-inject-modal input,
        [data-theme="light"] #quick-inject-modal select {
            background: #F1F5F9 !important;
            border-color: rgba(31,41,55,0.14) !important;
            color: #1F2937 !important;
        }
        [data-theme="light"] #quick-inject-modal input::placeholder {
            color: rgba(31,41,55,0.35) !important;
        }
        [data-theme="light"] #quick-inject-modal #bulkPrioritizeBtn {
            background: rgba(31,41,55,0.06) !important;
            border-color: rgba(31,41,55,0.12) !important;
            color: rgba(31,41,55,0.50) !important;
        }
        [data-theme="light"] #quick-inject-modal p {
            color: rgba(31,41,55,0.50) !important;
        }
        [data-theme="light"] #quick-inject-modal #close-quick-inject {
            color: rgba(31,41,55,0.35) !important;
        }

        /* counter badge */
        [data-theme="light"] #taskCounter {
            background: rgba(31,41,55,0.07);
        }

        /* primary CTA */
        [data-theme="light"] .hover\:bg-blue-600:hover { background-color: #2563eb !important; }
        [data-theme="light"] .hover\:text-white:hover { color: #fff !important; }

        /* search input */
        [data-theme="light"] #searchInput {
            background: #ffffff;
            border: 1px solid rgba(31,41,55,0.12);
            color: #1F2937;
        }
        [data-theme="light"] #searchInput::placeholder { color: rgba(31,41,55,0.35); }

        @media (prefers-reduced-motion: reduce) {
            .task-node { transition: none !important; animation: none !important; }
        }
    </style>

    <div id="backlog-page" class="backlog-page pt-8 px-6 pb-20 text-white">
        <div class="max-w-screen-2xl mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-6">
                <div class="flex-1">
                    <nav class="label-cyber mb-3 flex items-center gap-2">
                        <a href="{{ route('dashboard.planning.show', $board) }}" class="hover:text-blue-400">Board</a>
                        <span>/</span>
                        <span class="text-blue-500 italic">Backlog Grooming</span>
                    </nav>
                    <h1 class="text-5xl heading-cyber leading-none">Backlog Grooming</h1>
                    <p class="text-white/40 text-sm mt-3 italic border-l-2 border-white/10 pl-4 max-w-2xl">
                        Optimize your workflow by refining tasks and adjusting priority tiers for <span class="text-blue-400">{{ $board->name }}</span>.
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="relative group">
                        <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-white/30 group-focus-within:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input id="searchInput" placeholder="SEARCH ARCHIVES..." class="pl-11 pr-6 py-3 rounded-xl glass-panel text-[10px] font-black tracking-widest uppercase focus:ring-0 outline-none w-64" />
                    </div>
                    <a href="{{ route('dashboard.planning.show', $board) }}" class="px-6 py-3 glass-panel rounded-xl label-cyber text-white/60 hover:text-white flex items-center gap-3 btn-action">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Exit
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-12">
                    <div class="glass-panel rounded-[2.5rem] p-8">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="heading-cyber text-xl italic flex items-center gap-3">
                                <span class="w-2 h-8 bg-blue-500 rounded-full"></span>
                                Backlog Registry
                            </h3>
                            <div class="flex items-center gap-3">
                                <span class="label-cyber py-1 px-3 bg-white/5 rounded-full" id="taskCounter">0 Tasks</span>
                                <button id="open-quick-inject" class="flex items-center gap-2 px-4 py-2 glass-panel rounded-xl border border-white/10 hover:border-blue-500/40 label-cyber text-blue-400 hover:text-blue-300 transition-all btn-action text-[10px] font-black uppercase tracking-widest">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3"/></svg>
                                    Quick Inject
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-6 bg-[#070718] rounded p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-bold text-white">Tasks evolution</div>
                                <div class="flex items-center gap-3">
                                    <div class="text-xs text-white/60">Created vs Completed</div>
                                    <select id="chartRangeSelect" class="bg-[#02020a] text-white text-xs rounded px-2 py-1">
                                        <option value="7">Last 7 days</option>
                                        <option value="14" selected>Last 14 days</option>
                                        <option value="30">Last 30 days</option>
                                    </select>
                                </div>
                            </div>
                            <div style="height:140px">
                                <canvas id="tasksEvolutionChart"></canvas>
                            </div>
                        </div>

                        <div id="tasksList" class="space-y-4 custom-scroll max-h-[70vh] overflow-y-auto pr-4">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $allTasks = $board->tasks->map(function($t){
            return [
                'id' => $t->id,
                'status' => $t->status,
                'created_at' => $t->created_at ? $t->created_at->toDateString() : null,
                'updated_at' => $t->updated_at ? $t->updated_at->toDateString() : null,
            ];
        })->values()->toArray();
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        // Quick Inject modal
        const CAN_EDIT = window.CAN_EDIT === true || window.CAN_EDIT === 'true';
        const qiModal = document.getElementById('quick-inject-modal');
        const openQiBtn = document.getElementById('open-quick-inject');
        const closeQiBtn = document.getElementById('close-quick-inject');
        if(openQiBtn && qiModal && CAN_EDIT) openQiBtn.onclick = () => qiModal.classList.remove('hidden');
        if(closeQiBtn && qiModal) closeQiBtn.onclick = () => qiModal.classList.add('hidden');
        if(qiModal) qiModal.addEventListener('click', e => { if (e.target === qiModal) qiModal.classList.add('hidden'); });

        const boardId = {{ $board->id }};
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let serverTasks = @json($board->tasks->where('status','todo')->values());
        let allTasks = @json($allTasks);

        const tasksList = document.getElementById('tasksList');
        const quickCreateForm = document.getElementById('quickCreateForm');
        const searchInput = document.getElementById('searchInput');

        function render(list){
            tasksList.innerHTML = '';
            document.getElementById('taskCounter').textContent = list.length + ' Tasks';
            
            list.forEach((t, index) => {
                const el = document.createElement('div');
                el.className = 'p-5 glass-panel rounded-2xl flex items-center justify-between gap-6 task-node group';
                el.style.animationDelay = `${index * 50}ms`;
                el.dataset.id = t.id;
                
                const priorityColor = t.priority === 'high' ? 'text-red-400' : (t.priority === 'medium' ? 'text-blue-400' : 'text-gray-400');

                el.innerHTML = `
                    <div class="flex items-center gap-4 shrink-0 cursor-grab opacity-20 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 8h16M4 16h16"/></svg>
                    </div>
                    <div class="flex-1">
                        <input data-field="title" value="${escapeHtml(t.title)}" 
                               class="w-full bg-transparent font-black italic uppercase tracking-tight text-white/90 border-b border-transparent focus:border-blue-500/30 pb-1 outline-none transition-all mb-2" />
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <span class="label-cyber text-[8px]">Points:</span>
                                <input data-field="points" type="number" value="${t.points ?? ''}" 
                                       class="w-14 bg-white/5 border border-white/10 rounded-md px-2 py-1 text-xs text-center" />
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="label-cyber text-[8px]">Priority:</span>
                                <select data-field="priority" class="bg-white/5 border border-white/10 rounded-md px-2 py-1 text-xs ${priorityColor} font-bold uppercase tracking-widest outline-none">
                                    <option value="low" ${t.priority === 'low' ? 'selected' : ''}>Low</option>
                                    <option value="medium" ${t.priority === 'medium' ? 'selected' : ''}>Med</option>
                                    <option value="high" ${t.priority === 'high' ? 'selected' : ''}>High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all">
                        ${CAN_EDIT ? `<button data-action="save" class="p-3 bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white rounded-xl transition-all btn-action">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button data-action="delete" class="p-3 bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white rounded-xl transition-all btn-action">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>` : ''}
                    </div>
                `;

                if(CAN_EDIT){
                    const saveBtn = el.querySelector('[data-action="save"]');
                    const delBtn = el.querySelector('[data-action="delete"]');
                    if(saveBtn) saveBtn.addEventListener('click', () => saveTask(el));
                    if(delBtn) delBtn.addEventListener('click', () => deleteTask(el));
                }

                tasksList.appendChild(el);
            });
        }

        function escapeHtml(s){ return String(s).replace(/[&<>"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

        async function saveTask(el){
            const id = el.dataset.id;
            const btn = el.querySelector('[data-action="save"]');
            btn.classList.add('animate-pulse');
            
            const payload = {
                title: el.querySelector('[data-field="title"]').value.trim(),
                points: el.querySelector('[data-field="points"]').value || null,
                priority: el.querySelector('[data-field="priority"]').value
            };

            const res = await fetch(`/dashboard/planning/${boardId}/tasks/${id}`,{
                method: 'PATCH',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
                body: JSON.stringify(payload)
            });

            if(res.ok){
                const data = await res.json();
                const idx = serverTasks.findIndex(t=>String(t.id)===String(id));
                if(idx>-1) serverTasks[idx] = data.task ?? data;
                btn.classList.remove('animate-pulse');
                filterAndRender();
            }
        }

        async function deleteTask(el){
            if(!confirm('ERASE FROM BACKLOG?')) return;
            el.style.transform = 'scale(0.9) opacity(0)';
            setTimeout(async () => {
                const id = el.dataset.id;
                const res = await fetch(`/dashboard/planning/${boardId}/tasks/${id}`,{ method: 'DELETE', headers: {'X-CSRF-TOKEN':token} });
                if(res.ok){
                    serverTasks = serverTasks.filter(t=>String(t.id)!==String(id));
                    allTasks = allTasks.filter(t => String(t.id) !== String(id));
                    filterAndRender();
                }
            }, 300);
        }

        quickCreateForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const fd = new FormData(this);
            const res = await fetch(`/dashboard/planning/${boardId}/tasks`,{
                method: 'POST',
                headers: {'X-CSRF-TOKEN': token, 'Accept':'application/json'},
                body: (new URLSearchParams({ title: fd.get('title'), priority: fd.get('priority'), points: fd.get('points') }))
            });
            if(res.ok){
                const data = await res.json();
                const created = data.task ?? data;
                serverTasks.unshift(created);
                allTasks.unshift({ id: created.id, status: created.status ?? 'todo', created_at: created.created_at ? created.created_at.split('T')[0] : (new Date()).toISOString().slice(0,10), updated_at: created.updated_at ? created.updated_at.split('T')[0] : (new Date()).toISOString().slice(0,10) });
                this.reset();
                filterAndRender();
            }
        });

        Sortable.create(tasksList, {
            animation: 350,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            handle: '.cursor-grab',
            onEnd: async function(){
                const items = Array.from(tasksList.children);
                for(let i=0;i<items.length;i++){
                    const id = items[i].dataset.id;
                    await fetch(`/dashboard/planning/${id}/move`,{
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token},
                        body: JSON.stringify({ status: 'todo', position: i+1 })
                    });
                }
            }
        });

        // Chart: tasks evolution (created vs completed) for last 14 days
        const chartCtx = document.getElementById('tasksEvolutionChart')?.getContext('2d');
        let tasksChart = null;
        function lastNDates(n){
            const arr = [];
            for(let i=n-1;i>=0;i--){
                const d = new Date(); d.setDate(d.getDate() - i);
                arr.push(d.toISOString().slice(0,10));
            }
            return arr;
        }

        function renderChart(){
            if(!chartCtx) return;
            const range = Number(document.getElementById('chartRangeSelect')?.value || 14);
            const labels = lastNDates(range);
            const created = labels.map(d => allTasks.filter(t => t.created_at === d).length);
            const completed = labels.map(d => allTasks.filter(t => (t.status === 'done' && t.updated_at === d)).length);

            const datasets = [
                { label: 'Created', data: created, borderColor: '#60A5FA', backgroundColor: 'rgba(96,165,250,0.12)', tension: 0.3 },
                { label: 'Completed', data: completed, borderColor: '#34D399', backgroundColor: 'rgba(52,211,153,0.12)', tension: 0.3 }
            ];

            if(tasksChart){
                tasksChart.data.labels = labels;
                tasksChart.data.datasets = datasets;
                tasksChart.update();
            } else {
                tasksChart = new Chart(chartCtx, { type: 'line', data: { labels, datasets }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } } });
            }
        }

        function filterAndRender(){
            const q = searchInput.value.trim().toLowerCase();
            const filtered = serverTasks.filter(t => !q || (t.title && t.title.toLowerCase().includes(q)) );
            render(filtered);
            // update chart when list changes or filter applied
            try{ renderChart(); }catch(e){ console.warn('Chart update failed', e); }
        }

        searchInput.addEventListener('input', filterAndRender);
        document.getElementById('chartRangeSelect')?.addEventListener('change', renderChart);
        filterAndRender();

        requestAnimationFrame(() => {
            const page = document.getElementById('backlog-page');
            if (page) page.classList.add('is-ready');
        });
    });
    </script>
@endsection

@push('modals')
<div id="quick-inject-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4" style="backdrop-filter:blur(12px); background:rgba(0,0,0,0.65);">
    <div class="relative w-full max-w-md rounded-[2rem] p-8 border border-white/10 shadow-2xl" style="background:rgba(13,15,70,0.85);backdrop-filter:blur(20px);">
        <div class="flex items-center justify-between mb-6">
            <h3 style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:0.3em;color:rgba(96,165,250,1);" class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3"/></svg>
                Quick Inject
            </h3>
            <button id="close-quick-inject" class="p-2 rounded-xl transition-all" style="color:rgba(255,255,255,0.2);" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="quickCreateForm" class="space-y-4">
            <div class="space-y-1">
                <label style="font-size:8px;font-weight:900;text-transform:uppercase;letter-spacing:0.3em;color:rgba(255,255,255,0.3);">Objective Title</label>
                <input name="title" placeholder="ENTER TASK..." required style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:white;width:100%;padding:1rem;border-radius:0.75rem;font-size:0.875rem;font-weight:700;text-transform:uppercase;" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="space-y-1">
                    <label style="font-size:8px;font-weight:900;text-transform:uppercase;letter-spacing:0.3em;color:rgba(255,255,255,0.3);">Points</label>
                    <input name="points" type="number" placeholder="00" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:white;width:100%;padding:1rem;border-radius:0.75rem;font-size:0.875rem;" />
                </div>
                <div class="space-y-1">
                    <label style="font-size:8px;font-weight:900;text-transform:uppercase;letter-spacing:0.3em;color:rgba(255,255,255,0.3);">Priority</label>
                    <select name="priority" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:white;width:100%;padding:1rem;border-radius:0.75rem;font-size:0.875rem;">
                        <option value="low">Low</option>
                        <option value="medium" selected>Med</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            <div class="pt-4 flex flex-col gap-3">
                <button type="submit" style="width:100%;padding:1rem;background:white;color:black;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:0.3em;border-radius:0.75rem;border:none;cursor:pointer;" onmouseover="this.style.background='#2563eb';this.style.color='white'" onmouseout="this.style.background='white';this.style.color='black'">
                    Create Objective
                </button>
                <button id="bulkPrioritizeBtn" type="button" style="width:100%;padding:1rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.4);font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:0.3em;border-radius:0.75rem;cursor:pointer;">
                    Bulk Prioritize Selected
                </button>
            </div>
        </form>
        <div class="mt-6 flex items-start gap-3 p-4" style="background:rgba(59,130,246,0.05);border-radius:1rem;border:1px solid rgba(59,130,246,0.1);">
            <svg class="w-5 h-5 shrink-0" style="color:#60a5fa;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p style="font-size:10px;color:rgba(255,255,255,0.4);font-weight:500;text-transform:uppercase;letter-spacing:0.1em;line-height:1.6;">Drag nodes to re-order the chronological sequence of the backlog.</p>
        </div>
    </div>
</div>
@endpush