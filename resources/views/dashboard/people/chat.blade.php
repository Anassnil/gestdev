@extends('layouts.dashboard')

@section('dashboard-content')
<div class="flex h-[calc(100vh-11rem)] gap-3 p-2">
    <aside class="w-72 shrink-0 flex flex-col glass rounded-2xl border border-white/10 overflow-hidden">
        <div class="px-4 pt-4 pb-3 shrink-0 border-b border-white/8">
            <div class="flex items-center justify-between mb-3">
                <span class="font-bold text-sm">Messages</span>
                <a href="{{ route('dashboard.people.inbox') }}" class="p-1.5 rounded-lg hover:bg-white/10 transition-all text-white/50 hover:text-white/90" title="All conversations">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                </a>
            </div>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white/30 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input id="sidebar-search" type="text" placeholder="Search people or groups"
                    class="w-full pl-8 pr-3 py-1.5 rounded-lg bg-white/5 border border-white/8 text-xs text-white placeholder-white/30 focus:outline-none focus:border-blue-500 transition-colors">
            </div>
        </div>

        <div class="flex-1 overflow-y-auto py-2">
            @if($threads->count())
                <div class="px-3 pt-2 pb-1">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-white/30">Direct</span>
                </div>
                @foreach($threads as $thread)
                    @php $active = $thread->id === $user->id; @endphp
                    <a href="{{ $thread->url }}" data-name="{{ strtolower($thread->name) }}" data-user-id="{{ $thread->id }}"
                        class="sidebar-item flex items-center gap-3 mx-2 px-2 py-2 rounded-xl transition-all {{ $active ? 'bg-blue-600/20 border border-blue-500/30' : 'hover:bg-white/6 border border-transparent' }}">
                        <div class="relative shrink-0">
                            @if($thread->avatar)
                                <img src="{{ $thread->avatar }}" alt="{{ $thread->name }}" class="w-9 h-9 rounded-xl object-cover border {{ $active ? 'border-blue-400/50' : 'border-white/10' }}">
                            @else
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-[11px] font-black text-white/90">
                                    {{ $thread->initials }}
                                </div>
                            @endif
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-[#0a0a1a]"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold truncate {{ $active ? 'text-blue-300' : 'text-white/90' }}">{{ $thread->name }}</span>
                                <span class="text-[10px] text-white/35">{{ $thread->last_time }}</span>
                            </div>
                            <div class="flex items-center gap-1 mt-0.5">
                                <p class="text-[11px] text-white/35 truncate flex-1">{{ $thread->preview ?: 'Start a conversation' }}</p>
                                @if($thread->unread > 0)
                                    <span class="shrink-0 min-w-[18px] h-[18px] rounded-full bg-blue-500 text-white text-[10px] font-bold flex items-center justify-center px-1">{{ $thread->unread }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            @endif

            @if($groupChats->count())
                <div class="px-3 pt-3 pb-1">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-white/30">Groups</span>
                </div>
                @foreach($groupChats as $gc)
                    <a href="{{ $gc->url }}" data-name="{{ strtolower($gc->name) }}"
                        class="sidebar-item flex items-center gap-3 mx-2 px-2 py-2 rounded-xl transition-all hover:bg-white/6 border border-transparent">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-600 to-blue-700 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zM3 8a4 4 0 118 0"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-xs font-semibold truncate text-white/90">{{ $gc->name }}</div>
                                <span class="text-[10px] text-white/35">{{ $gc->last_time }}</span>
                            </div>
                            <p class="text-[11px] text-white/35 truncate mt-0.5">{{ $gc->preview }}</p>
                        </div>
                    </a>
                @endforeach
            @endif
        </div>

        <div class="px-3 py-3 border-t border-white/8 shrink-0">
            <a href="{{ route('dashboard.people.index') }}" class="flex items-center justify-center gap-2 w-full py-2 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 border border-blue-500/20 text-blue-300 text-xs font-semibold transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Conversation
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <div class="flex items-center gap-3 mb-3 px-1 shrink-0">
            @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-xl object-cover border border-white/15">
            @else
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-xs font-black text-white/90">{{ $user->initials }}</div>
            @endif
            <div class="flex-1 min-w-0">
                <a href="{{ route('dashboard.people.show', $user) }}" class="font-bold hover:text-blue-400 transition-colors text-sm">{{ $user->name }}</a>
                <div id="presence-status" class="text-xs {{ $partnerOnline ? 'text-emerald-400/80' : 'text-white/40' }}">{{ $partnerOnline ? 'Online' : 'Away' }}</div>
            </div>
            <div class="hidden md:flex items-center gap-1 p-1 rounded-lg bg-white/5 border border-white/10">
                <button id="tab-chat" class="px-2.5 py-1 text-xs rounded-md bg-blue-600 text-white">Chat</button>
                <button id="tab-media" class="px-2.5 py-1 text-xs rounded-md text-white/70 hover:text-white hover:bg-white/10">Shared</button>
            </div>
            <input id="message-search" type="text" placeholder="Search in chat"
                class="w-36 md:w-48 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-xs placeholder-white/30 focus:outline-none focus:border-blue-500">
        </div>

        <div id="chat-messages" class="flex-1 overflow-y-auto glass rounded-2xl border border-white/10 p-4 space-y-3 scroll-smooth">
            @php $currentDate = null; @endphp
            @forelse($messages as $msg)
                @php
                    $mine = $msg->sender_id === auth()->id();
                    $msgDate = $msg->created_at->toDateString();
                @endphp
                @if($currentDate !== $msgDate)
                    @php $currentDate = $msgDate; @endphp
                    <div class="date-divider text-center text-[10px] text-white/35" data-date="{{ $msgDate }}">
                        <span class="px-2 py-1 rounded-md bg-white/5 border border-white/10">{{ $msg->created_at->isToday() ? 'Today' : ($msg->created_at->isYesterday() ? 'Yesterday' : $msg->created_at->format('M d, Y')) }}</span>
                    </div>
                @endif

                <div class="message-row flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $msg->id }}" data-message-body="{{ e($msg->body) }}">
                    @unless($mine)
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-7 h-7 rounded-lg object-cover border border-white/15 shrink-0 mr-2 mt-1">
                        @else
                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-[10px] font-black text-white/90 shrink-0 mr-2 mt-1">{{ $user->initials }}</div>
                        @endif
                    @endunless
                    <div class="max-w-[66%] group">
                        @if($msg->replyTo)
                            <div class="mb-1 px-2 py-1 rounded-lg bg-white/6 border border-white/10 text-[11px] text-white/60 truncate">Replying to: {{ $msg->replyTo->body ?: 'Attachment' }}</div>
                        @endif
                        <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed {{ $mine ? 'bg-blue-600 text-white rounded-br-md' : 'bg-white/10 text-white/90 rounded-bl-md' }}">
                            @if($msg->attachment_path)
                                @if(str_starts_with($msg->attachment_mime ?? '', 'image/'))
                                    <img src="{{ Storage::disk('public')->url($msg->attachment_path) }}" alt="{{ $msg->attachment_name }}" class="max-w-xs max-h-52 rounded-xl mb-1 object-contain cursor-pointer" onclick="window.open(this.src,'_blank')">
                                @else
                                    <a href="{{ Storage::disk('public')->url($msg->attachment_path) }}" target="_blank" download="{{ $msg->attachment_name }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors mb-1 text-xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span class="truncate max-w-[200px]">{{ $msg->attachment_name }}</span>
                                    </a>
                                @endif
                            @endif
                            @if($msg->body){{ $msg->body }}@endif
                        </div>

                        @php
                            $reactions = $msg->reactions->groupBy('emoji')->map(function($rows, $emoji) {
                                return [
                                    'emoji' => $emoji,
                                    'count' => $rows->count(),
                                    'mine' => $rows->contains(fn($r) => $r->user_id === auth()->id()),
                                ];
                            })->values();
                        @endphp
                        @if($reactions->count())
                            <div class="mt-1 flex flex-wrap gap-1" data-reactions-for="{{ $msg->id }}">
                                @foreach($reactions as $r)
                                    <button type="button" class="reaction-btn px-1.5 py-0.5 text-[10px] rounded-full border {{ $r['mine'] ? 'border-blue-400/60 bg-blue-600/20' : 'border-white/10 bg-white/5' }}" data-message-id="{{ $msg->id }}" data-emoji="{{ $r['emoji'] }}">{{ $r['emoji'] }} {{ $r['count'] }}</button>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-1 flex flex-wrap gap-1" data-reactions-for="{{ $msg->id }}"></div>
                        @endif

                        <div class="flex items-center gap-2 text-[10px] text-white/30 mt-1 {{ $mine ? 'justify-end' : 'justify-start' }}">
                            <span>{{ $msg->created_at->format('H:i') }}</span>
                            @if($msg->edited_at)<span>edited</span>@endif
                            @if($mine && $msg->read_at)<span>Seen</span>@endif
                        </div>

                        <div class="opacity-0 group-hover:opacity-100 transition-opacity mt-1 flex gap-1 {{ $mine ? 'justify-end' : 'justify-start' }}">
                            <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="reply" data-message-id="{{ $msg->id }}">Reply</button>
                            <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="react" data-emoji="👍" data-message-id="{{ $msg->id }}">👍</button>
                            <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="react" data-emoji="❤️" data-message-id="{{ $msg->id }}">❤️</button>
                            <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="copy" data-message-id="{{ $msg->id }}">Copy</button>
                            <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="forward" data-message-id="{{ $msg->id }}">Forward</button>
                            @if($mine)
                                <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="edit" data-message-id="{{ $msg->id }}">Edit</button>
                                <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-red-500/15 hover:bg-red-500/30 text-[10px]" data-action="delete" data-message-id="{{ $msg->id }}">Delete</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div id="empty-state" class="flex flex-col items-center justify-center h-full text-white/30 gap-2 py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 16V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h11l4 4v-4z"/>
                    </svg>
                    <p class="text-sm font-medium">No messages yet. Say hello!</p>
                </div>
            @endforelse
        </div>

        <div id="media-panel" class="hidden flex-1 overflow-y-auto glass rounded-2xl border border-white/10 p-4">
            <div id="media-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3"></div>
        </div>

        <div id="typing-indicator" class="text-xs text-white/45 h-4 mt-2 px-1"></div>

        <form id="chat-form" class="flex flex-col gap-2 mt-2 shrink-0" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="reply-to-id" name="reply_to_id" value="">
            <div id="reply-preview" class="hidden items-center gap-2 px-3 py-2 rounded-xl bg-blue-600/15 border border-blue-500/30 text-xs">
                <span class="text-blue-300">Replying:</span>
                <span id="reply-preview-text" class="truncate flex-1 text-white/80"></span>
                <button type="button" id="reply-clear" class="text-white/50 hover:text-red-400">Cancel</button>
            </div>

            <div id="attach-preview" class="hidden items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-xs text-white/70">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span id="attach-name" class="truncate flex-1"></span>
                <button type="button" id="attach-clear" class="ml-auto text-white/40 hover:text-red-400 transition-colors text-base leading-none">&times;</button>
            </div>

            <div class="flex gap-2">
                <input type="file" id="chat-file" name="attachment" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.mp4,.mp3">
                <button type="button" id="attach-btn" title="Attach file" class="px-3 py-2.5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </button>
                <textarea id="chat-input" name="body" rows="1" placeholder="Write a message…" class="flex-1 resize-none px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-blue-500 text-sm leading-relaxed" style="max-height:120px;"></textarea>
                <button id="send-btn" type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-xl font-semibold text-sm transition-all shrink-0 text-white">Send</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
(function () {
    const sendUrl = @json(route('dashboard.people.message', $user));
    const pollUrl = @json(route('dashboard.people.poll', $user));
    const searchUrl = @json(route('dashboard.people.search', $user));
    const mediaUrl = @json(route('dashboard.people.media', $user));
    const typingUrl = @json(route('dashboard.people.typing', $user));
    const reactUrlT = @json(route('dashboard.people.message.react', ['user' => $user, 'message' => '__MSG__']));
    const editUrlT = @json(route('dashboard.people.message.edit', ['user' => $user, 'message' => '__MSG__']));
    const deleteUrlT = @json(route('dashboard.people.message.delete', ['user' => $user, 'message' => '__MSG__']));
    const forwardUrlT = @json(route('dashboard.people.message.forward', ['user' => $user, 'message' => '__MSG__']));
    const partnerAvatar = @json($user->avatar_url);
    const partnerInitials = @json($user->initials);

    const form = document.getElementById('chat-form');
    const box = document.getElementById('chat-messages');
    const mediaPanel = document.getElementById('media-panel');
    const mediaGrid = document.getElementById('media-grid');
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-btn');
    const searchInput = document.getElementById('message-search');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const fileInput = document.getElementById('chat-file');
    const attachBtn = document.getElementById('attach-btn');
    const attachPreview = document.getElementById('attach-preview');
    const attachName = document.getElementById('attach-name');
    const attachClear = document.getElementById('attach-clear');
    const typingEl = document.getElementById('typing-indicator');
    const presenceEl = document.getElementById('presence-status');

    const tabChat = document.getElementById('tab-chat');
    const tabMedia = document.getElementById('tab-media');

    const replyPreview = document.getElementById('reply-preview');
    const replyPreviewText = document.getElementById('reply-preview-text');
    const replyClear = document.getElementById('reply-clear');
    const replyToInput = document.getElementById('reply-to-id');

    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    let lastId = {{ $messages->max('id') ?? 0 }};
    let lastDate = @json(optional($messages->last())->created_at?->toDateString());
    let replyToId = null;
    let insertedNewDivider = false;
    let typingTimer = null;
    let typingSentTrue = false;
    let isSending = false;

    const sharedFiles = @json($sharedFiles ?? []);

    function esc(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatTime(iso) {
        const d = new Date(iso);
        return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
    }

    function dateLabel(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const test = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const diff = (today - test) / 86400000;
        if (diff === 0) return 'Today';
        if (diff === 1) return 'Yesterday';
        return d.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
    }

    function scrollBottom() {
        box.scrollTop = box.scrollHeight;
    }

    scrollBottom();

    document.getElementById('sidebar-search')?.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.sidebar-item').forEach((el) => {
            const name = el.dataset.name || '';
            el.style.display = name.includes(q) ? '' : 'none';
        });
    });

    function setReply(msgId, text) {
        replyToId = msgId;
        if (replyToInput) replyToInput.value = String(msgId);
        replyPreviewText.textContent = text || 'Message';
        replyPreview.classList.remove('hidden');
        replyPreview.classList.add('flex');
        input.focus();
    }

    function clearReply() {
        replyToId = null;
        if (replyToInput) replyToInput.value = '';
        replyPreview.classList.add('hidden');
        replyPreview.classList.remove('flex');
        replyPreviewText.textContent = '';
    }

    if (replyClear) {
        replyClear.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            clearReply();
        });
    }

    form.addEventListener('click', function (e) {
        const btn = e.target.closest('#reply-clear');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        clearReply();
    });

    attachBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', function () {
        if (this.files[0]) {
            attachName.textContent = this.files[0].name;
            attachPreview.classList.remove('hidden');
            attachPreview.classList.add('flex');
        }
    });

    attachClear.addEventListener('click', () => {
        fileInput.value = '';
        attachPreview.classList.add('hidden');
        attachPreview.classList.remove('flex');
        attachName.textContent = '';
    });

    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';

        if (!typingSentTrue) {
            typingSentTrue = true;
            fetch(typingUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ typing: true }),
            }).catch(() => {});
        }

        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            typingSentTrue = false;
            fetch(typingUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ typing: false }),
            }).catch(() => {});
        }, 1400);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

    function renderReactionButtons(messageId, reactions) {
        const container = document.querySelector(`[data-reactions-for="${messageId}"]`);
        if (!container) return;
        container.innerHTML = (reactions || []).map((r) => `
            <button type="button" class="reaction-btn px-1.5 py-0.5 text-[10px] rounded-full border ${r.mine ? 'border-blue-400/60 bg-blue-600/20' : 'border-white/10 bg-white/5'}" data-message-id="${messageId}" data-emoji="${esc(r.emoji)}">${esc(r.emoji)} ${r.count}</button>
        `).join('');
    }

    function buildAttachmentHtml(msg) {
        if (!msg.attachment_url) return '';
        const isImage = msg.attachment_mime && msg.attachment_mime.startsWith('image/');
        const name = msg.attachment_name || 'Attachment';
        if (isImage) {
            return `<img src="${msg.attachment_url}" alt="${esc(name)}" class="max-w-xs max-h-52 rounded-xl mb-1 object-contain cursor-pointer" onclick="window.open(this.src,'_blank')">`;
        }
        return `<a href="${msg.attachment_url}" target="_blank" download="${esc(name)}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors mb-1 text-xs"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg><span class="truncate max-w-[200px]">${esc(name)}</span></a>`;
    }

    function maybeInsertDateDivider(iso) {
        const date = String(iso).slice(0, 10);
        if (lastDate !== date) {
            const d = document.createElement('div');
            d.className = 'date-divider text-center text-[10px] text-white/35';
            d.setAttribute('data-date', date);
            d.innerHTML = `<span class="px-2 py-1 rounded-md bg-white/5 border border-white/10">${dateLabel(date)}</span>`;
            box.appendChild(d);
            lastDate = date;
        }
    }

    function maybeInsertNewDivider(msg) {
        if (!insertedNewDivider && !msg.mine) {
            const d = document.createElement('div');
            d.className = 'text-center text-[10px] text-blue-300/80 my-2';
            d.innerHTML = '<span class="px-2 py-1 rounded-md bg-blue-600/15 border border-blue-500/30">New messages</span>';
            box.appendChild(d);
            insertedNewDivider = true;
        }
    }

    function appendMessage(msg) {
        const empty = document.getElementById('empty-state');
        if (empty) empty.remove();

        maybeInsertDateDivider(msg.created_at);
        maybeInsertNewDivider(msg);

        const row = document.createElement('div');
        row.className = `message-row flex ${msg.mine ? 'justify-end' : 'justify-start'}`;
        row.setAttribute('data-message-id', msg.id);
        row.setAttribute('data-message-body', msg.body || '');

        const replyHtml = msg.reply_to ? `<div class="mb-1 px-2 py-1 rounded-lg bg-white/6 border border-white/10 text-[11px] text-white/60 truncate">Replying to: ${esc(msg.reply_to.body || 'Attachment')}</div>` : '';
        const avatarHtml = msg.mine
            ? ''
            : (partnerAvatar
                ? `<img src="${partnerAvatar}" alt="Partner" class="w-7 h-7 rounded-lg object-cover border border-white/15 shrink-0 mr-2 mt-1">`
                : `<div class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#0D00A4] to-[#22007C] flex items-center justify-center text-[10px] font-black text-white/90 shrink-0 mr-2 mt-1">${esc(partnerInitials || 'U')}</div>`);
        const editedHtml = msg.edited_at ? '<span>edited</span>' : '';

        row.innerHTML = `${avatarHtml}
            <div class="max-w-[66%] group">
                ${replyHtml}
                <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed ${msg.mine ? 'bg-blue-600 text-white rounded-br-md' : 'bg-white/10 text-white/90 rounded-bl-md'}">
                    ${buildAttachmentHtml(msg)}
                    ${msg.body ? esc(msg.body) : ''}
                </div>
                <div class="mt-1 flex flex-wrap gap-1" data-reactions-for="${msg.id}"></div>
                <div class="flex items-center gap-2 text-[10px] text-white/30 mt-1 ${msg.mine ? 'justify-end' : 'justify-start'}"><span>${formatTime(msg.created_at)}</span>${editedHtml}</div>
                <div class="opacity-0 group-hover:opacity-100 transition-opacity mt-1 flex gap-1 ${msg.mine ? 'justify-end' : 'justify-start'}">
                    <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="reply" data-message-id="${msg.id}">Reply</button>
                    <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="react" data-emoji="👍" data-message-id="${msg.id}">👍</button>
                    <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="react" data-emoji="❤️" data-message-id="${msg.id}">❤️</button>
                    <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="copy" data-message-id="${msg.id}">Copy</button>
                    <button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="forward" data-message-id="${msg.id}">Forward</button>
                    ${msg.mine ? `<button type="button" class="action-btn px-1.5 py-0.5 rounded bg-white/5 hover:bg-white/10 text-[10px]" data-action="edit" data-message-id="${msg.id}">Edit</button><button type="button" class="action-btn px-1.5 py-0.5 rounded bg-red-500/15 hover:bg-red-500/30 text-[10px]" data-action="delete" data-message-id="${msg.id}">Delete</button>` : ''}
                </div>
            </div>`;

        box.appendChild(row);
        renderReactionButtons(msg.id, msg.reactions || []);
        scrollBottom();
    }

    function uploadMessage(formData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', sendUrl, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);

            xhr.upload.onprogress = function (evt) {
                if (!evt.lengthComputable) return;
                const pct = Math.round((evt.loaded / evt.total) * 100);
                sendBtn.textContent = pct + '%';
            };

            xhr.onload = function () {
                sendBtn.textContent = 'Send';
                if (xhr.status >= 200 && xhr.status < 300) {
                    try { resolve(JSON.parse(xhr.responseText)); }
                    catch (e) { reject(e); }
                } else {
                    reject(new Error('Upload failed'));
                }
            };

            xhr.onerror = function () {
                sendBtn.textContent = 'Send';
                reject(new Error('Network error'));
            };

            xhr.send(formData);
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (isSending) return;

        const body = input.value.trim();
        const hasFile = fileInput.files.length > 0;
        if (!body && !hasFile) return;

        isSending = true;
        sendBtn.disabled = true;

        const fd = new FormData();
        fd.append('_token', csrf);
        if (body) fd.append('body', body);
        if (hasFile) fd.append('attachment', fileInput.files[0]);
        if (replyToId && !replyPreview.classList.contains('hidden')) {
            fd.append('reply_to_id', replyToId);
        }

        sendBtn.textContent = hasFile ? '0%' : 'Sending...';

        try {
            const data = await uploadMessage(fd);
            if (data.id) {
                appendMessage(data);
                lastId = Math.max(lastId, data.id);
            }
            input.value = '';
            input.style.height = 'auto';
            attachClear.click();
            clearReply();
        } catch (err) {
            sendBtn.textContent = 'Send';
            console.error(err);
        } finally {
            isSending = false;
            sendBtn.disabled = false;
        }
    });

    box.addEventListener('click', async function (e) {
        const actionBtn = e.target.closest('.action-btn, .reaction-btn');
        if (!actionBtn) return;

        const msgId = actionBtn.dataset.messageId;
        const emoji = actionBtn.dataset.emoji;
        const action = actionBtn.dataset.action || 'react';
        const row = document.querySelector(`.message-row[data-message-id="${msgId}"]`);
        const bodyText = row?.dataset.messageBody || '';

        if (action === 'reply') {
            setReply(msgId, bodyText || 'Attachment');
            return;
        }

        if (action === 'copy') {
            if (bodyText) navigator.clipboard?.writeText(bodyText).catch(() => {});
            return;
        }

        if (action === 'react' || actionBtn.classList.contains('reaction-btn')) {
            const res = await fetch(reactUrlT.replace('__MSG__', msgId), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ emoji: emoji || '👍' }),
            });
            const data = await res.json();
            if (data?.reactions) renderReactionButtons(msgId, data.reactions);
            return;
        }

        if (action === 'edit') {
            const next = prompt('Edit message:', bodyText);
            if (next === null) return;
            const res = await fetch(editUrlT.replace('__MSG__', msgId), {
                method: 'PATCH',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ body: next }),
            });
            if (res.ok) location.reload();
            return;
        }

        if (action === 'delete') {
            if (!confirm('Delete this message?')) return;
            const res = await fetch(deleteUrlT.replace('__MSG__', msgId), {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            if (res.ok) {
                row?.remove();
            }
            return;
        }

        if (action === 'forward') {
            const options = Array.from(document.querySelectorAll('.sidebar-item[data-user-id]'))
                .map((n) => `${n.dataset.userId}:${n.querySelector('span')?.textContent?.trim()}`)
                .join('\n');
            const target = prompt(`Forward to user id:\n${options}`);
            if (!target) return;
            await fetch(forwardUrlT.replace('__MSG__', msgId), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ to_user_id: Number(target) }),
            });
        }
    });

    function renderMedia(files) {
        mediaGrid.innerHTML = '';
        if (!files?.length) {
            mediaGrid.innerHTML = '<div class="text-xs text-white/40">No shared files yet.</div>';
            return;
        }

        files.forEach((f) => {
            const card = document.createElement('a');
            card.href = f.url;
            card.target = '_blank';
            card.className = 'block p-2 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 transition-colors';
            card.innerHTML = f.is_image
                ? `<img src="${f.url}" class="w-full h-28 object-cover rounded-lg mb-2" alt="${esc(f.name)}"><div class="text-[11px] text-white/75 truncate">${esc(f.name)}</div>`
                : `<div class="h-28 rounded-lg mb-2 bg-white/6 border border-white/10 flex items-center justify-center text-2xl">📄</div><div class="text-[11px] text-white/75 truncate">${esc(f.name)}</div>`;
            mediaGrid.appendChild(card);
        });
    }

    tabChat?.addEventListener('click', () => {
        tabChat.className = 'px-2.5 py-1 text-xs rounded-md bg-blue-600 text-white';
        tabMedia.className = 'px-2.5 py-1 text-xs rounded-md text-white/70 hover:text-white hover:bg-white/10';
        box.classList.remove('hidden');
        mediaPanel.classList.add('hidden');
    });

    tabMedia?.addEventListener('click', async () => {
        tabMedia.className = 'px-2.5 py-1 text-xs rounded-md bg-blue-600 text-white';
        tabChat.className = 'px-2.5 py-1 text-xs rounded-md text-white/70 hover:text-white hover:bg-white/10';
        box.classList.add('hidden');
        mediaPanel.classList.remove('hidden');
        renderMedia(sharedFiles);

        try {
            const res = await fetch(mediaUrl, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
            const data = await res.json();
            renderMedia(data.files || []);
        } catch (_) {}
    });

    searchInput?.addEventListener('input', async function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.message-row').forEach((row) => {
            const txt = (row.dataset.messageBody || '').toLowerCase();
            row.style.display = q === '' || txt.includes(q) ? '' : 'none';
        });

        if (q.length < 2) return;
        try {
            await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
        } catch (_) {}
    });

    box.addEventListener('dragover', (e) => {
        e.preventDefault();
        box.classList.add('ring-2', 'ring-blue-500/50');
    });
    box.addEventListener('dragleave', () => box.classList.remove('ring-2', 'ring-blue-500/50'));
    box.addEventListener('drop', (e) => {
        e.preventDefault();
        box.classList.remove('ring-2', 'ring-blue-500/50');
        const f = e.dataTransfer?.files?.[0];
        if (!f) return;
        const dt = new DataTransfer();
        dt.items.add(f);
        fileInput.files = dt.files;
        attachName.textContent = f.name;
        attachPreview.classList.remove('hidden');
        attachPreview.classList.add('flex');
    });

    async function poll() {
        try {
            const res = await fetch(`${pollUrl}?after=${lastId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json();

            if (presenceEl) {
                if (data.partner_online) {
                    presenceEl.textContent = 'Online';
                    presenceEl.className = 'text-xs text-emerald-400/80';
                } else {
                    presenceEl.textContent = 'Away';
                    presenceEl.className = 'text-xs text-white/40';
                }
            }

            typingEl.textContent = data.typing ? '{{ $user->name }} is typing...' : '';

            if (data.messages?.length) {
                data.messages.forEach((m) => {
                    if (!document.querySelector(`[data-message-id="${m.id}"]`)) {
                        appendMessage(m);
                    }
                    lastId = Math.max(lastId, m.id);
                });
            }
        } catch (_) {}
    }

    setInterval(poll, 2500);
})();
</script>
@endsection
