@extends('layouts.dashboard')

@section('dashboard-content')
<div class="space-y-6 p-2">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.people.index') }}" class="p-2 rounded-lg hover:bg-white/10 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-extrabold tracking-tight">Inbox</h1>
        </div>
        <a href="{{ route('dashboard.people.index') }}" class="text-xs px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white/70 transition-all">Manage community</a>
    </div>

    <div class="glass rounded-2xl border border-white/10 p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-white/50">Create Project Group Chat</h2>
                <p class="text-xs text-white/40 mt-1">Use a guided builder to set name, project, and members in seconds.</p>
            </div>
            <button id="open-group-builder" type="button" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-all">
                Launch Group Builder
            </button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-4 text-xs">
            <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-white/70">
                Associates: <span class="text-white font-semibold">{{ $associateOptions->count() }}</span>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-white/70">
                Projects: <span class="text-white font-semibold">{{ $boards->count() }}</span>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-white/70 col-span-2 md:col-span-1">
                Owner: <span class="text-white font-semibold">You</span>
            </div>
        </div>
    </div>

    @if($threads->isEmpty() && $groupThreads->isEmpty())
        <div class="glass rounded-2xl border border-white/10 p-14 flex flex-col items-center gap-3 text-white/40">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 16V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h11l4 4v-4z"/>
            </svg>
            <p class="font-semibold">No conversations yet</p>
            <a href="{{ route('dashboard.people.index') }}" class="mt-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-all">Find people to message</a>
        </div>
    @else
        @if($groupThreads->isNotEmpty())
            <div class="glass rounded-2xl border border-white/10 overflow-hidden">
                <div class="px-5 py-3 border-b border-white/5 text-xs font-bold uppercase tracking-wider text-white/50">Project Group Chats</div>
                <div class="divide-y divide-white/5">
                    @foreach($groupThreads as $thread)
                        <a href="{{ route('dashboard.people.group.chat', $thread->chat) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-white/5 transition-all">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-600 to-blue-700 flex items-center justify-center text-sm font-black text-white/90 shrink-0">
                                GC
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold {{ $thread->unread > 0 ? 'text-white' : 'text-white/80' }} truncate">{{ $thread->chat->name }}</span>
                                    <span class="text-xs text-white/40 shrink-0">{{ $thread->lastMessage?->created_at?->diffForHumans() }}</span>
                                </div>
                                <div class="text-xs text-white/40 truncate">
                                    {{ $thread->chat->board?->name ? 'Project: '.$thread->chat->board->name.' · ' : '' }}{{ $thread->chat->members_count }} members
                                </div>
                                @if($thread->lastMessage)
                                    <div class="text-sm truncate mt-0.5 {{ $thread->unread > 0 ? 'text-white/80 font-medium' : 'text-white/40' }}">
                                        {{ $thread->lastMessage->body }}
                                    </div>
                                @endif
                            </div>
                            @if($thread->unread > 0)
                                <span class="shrink-0 min-w-[1.25rem] h-5 px-1.5 bg-blue-600 text-white text-xs font-black rounded-full flex items-center justify-center">
                                    {{ $thread->unread }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($threads->isNotEmpty())
            <div class="glass rounded-2xl border border-white/10 divide-y divide-white/5 overflow-hidden">
                <div class="px-5 py-3 border-b border-white/5 text-xs font-bold uppercase tracking-wider text-white/50">Direct Messages</div>
                @foreach($threads as $thread)
                    <a href="{{ route('dashboard.people.chat', $thread->partner) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-white/5 transition-all">
                        {{-- Avatar --}}
                        @if($thread->partner->avatar_url)
                            <img src="{{ $thread->partner->avatar_url }}" alt="{{ $thread->partner->name }}" class="w-12 h-12 rounded-xl object-cover border border-white/15 shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-sm font-black text-white/90 shrink-0">
                                {{ $thread->partner->initials }}
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-bold {{ $thread->unread > 0 ? 'text-white' : 'text-white/80' }}">{{ $thread->partner->name }}</span>
                                <span class="text-xs text-white/40 shrink-0 ml-2">{{ $thread->lastMessage?->created_at?->diffForHumans() }}</span>
                            </div>
                            <div class="text-sm truncate mt-0.5 {{ $thread->unread > 0 ? 'text-white/80 font-medium' : 'text-white/40' }}">
                                @if($thread->lastMessage?->sender_id === auth()->id())
                                    <span class="text-white/30">You: </span>
                                @endif
                                {{ $thread->lastMessage?->body }}
                            </div>
                        </div>

                        @if($thread->unread > 0)
                            <span class="shrink-0 min-w-[1.25rem] h-5 px-1.5 bg-blue-600 text-white text-xs font-black rounded-full flex items-center justify-center">
                                {{ $thread->unread }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</div>

<div id="group-builder-modal" class="hidden fixed inset-0 z-50">
    <div id="group-builder-backdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-3xl glass rounded-2xl border border-white/10 shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold">New Project Group</h3>
                    <p class="text-xs text-white/40">Step-by-step setup for focused team chats.</p>
                </div>
                <button id="close-group-builder" type="button" class="p-2 rounded-lg hover:bg-white/10 text-white/60 hover:text-white transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-5 pt-4">
                <div class="grid grid-cols-3 gap-2 text-[11px] font-semibold">
                    <div id="step-pill-1" class="rounded-full px-3 py-1.5 text-center bg-blue-600 text-white">1. Basics</div>
                    <div id="step-pill-2" class="rounded-full px-3 py-1.5 text-center bg-white/5 text-white/50">2. Members</div>
                    <div id="step-pill-3" class="rounded-full px-3 py-1.5 text-center bg-white/5 text-white/50">3. Review</div>
                </div>
            </div>

            <form id="group-builder-form" method="POST" action="{{ route('dashboard.people.group.create') }}" class="p-5">
                @csrf

                <section data-step="1" class="space-y-4">
                    <div>
                        <label for="group-name" class="block text-xs font-semibold text-white/60 mb-1">Group Name</label>
                        <input id="group-name" type="text" name="name" required maxlength="120" placeholder="Example: Sprint Alpha - API" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-white/30 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label for="group-board" class="block text-xs font-semibold text-white/60 mb-1">Project (Optional)</label>
                        <select id="group-board" name="board_id" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:outline-none focus:border-blue-500">
                            <option value="">No project</option>
                            @foreach($boards as $board)
                                <option value="{{ $board->id }}">{{ $board->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>

                <section data-step="2" class="space-y-4 hidden">
                    <div>
                        <label for="member-search" class="block text-xs font-semibold text-white/60 mb-1">Find Associates</label>
                        <input id="member-search" type="text" placeholder="Search by name..." class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-white/30 focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-white/60 mb-2">Selected Members</p>
                        <div id="selected-members" class="min-h-[44px] rounded-xl border border-white/10 bg-white/5 p-2 flex flex-wrap gap-2"></div>
                        <div id="selected-member-inputs"></div>
                        <p class="text-[11px] text-white/40 mt-1">You will be included automatically as owner.</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-white/60 mb-2">Available Associates</p>
                        <div id="associate-list" class="max-h-56 overflow-y-auto rounded-xl border border-white/10 divide-y divide-white/5 bg-black/10">
                            @forelse($associateOptions as $member)
                                <button
                                    type="button"
                                    class="associate-item w-full text-left px-3 py-2.5 hover:bg-white/5 transition-all"
                                    data-member-id="{{ $member->id }}"
                                    data-member-name="{{ $member->name }}"
                                    data-member-type="{{ ucfirst($member->relationship_type) }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-semibold">{{ $member->name }}</span>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full border border-blue-500/30 bg-blue-600/20 text-blue-300">{{ ucfirst($member->relationship_type) }}</span>
                                    </div>
                                </button>
                            @empty
                                <div class="px-3 py-4 text-sm text-white/40">No associates yet. Add associates from Community first.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section data-step="3" class="space-y-4 hidden">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4 space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-white/50">Group Name</span>
                            <span id="review-name" class="text-sm font-semibold">-</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-white/50">Project</span>
                            <span id="review-project" class="text-sm font-semibold">No project</span>
                        </div>
                        <div>
                            <span class="text-xs text-white/50">Members</span>
                            <div id="review-members" class="mt-2 flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                </section>

                <div class="pt-5 flex items-center justify-between gap-2">
                    <button id="builder-prev" type="button" class="px-4 py-2 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-sm text-white/80 transition-all">Back</button>
                    <div class="flex items-center gap-2">
                        <button id="builder-cancel" type="button" class="px-4 py-2 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-sm text-white/80 transition-all">Cancel</button>
                        <button id="builder-next" type="button" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition-all">Next</button>
                        <button id="builder-submit" type="submit" class="hidden px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-all">Create Group Chat</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const modal = document.getElementById('group-builder-modal');
    const openBtn = document.getElementById('open-group-builder');
    const closeBtn = document.getElementById('close-group-builder');
    const cancelBtn = document.getElementById('builder-cancel');
    const backdrop = document.getElementById('group-builder-backdrop');
    const prevBtn = document.getElementById('builder-prev');
    const nextBtn = document.getElementById('builder-next');
    const submitBtn = document.getElementById('builder-submit');
    const form = document.getElementById('group-builder-form');
    const stepPills = [
        document.getElementById('step-pill-1'),
        document.getElementById('step-pill-2'),
        document.getElementById('step-pill-3')
    ];
    const stepSections = Array.from(form.querySelectorAll('section[data-step]'));
    const memberSearch = document.getElementById('member-search');
    const associateItems = Array.from(document.querySelectorAll('.associate-item'));
    const selectedMembersEl = document.getElementById('selected-members');
    const selectedMemberInputs = document.getElementById('selected-member-inputs');
    const groupNameInput = document.getElementById('group-name');
    const groupBoardInput = document.getElementById('group-board');
    const reviewName = document.getElementById('review-name');
    const reviewProject = document.getElementById('review-project');
    const reviewMembers = document.getElementById('review-members');

    let currentStep = 1;
    const selected = new Map();

    function openModal() {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        goToStep(1);
        setTimeout(() => groupNameInput.focus(), 40);
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function goToStep(step) {
        currentStep = Math.max(1, Math.min(3, step));

        stepSections.forEach((section) => {
            section.classList.toggle('hidden', Number(section.dataset.step) !== currentStep);
        });

        stepPills.forEach((pill, idx) => {
            const stepNum = idx + 1;
            const active = stepNum === currentStep;
            pill.classList.toggle('bg-blue-600', active);
            pill.classList.toggle('text-white', active);
            pill.classList.toggle('bg-white/5', !active);
            pill.classList.toggle('text-white/50', !active);
        });

        prevBtn.classList.toggle('invisible', currentStep === 1);
        nextBtn.classList.toggle('hidden', currentStep === 3);
        submitBtn.classList.toggle('hidden', currentStep !== 3);

        if (currentStep === 3) {
            renderReview();
        }
    }

    function renderSelected() {
        selectedMembersEl.innerHTML = '';
        selectedMemberInputs.innerHTML = '';

        if (selected.size === 0) {
            const empty = document.createElement('span');
            empty.className = 'text-xs text-white/35';
            empty.textContent = 'No members selected yet.';
            selectedMembersEl.appendChild(empty);
            return;
        }

        selected.forEach((member, id) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-600/20 border border-emerald-500/30 text-emerald-200 text-xs';
            chip.innerHTML = `${member.name} <span class="text-emerald-300/80">(${member.type})</span> <span class="text-white/70">x</span>`;
            chip.addEventListener('click', () => {
                selected.delete(id);
                syncAssociateStyles();
                renderSelected();
            });
            selectedMembersEl.appendChild(chip);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'member_ids[]';
            hidden.value = id;
            selectedMemberInputs.appendChild(hidden);
        });
    }

    function syncAssociateStyles() {
        associateItems.forEach((btn) => {
            const id = btn.dataset.memberId;
            const isSelected = selected.has(id);
            btn.classList.toggle('bg-emerald-500/10', isSelected);
            btn.classList.toggle('border-l-2', isSelected);
            btn.classList.toggle('border-emerald-400', isSelected);
        });
    }

    function renderReview() {
        const projectLabel = groupBoardInput.options[groupBoardInput.selectedIndex]?.text || 'No project';
        reviewName.textContent = groupNameInput.value.trim() || '-';
        reviewProject.textContent = groupBoardInput.value ? projectLabel : 'No project';
        reviewMembers.innerHTML = '';

        if (selected.size === 0) {
            const noMembers = document.createElement('span');
            noMembers.className = 'text-xs text-white/40';
            noMembers.textContent = 'No associates selected. You can still create the group and invite later.';
            reviewMembers.appendChild(noMembers);
        } else {
            selected.forEach((member) => {
                const badge = document.createElement('span');
                badge.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-blue-600/20 border border-blue-500/30 text-blue-200';
                badge.textContent = member.name + ' (' + member.type + ')';
                reviewMembers.appendChild(badge);
            });
        }
    }

    function validateCurrentStep() {
        if (currentStep === 1) {
            if (!groupNameInput.value.trim()) {
                groupNameInput.focus();
                groupNameInput.classList.add('border-rose-500');
                return false;
            }
            groupNameInput.classList.remove('border-rose-500');
        }
        return true;
    }

    if (memberSearch) {
        memberSearch.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            associateItems.forEach((btn) => {
                const name = (btn.dataset.memberName || '').toLowerCase();
                const type = (btn.dataset.memberType || '').toLowerCase();
                const visible = q === '' || name.includes(q) || type.includes(q);
                btn.classList.toggle('hidden', !visible);
            });
        });
    }

    associateItems.forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.memberId;
            if (!id) return;

            if (selected.has(id)) {
                selected.delete(id);
            } else {
                selected.set(id, {
                    name: btn.dataset.memberName || 'Member',
                    type: btn.dataset.memberType || 'Associate',
                });
            }

            syncAssociateStyles();
            renderSelected();
        });
    });

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    prevBtn?.addEventListener('click', () => goToStep(currentStep - 1));
    nextBtn?.addEventListener('click', () => {
        if (!validateCurrentStep()) return;
        goToStep(currentStep + 1);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    form?.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
    });

    renderSelected();
    syncAssociateStyles();
})();
</script>
@endsection
