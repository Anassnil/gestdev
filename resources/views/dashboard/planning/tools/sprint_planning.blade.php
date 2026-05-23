@extends('layouts.dashboard')

@section('dashboard-content')
<script src="https://unpkg.com/lucide@latest"></script>
<style>
    :root {
        --lm-bg: #F8FAFC;
        --lm-surface: #F1F5F9;
        --lm-primary-light: #E6E9FF;
        --lm-primary: #6366F1;
        --lm-text: #1F2937;
    }

    /* 1. MATRIX CORE LAYOUT */
    .matrix-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 1.5rem;
        height: 75vh; /* Fixed height to prevent endless scrolling */
        perspective: 1000px;
    }

    .sprint-planning-page {
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 220ms ease, transform 220ms ease;
        will-change: opacity, transform;
    }

    .sprint-planning-page.is-ready {
        opacity: 1;
        transform: translateY(0);
    }

    .matrix-quadrant {
        background: rgba(15, 15, 30, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 24px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: background-color 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
        position: relative;
    }

    /* QUADRANT FOCUS EFFECT */
    .matrix-quadrant:hover {
        border-color: rgba(99, 102, 241, 0.3);
        background: rgba(20, 20, 45, 0.6);
        box-shadow: 0 0 40px rgba(0,0,0,0.4);
    }

    /* 2. TASK DENSITY SYSTEM */
    .task-node {
        background: #1a1b3a;
        border-left: 4px solid var(--node-color);
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: grab;
        transition: transform 160ms ease, box-shadow 160ms ease;
        position: relative;
    }

    .task-node:active { cursor: grabbing; transform: scale(0.98); }

    .node-info h5 {
        font-size: 0.85rem;
        font-weight: 700;
        color: #e2e8f0;
        margin: 0;
        letter-spacing: -0.01em;
    }

    /* 3. AXIS LABELS */
    .axis-label-v {
        position: absolute;
        left: -40px;
        top: 50%;
        transform: rotate(-90deg) translateY(-50%);
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        color: rgba(255,255,255,0.2);
        letter-spacing: 0.5em;
    }

    .axis-label-h {
        position: absolute;
        bottom: -30px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        color: rgba(255,255,255,0.2);
        letter-spacing: 0.5em;
    }

    /* SCROLLING WITHIN QUADRANT */
    .node-list {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
        -webkit-mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
    }

    .node-list::-webkit-scrollbar { width: 3px; }
    .node-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

    .task-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        min-width: 120px;
        background: rgba(12, 14, 30, 0.98);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 22px rgba(0,0,0,0.4);
        z-index: 40;
    }

    .task-menu button {
        width: 100%;
        padding: 8px 10px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        text-align: left;
        color: rgba(255, 255, 255, 0.8);
        background: transparent;
    }

    .task-menu button:hover {
        background: rgba(99, 102, 241, 0.12);
        color: #ffffff;
    }

    .task-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(2, 6, 23, 0.52);
        backdrop-filter: blur(3px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 90;
    }

    .task-modal {
        width: min(460px, calc(100vw - 2rem));
        border-radius: 16px;
        background: #10142a;
        border: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: 0 22px 38px -16px rgba(0, 0, 0, 0.6);
        padding: 18px;
    }

    .task-modal-title {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: rgba(255,255,255,0.86);
    }

    .task-modal-desc {
        font-size: 12px;
        color: rgba(255,255,255,0.56);
        margin-top: 6px;
    }

    .task-modal-input {
        width: 100%;
        margin-top: 14px;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255,255,255,0.04);
        color: #fff;
        font-size: 14px;
    }

    .task-modal-actions {
        margin-top: 14px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .task-modal-btn {
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .task-modal-cancel {
        background: rgba(255,255,255,0.08);
        color: rgba(255,255,255,0.78);
    }

    .task-modal-confirm {
        background: #6366F1;
        color: #fff;
    }

    [data-theme="light"] .task-modal-overlay {
        background: rgba(31, 41, 55, 0.28);
    }

    [data-theme="light"] .task-modal {
        background: #F8FAFC;
        border-color: rgba(31,41,55,0.12);
        box-shadow: 0 22px 38px -20px rgba(31,41,55,0.28);
    }

    [data-theme="light"] .task-modal-title { color: #1F2937; }
    [data-theme="light"] .task-modal-desc { color: rgba(31,41,55,0.62); }
    [data-theme="light"] .task-modal-input {
        background: #fff;
        border-color: rgba(31,41,55,0.14);
        color: #1F2937;
    }
    [data-theme="light"] .task-modal-cancel {
        background: #E5EAF6;
        color: #1F2937;
    }

    /* LIGHT MODE PALETTE */
    [data-theme="light"] .sprint-planning-page {
        background: linear-gradient(180deg, var(--lm-bg) 0%, var(--lm-primary-light) 100%);
        border: 1px solid rgba(31, 41, 55, 0.08);
        box-shadow: 0 14px 28px -18px rgba(31, 41, 55, 0.18);
        color: var(--lm-text);
    }

    [data-theme="light"] .matrix-quadrant {
        background: linear-gradient(180deg, #FFFFFF 0%, var(--lm-surface) 100%);
        border-color: rgba(31, 41, 55, 0.10);
        box-shadow: 0 10px 20px -16px rgba(31, 41, 55, 0.16);
    }

    [data-theme="light"] .matrix-quadrant:hover {
        border-color: rgba(99, 102, 241, 0.40);
        background: linear-gradient(180deg, #FFFFFF 0%, var(--lm-primary-light) 100%);
        box-shadow: 0 16px 30px -18px rgba(99, 102, 241, 0.28);
    }

    [data-theme="light"] .task-node {
        background: #FFFFFF;
        border: 1px solid rgba(31, 41, 55, 0.10);
        box-shadow: 0 8px 16px -14px rgba(31, 41, 55, 0.20);
    }

    [data-theme="light"] .node-info h5,
    [data-theme="light"] .text-white,
    [data-theme="light"] .text-white\/80,
    [data-theme="light"] .text-white\/70 {
        color: var(--lm-text) !important;
    }

    [data-theme="light"] .text-white\/10 {
        color: rgba(31, 41, 55, 0.26) !important;
    }

    [data-theme="light"] .text-slate-500 {
        color: rgba(31, 41, 55, 0.58) !important;
    }

    [data-theme="light"] .bg-white\/5 {
        background-color: var(--lm-surface) !important;
        border-color: rgba(31, 41, 55, 0.10) !important;
    }

    [data-theme="light"] .border-white\/10,
    [data-theme="light"] .border-white\/5 {
        border-color: rgba(31, 41, 55, 0.12) !important;
    }

    [data-theme="light"] .bg-indigo-600 {
        background-color: var(--lm-primary) !important;
    }

    [data-theme="light"] .hover\:bg-indigo-500:hover {
        background-color: #575be8 !important;
    }

    [data-theme="light"] .text-indigo-500,
    [data-theme="light"] .text-indigo-400 {
        color: var(--lm-primary) !important;
    }

    [data-theme="light"] .node-list::-webkit-scrollbar-thumb {
        background: rgba(31, 41, 55, 0.20);
    }

    @media (prefers-reduced-motion: reduce) {
        .sprint-planning-page,
        .matrix-quadrant,
        .task-node {
            transition: none !important;
        }
    }
</style>

<div id="sprint-planning-page" class="sprint-planning-page p-8 lg:p-12 max-w-[1700px] mx-auto">
    
    <header class="flex justify-between items-start mb-12">
        <div>
            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-white">Strategy <span class="text-indigo-500">Matrix</span></h1>
            <p class="text-slate-500 font-bold mt-2">Team Velocity: <span class="text-white">42.5 SP</span> // Status: <span class="text-emerald-400">Stable</span></p>
        </div>
        <div class="flex gap-4">
            <div class="flex bg-white/5 rounded-2xl p-1 border border-white/10">
                <button id="matrixViewBtn" class="px-6 py-2 rounded-xl bg-indigo-600 text-[10px] font-black uppercase">Matrix</button>
                <button id="listViewBtn" class="px-6 py-2 rounded-xl text-[10px] font-black uppercase text-slate-500">List</button>
            </div>
            <button onclick="createTask('todo')" class="bg-white text-black px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-500 hover:text-white transition-all shadow-xl shadow-indigo-500/10">+ Deploy Task</button>
            <a href="{{ route('dashboard.planning.show', $board) }}" class="flex items-center gap-2 px-5 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-white hover:text-black transition-all" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Board
            </a>
        </div>
    </header>

    <div class="relative">
        <!-- Labels for UX Context -->
        <div class="absolute -left-12 inset-y-0 flex flex-col justify-between py-24 text-[10px] font-black text-white/10 uppercase [writing-mode:vertical-lr] rotate-180">
            <span>High Impact</span>
            <span>Low Impact</span>
        </div>
        <div class="absolute -top-12 inset-x-0 flex justify-between px-24 text-[10px] font-black text-white/10 uppercase">
            <span>Urgent</span>
            <span>Non-Urgent</span>
        </div>

        <div class="matrix-container">
            @php 
                $quadrants = [
                    ['id' => 'todo', 'title' => 'Critical / Urgent', 'color' => '#EF4444', 'icon' => 'zap'],
                    ['id' => 'schedule', 'title' => 'High Impact / Plan', 'color' => '#F59E0B', 'icon' => 'calendar'],
                    ['id' => 'delegate', 'title' => 'Maintenance / Busy', 'color' => '#6366F1', 'icon' => 'users'],
                    ['id' => 'delete', 'title' => 'Backlog / Archive', 'color' => '#475569', 'icon' => 'archive']
                ];
            @endphp

            @foreach($quadrants as $quad)
                <div class="matrix-quadrant" data-status="{{ $quad['id'] }}">
                    <!-- Quadrant Header -->
                    <div class="p-6 border-b border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg" style="background: {{ $quad['color'] }}20">
                                <i data-lucide="{{ $quad['icon'] }}" class="w-4 h-4" style="color: {{ $quad['color'] }}"></i>
                            </div>
                            <h3 class="text-[11px] font-black uppercase tracking-widest text-white/80">{{ $quad['title'] }}</h3>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500" data-count="{{ $quad['id'] }}">0</span>
                    </div>

                    <!-- Task List -->
                    <div class="node-list custom-scroll" id="list-{{ $quad['id'] }}" ondrop="drop(event)" ondragover="allowDrop(event)">
                        <!-- Tasks dynamically injected -->
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<div id="task-modal-overlay" class="task-modal-overlay" style="display:none">
    <div class="task-modal">
        <div id="task-modal-title" class="task-modal-title">Task Action</div>
        <div id="task-modal-desc" class="task-modal-desc">Details</div>
        <input id="task-modal-input" class="task-modal-input" type="text" placeholder="Task title">
        <div class="task-modal-actions">
            <button id="task-modal-cancel" type="button" class="task-modal-btn task-modal-cancel" onclick="closeTaskModal()">Cancel</button>
            <button id="task-modal-confirm" type="button" class="task-modal-btn task-modal-confirm" onclick="handleTaskModalConfirm()">Confirm</button>
        </div>
    </div>
</div>

<script>
    const modalState = {
        action: null,
        payload: null,
    };

    document.addEventListener('DOMContentLoaded', () => {
        const pageShell = document.getElementById('sprint-planning-page');
        const listViewBtn = document.getElementById('listViewBtn');
        if (listViewBtn) {
            listViewBtn.addEventListener('click', () => {
                window.location.href = "{{ route('dashboard.planning.project_tracking', $board) }}";
            });
        }

        document.addEventListener('click', (e) => {
            const cancelModalBtn = e.target.closest('#task-modal-cancel');
            const confirmModalBtn = e.target.closest('#task-modal-confirm');
            const modalOverlay = e.target.closest('#task-modal-overlay');
            const menuBtn = e.target.closest('[data-task-menu-btn]');
            const editBtn = e.target.closest('[data-task-edit]');
            const deleteBtn = e.target.closest('[data-task-delete]');

            if (cancelModalBtn) {
                e.preventDefault();
                e.stopPropagation();
                closeTaskModal();
                return;
            }

            if (confirmModalBtn) {
                e.preventDefault();
                e.stopPropagation();
                handleTaskModalConfirm();
                return;
            }

            if (modalOverlay && e.target.id === 'task-modal-overlay') {
                e.preventDefault();
                e.stopPropagation();
                closeTaskModal();
                return;
            }

            if (menuBtn) {
                e.preventDefault();
                e.stopPropagation();
                const taskId = menuBtn.dataset.taskMenuBtn;
                const menu = document.querySelector(`[data-task-menu="${taskId}"]`);
                if (!menu) return;
                const willOpen = menu.classList.contains('hidden');
                closeAllTaskMenus();
                if (willOpen) menu.classList.remove('hidden');
                return;
            }

            if (editBtn) {
                e.preventDefault();
                e.stopPropagation();
                const taskId = editBtn.dataset.taskEdit;
                closeAllTaskMenus();
                editTask(taskId);
                return;
            }

            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                const taskId = deleteBtn.dataset.taskDelete;
                closeAllTaskMenus();
                deleteTask(taskId);
                return;
            }

            closeAllTaskMenus();
        });

        lucide.createIcons();
        renderMatrix();

        // Stage the first paint for smoother reload and avoid abrupt content flash.
        requestAnimationFrame(() => {
            if (pageShell) pageShell.classList.add('is-ready');
        });
    });

    const boardId = {{ $board->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const tasks = {!! $board->tasks->map(fn($t) => [
        'id' => $t->id,
        'title' => $t->title,
        'status' => match($t->status ?? 'todo') {
            'in_progress' => 'delegate',
            'done' => 'delete',
            default => 'todo',
        },
        'points' => $t->points ?? 0,
        'color' => match($t->status) {
            'todo' => '#EF4444',
            'schedule' => '#F59E0B',
            'delegate' => '#6366F1',
            default => '#475569'
        }
    ])->toJson() !!};

    function escapeHtml(value) {
        return String(value).replace(/[&<>"'`]/g, (c) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '`': '&#96;'
        }[c]));
    }

    function renderMatrix() {
        // Clear all lists
        document.querySelectorAll('.node-list').forEach(l => l.innerHTML = '');
        const counts = { todo: 0, schedule: 0, delegate: 0, delete: 0 };

        tasks.forEach(t => {
            const list = document.getElementById(`list-${t.status}`);
            if(!list) return;

            counts[t.status] = (counts[t.status] || 0) + 1;

            const node = document.createElement('div');
            node.className = 'task-node';
            node.style.setProperty('--node-color', getStatusColor(t.status));
            node.draggable = true;
            node.id = `task-${t.id}`;
            node.innerHTML = `
                <div class="node-info">
                    <h5>${escapeHtml(t.title)}</h5>
                    <span class="text-[9px] font-bold text-slate-500 uppercase">${t.points} Story Points</span>
                </div>
                <div class="flex gap-2 relative">
                    <button type="button" data-task-menu-btn="${t.id}" class="p-1.5 hover:bg-white/5 rounded-md"><i data-lucide="more-vertical" class="w-3 h-3 text-slate-500"></i></button>
                    <div data-task-menu="${t.id}" class="task-menu hidden">
                        <button type="button" data-task-edit="${t.id}">Edit</button>
                        <button type="button" data-task-delete="${t.id}">Delete</button>
                    </div>
                </div>
            `;
            
            node.ondragstart = (e) => e.dataTransfer.setData("text", t.id);
            list.appendChild(node);
        });

        // Update UI Counts
        Object.keys(counts).forEach(id => {
            const el = document.querySelector(`[data-count="${id}"]`);
            if(el) el.textContent = counts[id];
        });
        lucide.createIcons();
    }

    function allowDrop(e) { e.preventDefault(); }

    function mapUiStatusToBackend(status) {
        const map = {
            todo: 'todo',
            schedule: 'todo',
            delegate: 'in_progress',
            delete: 'done',
        };
        return map[status] || 'todo';
    }

    async function persistTaskMove(taskId, uiStatus, position) {
        try {
            await fetch(`/dashboard/planning/${taskId}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    status: mapUiStatusToBackend(uiStatus),
                    position: position,
                }),
            });
        } catch (err) {
            console.error('Failed to persist move', err);
        }
    }

    function drop(e) {
        e.preventDefault();
        const taskId = e.dataTransfer.getData("text");
        const targetQuadrant = e.target.closest('.matrix-quadrant');
        if(!targetQuadrant) return;
        const targetStatus = targetQuadrant.dataset.status;
        
        const task = tasks.find(t => String(t.id) === String(taskId));
        if(task) {
            task.status = targetStatus;
            renderMatrix();

            const list = document.getElementById(`list-${targetStatus}`);
            const position = list ? list.querySelectorAll('.task-node').length : 1;
            persistTaskMove(task.id, targetStatus, position);
        }
    }

    function getStatusColor(status) {
        const colors = { todo: '#EF4444', schedule: '#F59E0B', delegate: '#6366F1', delete: '#475569' };
        return colors[status] || '#475569';
    }

    function closeAllTaskMenus() {
        document.querySelectorAll('[data-task-menu]').forEach((el) => el.classList.add('hidden'));
    }

    function openTaskModal({ action, title, description, value = '', confirmText = 'Confirm', showInput = true, payload = null }) {
        const overlay = document.getElementById('task-modal-overlay');
        const titleEl = document.getElementById('task-modal-title');
        const descEl = document.getElementById('task-modal-desc');
        const inputEl = document.getElementById('task-modal-input');
        const confirmBtn = document.getElementById('task-modal-confirm');

        if (!overlay || !titleEl || !descEl || !inputEl || !confirmBtn) return;

        modalState.action = action;
        modalState.payload = payload;

        titleEl.textContent = title;
        descEl.textContent = description;
        confirmBtn.textContent = confirmText;

        inputEl.value = value;
        inputEl.style.display = showInput ? 'block' : 'none';

        overlay.style.display = 'flex';
        if (showInput) setTimeout(() => inputEl.focus(), 50);
    }

    function closeTaskModal() {
        const overlay = document.getElementById('task-modal-overlay');
        if (overlay) overlay.style.display = 'none';
        modalState.action = null;
        modalState.payload = null;
    }

    async function handleTaskModalConfirm() {
        const inputEl = document.getElementById('task-modal-input');
        const nextValue = (inputEl?.value || '').trim();

        if (modalState.action === 'create') {
            if (!nextValue) return;
            await createTaskRequest(modalState.payload?.status || 'todo', nextValue);
            closeTaskModal();
            return;
        }

        if (modalState.action === 'edit') {
            if (!nextValue) return;
            await editTaskRequest(modalState.payload?.taskId, nextValue);
            closeTaskModal();
            return;
        }

        if (modalState.action === 'delete') {
            await deleteTaskRequest(modalState.payload?.taskId);
            closeTaskModal();
        }
    }

    async function editTask(taskId) {
        const task = tasks.find(t => String(t.id) === String(taskId));
        if (!task) return;

        openTaskModal({
            action: 'edit',
            title: 'Edit Task',
            description: 'Update the task title.',
            value: task.title,
            confirmText: 'Save',
            showInput: true,
            payload: { taskId: taskId },
        });
    }

    async function editTaskRequest(taskId, nextTitle) {
        const task = tasks.find(t => String(t.id) === String(taskId));
        if (!task) return;
        if (nextTitle === task.title) return;

        try {
            const res = await fetch(`/dashboard/planning/${boardId}/tasks/${task.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ title: nextTitle.trim() }),
            });

            if (!res.ok) {
                console.error('Task edit failed', await res.text());
                return;
            }

            task.title = nextTitle.trim();
            renderMatrix();
        } catch (err) {
            console.error('Task edit error', err);
        }
    }

    async function deleteTask(taskId) {
        const task = tasks.find(t => String(t.id) === String(taskId));
        if (!task) return;

        openTaskModal({
            action: 'delete',
            title: 'Delete Task',
            description: `Delete \"${task.title}\"? This action cannot be undone.`,
            confirmText: 'Delete',
            showInput: false,
            payload: { taskId: taskId },
        });
    }

    async function deleteTaskRequest(taskId) {
        const task = tasks.find(t => String(t.id) === String(taskId));
        if (!task) return;

        try {
            const res = await fetch(`/dashboard/planning/${boardId}/tasks/${task.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                console.error('Task delete failed', await res.text());
                return;
            }

            const idx = tasks.findIndex(t => String(t.id) === String(taskId));
            if (idx > -1) tasks.splice(idx, 1);
            renderMatrix();
        } catch (err) {
            console.error('Task delete error', err);
        }
    }

    async function createTask(status) {
        openTaskModal({
            action: 'create',
            title: 'Create Task',
            description: 'Enter a title for the new task.',
            value: '',
            confirmText: 'Create',
            showInput: true,
            payload: { status: status || 'todo' },
        });
    }

    async function createTaskRequest(status, title) {
        if (!title || !title.trim()) return;

        try {
            const form = new FormData();
            form.append('title', title.trim());
            form.append('description', '');

            const res = await fetch(`/dashboard/planning/${boardId}/tasks`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: form,
            });

            if (!res.ok) {
                console.error('Task creation failed', await res.text());
                return;
            }

            const data = await res.json();
            const created = data.task || data;

            tasks.push({
                id: created.id,
                title: created.title,
                status: status || 'todo',
                points: created.points || 0,
                color: getStatusColor(status || 'todo'),
            });

            renderMatrix();
        } catch (err) {
            console.error('Task creation error', err);
        }
    }
</script>
@endsection