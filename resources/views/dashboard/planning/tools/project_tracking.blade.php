@extends('layouts.dashboard')

@section('dashboard-content')
<style>
    /* Section Fade & Background */
    .matrix-bg {
        background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05) 0%, transparent 60%),
                    radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.05) 0%, transparent 60%);
    }

    .glass-panel {
        background: rgba(13, 15, 70, 0.4);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    /* Task Card Animations */
    .task-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.03);
        animation: slideIn 0.4s ease-out forwards;
    }
    .task-card:hover {
        background: rgba(255, 255, 255, 0.06);
        transform: translateX(8px);
        border-color: rgba(99, 102, 241, 0.3);
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Modal Transitions */
    .modal-backdrop {
        backdrop-filter: blur(8px);
        transition: opacity 0.3s ease;
    }
    .modal-content {
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform: scale(0.9) translateY(20px);
    }
    .modal-active .modal-content {
        transform: scale(1) translateY(0);
    }

    /* AI Toggle Shimmer */
    .ai-active {
        background: linear-gradient(90deg, #10b981, #3b82f6);
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
    }

    .label-cyber { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.2em; color: rgba(255, 255, 255, 0.3); }
    .btn-cyber { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; transition: all 0.2s ease; }

    /* ─── LIGHT MODE (sprint_planning-style) ─── */
    [data-theme="light"] .glass-panel {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.10) !important;
        box-shadow: 0 12px 28px -16px rgba(31,41,55,0.16) !important;
    }
    [data-theme="light"] .task-card {
        background: #ffffff;
        border-color: rgba(31,41,55,0.10);
    }
    [data-theme="light"] .task-card:hover {
        background: #F1F5F9;
        border-color: rgba(99,102,241,0.35);
        box-shadow: 0 8px 20px -10px rgba(99,102,241,0.18);
    }
    [data-theme="light"] .label-cyber { color: rgba(31,41,55,0.50) !important; }
    /* Modal overlay: matches sprint_planning overlay tint */
    [data-theme="light"] #modal-container {
        background: rgba(31,41,55,0.28) !important;
    }
    /* Modal inner panels: white background */
    [data-theme="light"] .modal-content {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.12) !important;
        box-shadow: 0 22px 38px -16px rgba(31,41,55,0.20) !important;
    }
    /* bg utilities */
    [data-theme="light"] .bg-white\/5 { background: #F1F5F9 !important; }
    [data-theme="light"] .bg-white\/10 { background: #E8EEFF !important; }
    [data-theme="light"] .bg-black\/40 { background: #ffffff !important; border-color: rgba(31,41,55,0.12) !important; }
    /* border utilities */
    [data-theme="light"] .border-white\/10 { border-color: rgba(31,41,55,0.12) !important; }
    [data-theme="light"] .border-white\/5 { border-color: rgba(31,41,55,0.08) !important; }
    /* text utilities */
    [data-theme="light"] .text-white { color: #1F2937 !important; }
    [data-theme="light"] .text-white\/60 { color: rgba(31,41,55,0.62) !important; }
    [data-theme="light"] .text-white\/40 { color: rgba(31,41,55,0.45) !important; }
    [data-theme="light"] .text-white\/20 { color: rgba(31,41,55,0.28) !important; }
    [data-theme="light"] .text-white\/10 { color: rgba(31,41,55,0.18) !important; }
    [data-theme="light"] .text-indigo-400 { color: #6366F1 !important; }
    [data-theme="light"] .text-indigo-300 { color: #6366F1 !important; }
    /* form inputs/selects */
    [data-theme="light"] input,
    [data-theme="light"] textarea,
    [data-theme="light"] select {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.14) !important;
        color: #1F2937 !important;
    }
    [data-theme="light"] input::placeholder,
    [data-theme="light"] textarea::placeholder { color: rgba(31,41,55,0.35) !important; }
    [data-theme="light"] input:focus,
    [data-theme="light"] textarea:focus,
    [data-theme="light"] select:focus {
        border-color: #6366F1 !important;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.10) !important;
    }
    /* cancel + close buttons (match sprint_planning cancel style) */
    [data-theme="light"] #create-task-cancel { color: rgba(31,41,55,0.50) !important; }
    [data-theme="light"] #create-task-cancel:hover { color: #1F2937 !important; }
    [data-theme="light"] #close-modal,
    [data-theme="light"] #close-ai-modal {
        background: rgba(31,41,55,0.06) !important;
        color: rgba(31,41,55,0.45) !important;
    }
    [data-theme="light"] #close-modal:hover,
    [data-theme="light"] #close-ai-modal:hover { color: #1F2937 !important; background: rgba(31,41,55,0.10) !important; }
    /* page background */
    [data-theme="light"] .matrix-bg { background: #F8FAFC; }
    /* task counter */
    [data-theme="light"] #task-counter { color: #6366F1 !important; }
    /* scrollbar in AI suggestions */
    [data-theme="light"] .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    [data-theme="light"] .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(31,41,55,0.18); border-radius: 10px; }
</style>

<div class="pt-12 px-8 pb-12 matrix-bg min-h-screen">
    <div class="max-w-5xl mx-auto">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div>
                <h1 class="text-4xl font-black italic uppercase tracking-tighter text-white">Project Tracking</h1>
                <p class="label-cyber mt-2 text-indigo-400/60">Active Board // <span class="text-white/60">{{ $board->name }}</span></p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-xl border border-white/5">
                    <span class="label-cyber">AI Co-Pilot</span>
                    <button id="ai-toggle" class="w-12 h-6 rounded-full bg-white/10 relative transition-all duration-300">
                        <div class="dot absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform duration-300"></div>
                    </button>
                </div>
                @if(auth()->check() && $board->canEdit(auth()->user()))
                    <button id="btn-ai-assist" class="btn-cyber px-5 py-3 bg-amber-500 hover:bg-amber-400 text-black rounded-xl shadow-lg shadow-amber-500/10">AI Suggest</button>
                    <button id="btn-sync-git" class="btn-cyber px-5 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl shadow-lg shadow-emerald-600/10">Sync Git</button>
                    <button id="btn-new-task" class="btn-cyber px-5 py-3 bg-white hover:bg-indigo-50 text-black rounded-xl shadow-xl">New Task</button>
                @endif

                <!-- Search controls: type + input + sort -->
                <div class="ml-4 flex items-center gap-2">
                    <select id="search-type" class="px-3 py-2 rounded-lg bg-black/40 border border-white/10 text-white text-sm outline-none">
                        <option value="full">Full Text</option>
                        <option value="title">Title</option>
                        <option value="description">Description</option>
                        <option value="assignee">Assignee</option>
                        <option value="status">Status</option>
                        <option value="has_pr">Has PR</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                    <input id="task-search" placeholder="Search tasks..." class="px-3 py-2 rounded-lg bg-black/40 border border-white/10 text-white text-sm outline-none" />
                    <select id="task-sort" class="px-3 py-2 rounded-lg bg-black/40 border border-white/10 text-white text-sm outline-none">
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="glass-panel rounded-[2.5rem] overflow-hidden">
            <div class="p-8 border-b border-white/5 flex justify-between items-center bg-white/2">
                <h3 class="label-cyber text-white/60 italic">Interactive Task Matrix</h3>
                <div id="task-counter" class="text-[10px] font-mono text-indigo-400">00 Nodes Active</div>
            </div>

            <div id="tasks-list" class="p-6 space-y-3 min-h-[400px]">
                {{-- Tasks rendered by JS --}}
            </div>
        </div>
    </div>
</div>

<div id="modal-container" class="fixed inset-0 z-50 hidden items-center justify-center p-4 modal-backdrop bg-black/60">
    <div id="task-modal" class="hidden bg-[#0a0b1e] rounded-[2rem] w-full max-w-2xl p-8 border border-white/10 modal-content shadow-2xl">
        <div class="flex justify-between items-start mb-8">
            <div>
                <span class="label-cyber text-indigo-400">Node Configuration</span>
                <h4 id="modal-title" class="text-2xl font-bold text-white mt-1 uppercase tracking-tight">Task</h4>
            </div>
            <div class="flex gap-2">
                <button id="open-pr" class="btn-cyber px-4 py-2 bg-emerald-600 text-white rounded-lg">Open PR</button>
                <button id="close-modal" class="btn-cyber px-4 py-2 bg-white/5 text-white/60 hover:bg-white/10 rounded-lg">Close</button>
            </div>
        </div>
        
        <div class="space-y-6">
            <div>
                <label class="label-cyber block mb-2">Technical Description</label>
                <p id="modal-desc" class="text-sm text-white/60 italic leading-relaxed bg-white/2 p-4 rounded-xl border border-white/5"></p>
            </div>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="label-cyber block mb-2">Assigned Agent</label>
                    <div id="modal-assignee" class="text-sm text-indigo-300 font-bold tracking-wide"></div>
                </div>
                <div>
                    <label class="label-cyber block mb-2">Asset Link (PR/URL)</label>
                    <div class="flex gap-2">
                        <input id="pr-input" class="flex-1 px-4 py-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs outline-none focus:border-indigo-500 transition-all" placeholder="https://github.com/...">
                        <button id="attach-pr" class="btn-cyber px-4 py-2 bg-indigo-600 text-white rounded-lg">Link</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="new-task-modal" class="hidden bg-[#0a0b1e] rounded-[2rem] w-full max-w-lg p-8 border border-white/10 modal-content shadow-2xl">
        <h4 class="text-xl font-black text-white mb-6 uppercase italic tracking-tighter">Initialize New Task</h4>
            <div class="space-y-4">
            <input id="new-title" placeholder="TASK IDENTITY" class="w-full px-5 py-4 rounded-xl bg-black/40 border border-white/10 text-white text-sm outline-none focus:border-indigo-500 font-bold uppercase italic">
            <textarea id="new-desc" placeholder="LOGIC PARAMETERS..." rows="4" class="w-full px-5 py-4 rounded-xl bg-black/40 border border-white/10 text-white text-sm outline-none focus:border-indigo-500 italic"></textarea>
                <div class="flex justify-end gap-3 pt-4">
                    <button id="create-task-cancel" class="btn-cyber px-6 py-3 text-white/40 hover:text-white transition-colors">Abort</button>
                    @if(auth()->check() && $board->canEdit(auth()->user()))
                        <button id="create-task-confirm" class="btn-cyber px-8 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl shadow-lg shadow-indigo-600/20">Commit Task</button>
                    @else
                        <button id="create-task-confirm" disabled class="btn-cyber px-8 py-3 bg-indigo-600/40 text-white rounded-xl">Commit Task (read-only)</button>
                    @endif
                </div>
        </div>
    </div>

    <div id="ai-suggest-modal" class="hidden bg-[#0a0b1e] rounded-[2rem] w-full max-w-4xl p-8 border border-white/10 modal-content shadow-2xl">
        <div class="flex justify-between items-center mb-8">
            <h4 class="text-xl font-black text-amber-500 italic uppercase">AI Neural Suggestions</h4>
            <button id="close-ai-modal" class="text-white/20 hover:text-white transition-colors"><i data-lucide="x"></i></button>
        </div>
        <div class="space-y-6">
            <div class="relative">
                <textarea id="ai-prompt" rows="2" placeholder="Describe the mission context (e.g. 'Build a React auth flow')..." class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/10 text-white text-sm outline-none focus:border-amber-500/50 italic"></textarea>
                <button id="ai-generate" class="absolute right-3 bottom-3 btn-cyber px-6 py-2 bg-amber-500 text-black rounded-lg hover:scale-105">Generate</button>
            </div>
            <div id="ai-suggestions" class="grid grid-cols-1 md:grid-cols-3 gap-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar"></div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2/dist/fuse.min.js"></script>
<script>
(() => {
    const BOARD_ID = {{ $board->id }};
    let tasks = @json($tasks ?? []);
    let filteredTasks = tasks.slice();

    const container = document.getElementById('modal-container');
    const tasksList = document.getElementById('tasks-list');
    const taskCounter = document.getElementById('task-counter');

    function showModal(modalId) {
        container.classList.remove('hidden');
        container.classList.add('flex');
        setTimeout(() => container.classList.add('modal-active'), 10);
        document.querySelectorAll('.modal-content').forEach(m => m.classList.add('hidden'));
        document.getElementById(modalId).classList.remove('hidden');
    }

    function hideModal() {
        container.classList.remove('modal-active');
        setTimeout(() => {
            container.classList.add('hidden');
            container.classList.remove('flex');
        }, 300);
    }

    let fuse = null;
    const FUSE_THRESHOLD = 500; // client-side fuzzy cap

    function buildFuse(){
        try {
            fuse = new Fuse(tasks, {
                keys: [
                    { name: 'title', weight: 0.6 },
                    { name: 'description', weight: 0.3 },
                    { name: 'assignee', weight: 0.1 }
                ],
                includeScore: true,
                threshold: 0.4,
                ignoreLocation: true,
                minMatchCharLength: 2,
            });
        } catch(e){ fuse = null; }
    }

    function shouldUseServerSearch(type){
        return type === 'server' || tasks.length > FUSE_THRESHOLD;
    }

    function debounce(fn, wait=200){
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(()=>fn(...args), wait); };
    }

    async function applyFiltersAndSort(){
        const q = document.getElementById('task-search').value.trim();
        const type = document.getElementById('search-type').value;
        const sort = document.getElementById('task-sort').value;

        if(!q){
            filteredTasks = tasks.slice();
            filteredTasks.sort((a,b) => new Date(b.created_at||0) - new Date(a.created_at||0));
            return;
        }

        if(shouldUseServerSearch(type)){
            const params = new URLSearchParams({ q: q, type: type, page: 1, per_page: 200 });
            try {
                const res = await fetch(`/dashboard/planning/${BOARD_ID}/project-tracking/search?` + params.toString(), { headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                const data = await res.json();
                if(data.ok){
                    filteredTasks = data.tasks;
                    return;
                }
            } catch(e){
                filteredTasks = [];
                return;
            }
        }

        // Use Fuse (fuzzy) when available or fall back to simple substring
        if(typeof Fuse !== 'undefined' && (tasks.length <= FUSE_THRESHOLD || type === 'fuzzy')){
            if(!fuse) buildFuse();
            if(fuse){
                const results = fuse.search(q, { limit: 200 });
                filteredTasks = results.map(r => r.item);
                return;
            }
        }

        // default local filtering
        filteredTasks = tasks.filter(t => {
            const lowerQ = q.toLowerCase();
            switch(type){
                case 'title': return (t.title||'').toLowerCase().includes(lowerQ);
                case 'description': return (t.description||'').toLowerCase().includes(lowerQ);
                case 'assignee': return (t.assignee||'').toLowerCase().includes(lowerQ);
                case 'status': return (t.status||'').toLowerCase().includes(lowerQ);
                case 'has_pr': return !!(t.pr_url && t.pr_url.length);
                case 'unassigned': return !(t.assignee && t.assignee.length);
                case 'full':
                default:
                    return ((t.title||'') + ' ' + (t.description||'') + ' ' + (t.assignee||'') + ' ' + (t.status||'')).toLowerCase().includes(lowerQ);
            }
        });
        filteredTasks.sort((a,b) => new Date(b.created_at||0) - new Date(a.created_at||0));
    }

    async function renderTasks(){
        await applyFiltersAndSort();
        tasksList.innerHTML = '';
        taskCounter.innerText = `${filteredTasks.length.toString().padStart(2, '0')} Nodes Active`;

        filteredTasks.forEach((t, i) => {
            const el = document.createElement('div');
            el.className = 'task-card p-5 bg-white/2 rounded-2xl flex justify-between items-center group';
            el.style.animationDelay = `${i * 0.05}s`;

            const prBadge = t.pr_url ? `<span class="ml-3 px-2 py-0.5 bg-emerald-500/10 text-emerald-400 text-[8px] font-black uppercase rounded border border-emerald-500/20 tracking-tighter">PR Linked</span>` : '';
            
            el.innerHTML = `
                <div class="flex items-center gap-4">
                    <div class="w-1 h-10 rounded-full bg-indigo-500/20 group-hover:bg-indigo-500 transition-colors"></div>
                    <div>
                        <div class="font-bold text-white uppercase tracking-tight flex items-center">${t.title} ${prBadge}</div>
                        <div class="text-[10px] text-white/30 uppercase font-black mt-1">
                            <span class="text-indigo-400/60">${t.assignee || 'Unassigned'}</span> • 
                            <span class="italic font-normal">${t.status}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transform translate-x-4 group-hover:translate-x-0 transition-all duration-300">
                    <button data-id="${t.id}" class="open-task btn-cyber px-4 py-2 bg-white/5 hover:bg-white text-white hover:text-black rounded-lg">View</button>
                    <button data-id="${t.id}" class="edit-task btn-cyber px-4 py-2 bg-indigo-600/20 text-indigo-300 hover:bg-indigo-600 hover:text-white rounded-lg">Edit</button>
                    <button data-id="${t.id}" class="delete-task btn-cyber px-4 py-2 bg-red-900/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg">Purge</button>
                </div>
            `;
            tasksList.appendChild(el);
        });

        // Event Delegation for buttons
        attachTaskEvents();
    }

    // wire search controls
    const searchInput = document.getElementById('task-search');
    const searchType = document.getElementById('search-type');
    const taskSort = document.getElementById('task-sort');
    const debouncedRender = debounce(() => renderTasks(), 200);
    searchInput.addEventListener('input', debouncedRender);
    searchType.addEventListener('change', () => { if(!shouldUseServerSearch(searchType.value)) buildFuse(); renderTasks(); });
    taskSort.addEventListener('change', renderTasks);

    function attachTaskEvents() {
        document.querySelectorAll('.open-task').forEach(btn => btn.onclick = () => openTask(btn.dataset.id));
        
        document.querySelectorAll('.edit-task').forEach(btn => btn.onclick = async () => {
            const t = tasks.find(x => x.id == btn.dataset.id);
            const title = prompt('Edit title', t.title);
            if(title === null) return;
            const desc = prompt('Edit description', t.description || '');
            
            const res = await fetch(`/dashboard/planning/${BOARD_ID}/tasks/${btn.dataset.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ title, description: desc })
            }).then(r => r.json());

            if(res.ok) {
                const idx = tasks.findIndex(x => x.id == btn.dataset.id);
                tasks[idx] = res.task;
                renderTasks();
            }
        });

        document.querySelectorAll('.delete-task').forEach(btn => btn.onclick = async () => {
            if(!confirm('TERMINATE NODE?')) return;
            const res = await fetch(`/dashboard/planning/${BOARD_ID}/tasks/${btn.dataset.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(r => r.json());
            if(res.ok) {
                tasks = tasks.filter(x => x.id != btn.dataset.id);
                renderTasks();
            }
        });
    }

    function openTask(id){
        const t = tasks.find(x => x.id == id);
        if(!t) return;
        document.getElementById('modal-title').innerText = t.title;
        document.getElementById('modal-desc').innerText = t.description || 'No data logs provided.';
        document.getElementById('modal-assignee').innerText = t.assignee || 'STATION UNASSIGNED';
        document.getElementById('pr-input').value = t.pr_url || '';
        document.getElementById('task-modal').dataset.taskId = id;
        showModal('task-modal');
    }

    // AI Toggle Logic
    const aiToggle = document.getElementById('ai-toggle');
    function updateAiUI(state) {
        const dot = aiToggle.querySelector('.dot');
        if(state) {
            aiToggle.classList.add('ai-active');
            dot.style.transform = 'translateX(24px)';
        } else {
            aiToggle.classList.remove('ai-active');
            dot.style.transform = 'translateX(0)';
        }
    }

    aiToggle.addEventListener('click', async () => {
        const key = 'project_ai_' + BOARD_ID;
        const current = localStorage.getItem(key) === '1';
        const next = !current;
        
        const res = await fetch(`/dashboard/planning/${BOARD_ID}/settings/ai`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ ai_enabled: next ? 1 : 0 })
        }).then(r => r.json());

        if(res.ok) {
            localStorage.setItem(key, next ? '1' : '0');
            updateAiUI(next);
        }
    });

    // New Task Flow
    document.getElementById('btn-new-task').onclick = () => showModal('new-task-modal');
    document.getElementById('create-task-cancel').onclick = hideModal;
    document.getElementById('create-task-confirm').onclick = async () => {
        const title = document.getElementById('new-title').value.trim();
        const description = document.getElementById('new-desc').value.trim();
        if(!title) return;

        const res = await fetch(`/dashboard/planning/${BOARD_ID}/project-tracking/tasks`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ title, description })
        }).then(r => r.json());

        if(res.ok) {
            // Prepend new task so latest appears on top
            tasks.unshift(res.task);
            renderTasks();
            hideModal();
            document.getElementById('new-title').value = '';
            document.getElementById('new-desc').value = '';
        }
    };

    // AI Suggestions
    document.getElementById('btn-ai-assist').onclick = () => showModal('ai-suggest-modal');
    document.getElementById('close-ai-modal').onclick = hideModal;
    document.getElementById('close-modal').onclick = hideModal;

    document.getElementById('ai-generate').onclick = async () => {
        const btn = document.getElementById('ai-generate');
        const prompt = document.getElementById('ai-prompt').value;
        btn.innerText = 'NEURAL LINK...';
        btn.disabled = true;

        try {
            const res = await fetch(`/dashboard/planning/${BOARD_ID}/project-tracking/ai-generate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ prompt })
            }).then(r => r.json());

            const container = document.getElementById('ai-suggestions');
            container.innerHTML = '';
            res.suggestions.forEach(s => {
                const card = document.createElement('div');
                card.className = 'p-4 bg-white/5 rounded-xl border border-white/5 hover:border-amber-500/30 transition-all';
                card.innerHTML = `
                    <div class="font-bold text-sm text-white mb-2">${s.title}</div>
                    <div class="text-[10px] text-white/40 mb-4 h-12 overflow-hidden">${s.description}</div>
                    <button class="select-suggestion btn-cyber w-full py-2 bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-black rounded-lg" 
                            data-title="${s.title}" data-desc="${s.description}" data-key="${s.key}">Initialize</button>
                `;
                container.appendChild(card);
            });

            document.querySelectorAll('.select-suggestion').forEach(sb => {
                sb.onclick = async () => {
                    const res2 = await fetch(`/dashboard/planning/${BOARD_ID}/project-tracking/ai-select`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ title: sb.dataset.title, description: sb.dataset.desc, key: sb.dataset.key })
                    }).then(r => r.json());
                    if(res2.ok) {
                        tasks.unshift(res2.task);
                        renderTasks();
                        hideModal();
                    }
                };
            });
        } finally {
            btn.innerText = 'GENERATE';
            btn.disabled = false;
        }
    };

    // Git Sync
    document.getElementById('btn-sync-git').onclick = async () => {
        const btn = document.getElementById('btn-sync-git');
        btn.innerText = 'SYNCING...';
        const res = await fetch(`/dashboard/planning/${BOARD_ID}/project-tracking/sync-git`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(r => r.json());
        if(res.ok) {
            // ensure tasks array is sorted newest-first from server payload
            tasks = (res.tasks || []).slice().sort((a,b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
            renderTasks();
            btn.innerText = 'SYNC GIT';
        }
    };

    // Initial setup
    if(typeof Fuse !== 'undefined' && tasks.length <= FUSE_THRESHOLD) buildFuse();
    renderTasks();
    const serverAi = {{ $board->ai_enabled ? 'true' : 'false' }};
    const savedAi = localStorage.getItem('project_ai_' + BOARD_ID);
    const aiState = savedAi === null ? serverAi : savedAi === '1';
    updateAiUI(aiState);
    lucide.createIcons();
})();
</script>
@endsection