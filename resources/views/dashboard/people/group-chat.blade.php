@extends('layouts.dashboard')

@section('dashboard-content')
<div class="flex flex-col h-[calc(100vh-11rem)] p-2">

    <div class="flex items-center justify-between gap-3 mb-4 shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('dashboard.people.inbox') }}" class="p-2 rounded-lg hover:bg-white/10 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-600 to-blue-700 flex items-center justify-center text-xs font-black text-white/90 shrink-0">
                GC
            </div>
            <div class="min-w-0">
                <div class="font-bold truncate">{{ $groupChat->name }}</div>
                <div class="text-xs text-white/40 truncate">
                    {{ $groupChat->board?->name ? 'Project: '.$groupChat->board->name.' · ' : '' }}{{ $members->count() }} members
                </div>
            </div>
        </div>
        <div class="hidden md:flex flex-wrap justify-end gap-1 max-w-[50%]">
            @foreach($members as $member)
                <span class="px-2 py-1 rounded-lg text-xs bg-white/5 border border-white/10 text-white/70">{{ $member->user?->name }}</span>
            @endforeach
        </div>
    </div>

    <div id="chat-messages" class="flex-1 overflow-y-auto glass rounded-2xl border border-white/10 p-4 space-y-3 scroll-smooth">
        @forelse($messages as $msg)
            @php $mine = $msg->sender_id === auth()->id(); @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $msg->id }}">
                <div class="max-w-[70%] group">
                    @unless($mine)
                        <div class="text-[10px] text-white/40 mb-1">{{ $msg->sender?->name ?? 'Member' }}</div>
                    @endunless
                    <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed {{ $mine ? 'bg-blue-600 text-white rounded-br-md' : 'bg-white/10 text-white/90 rounded-bl-md' }}">
                        @if($msg->attachment_path)
                            @if(str_starts_with($msg->attachment_mime ?? '', 'image/'))
                                <img src="{{ Storage::disk('public')->url($msg->attachment_path) }}" alt="{{ $msg->attachment_name }}" class="max-w-xs max-h-48 rounded-xl mb-1 object-contain cursor-pointer" onclick="window.open(this.src,'_blank')">
                            @else
                                <a href="{{ Storage::disk('public')->url($msg->attachment_path) }}" target="_blank" download="{{ $msg->attachment_name }}"
                                   class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors mb-1 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="truncate max-w-[180px]">{{ $msg->attachment_name }}</span>
                                </a>
                            @endif
                        @endif
                        @if($msg->body){{ $msg->body }}@endif
                    </div>
                    <div class="text-[10px] text-white/30 mt-1 {{ $mine ? 'text-right' : 'text-left' }}">{{ $msg->created_at->format('H:i') }}</div>
                </div>
            </div>
        @empty
            <div id="empty-state" class="flex flex-col items-center justify-center h-full text-white/30 gap-2 py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 16V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h11l4 4v-4z"/>
                </svg>
                <p class="text-sm font-medium">No messages yet. Start the project discussion.</p>
            </div>
        @endforelse
    </div>

    <form id="chat-form" class="flex flex-col gap-2 mt-3 shrink-0" enctype="multipart/form-data">
        @csrf
        <div id="attach-preview" class="hidden flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-xs text-white/70">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span id="attach-name" class="truncate flex-1"></span>
            <button type="button" id="attach-clear" class="ml-auto text-white/40 hover:text-red-400 transition-colors">&times;</button>
        </div>
        <div class="flex gap-2">
            <input type="file" id="chat-file" name="attachment" class="hidden"
                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.mp4,.mp3">
            <button type="button" id="attach-btn" title="Attach file"
                    class="px-3 py-2.5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
            </button>
            <textarea
                id="chat-input"
                name="body"
                rows="1"
                placeholder="Write a group message..."
                class="flex-1 resize-none px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/30 focus:outline-none focus:border-blue-500 text-sm leading-relaxed"
                style="max-height:120px;"
            ></textarea>
            <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-xl font-semibold text-sm transition-all shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
(function () {
    const sendUrl  = @json(route('dashboard.people.group.message', $groupChat));
    const pollUrl  = @json(route('dashboard.people.group.poll', $groupChat));
    const form     = document.getElementById('chat-form');
    const input    = document.getElementById('chat-input');
    const box      = document.getElementById('chat-messages');
    const csrf     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const fileInput    = document.getElementById('chat-file');
    const attachBtn    = document.getElementById('attach-btn');
    const attachPreview= document.getElementById('attach-preview');
    const attachName   = document.getElementById('attach-name');
    const attachClear  = document.getElementById('attach-clear');

    let lastId = {{ $messages->max('id') ?? 0 }};

    function scrollBottom() { box.scrollTop = box.scrollHeight; }
    scrollBottom();

    // Attachment picker
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
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

    function formatTime(iso) {
        const d = new Date(iso);
        return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function buildAttachmentHtml(msg) {
        if (!msg.attachment_url) return '';
        const isImage = msg.attachment_mime && msg.attachment_mime.startsWith('image/');
        const name = msg.attachment_name || 'Attachment';
        if (isImage) {
            return `<img src="${msg.attachment_url}" alt="${escapeHtml(name)}" class="max-w-xs max-h-48 rounded-xl mb-1 object-contain cursor-pointer" onclick="window.open(this.src,'_blank')">`;
        }
        return `<a href="${msg.attachment_url}" target="_blank" download="${escapeHtml(name)}"
            class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors mb-1 text-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span class="truncate max-w-[180px]">${escapeHtml(name)}</span>
        </a>`;
    }

    function appendMessage(msg) {
        const empty = document.getElementById('empty-state');
        if (empty) empty.remove();

        const wrapper = document.createElement('div');
        wrapper.className = 'flex ' + (msg.mine ? 'justify-end' : 'justify-start');
        wrapper.setAttribute('data-message-id', msg.id);

        wrapper.innerHTML = `
            <div class="max-w-[70%] group">
                ${msg.mine ? '' : `<div class="text-[10px] text-white/40 mb-1">${escapeHtml(msg.sender_name || 'Member')}</div>`}
                <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed ${msg.mine ? 'bg-blue-600 text-white rounded-br-md' : 'bg-white/10 text-white/90 rounded-bl-md'}">
                    ${buildAttachmentHtml(msg)}
                    ${msg.body ? escapeHtml(msg.body) : ''}
                </div>
                <div class="text-[10px] text-white/30 mt-1 ${msg.mine ? 'text-right' : 'text-left'}">
                    ${formatTime(msg.created_at)}
                </div>
            </div>`;

        box.appendChild(wrapper);
        scrollBottom();
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = input.value.trim();
        const hasFile = fileInput.files.length > 0;
        if (!body && !hasFile) return;

        const fd = new FormData();
        fd.append('_token', csrf);
        if (body) fd.append('body', body);
        if (hasFile) fd.append('attachment', fileInput.files[0]);

        input.value = '';
        input.style.height = 'auto';
        attachClear.click();

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: fd,
            });
            const data = await res.json();
            if (data.id) {
                appendMessage(data);
                lastId = Math.max(lastId, data.id);
            }
        } catch (err) {
            console.error('Send failed', err);
        }
    });

    async function poll() {
        try {
            const res = await fetch(`${pollUrl}?after=${lastId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json();
            if (data.messages && data.messages.length) {
                data.messages.forEach(m => {
                    if (!document.querySelector(`[data-message-id="${m.id}"]`)) {
                        appendMessage(m);
                    }
                    lastId = Math.max(lastId, m.id);
                });
            }
        } catch (_) {}
    }

    setInterval(poll, 3000);
})();
</script>
@endsection
