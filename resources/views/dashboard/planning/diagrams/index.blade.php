@extends('layouts.dashboard')

@section('dashboard-content')
<style>
    :root {
        --lm-bg: #F8FAFC;
        --lm-surface: #F1F5F9;
        --lm-primary: #6366F1;
        --lm-border: rgba(31,41,55,0.10);
        --lm-text: #1F2937;
        --lm-muted: rgba(31,41,55,0.50);
    }

    /* Unified Hub styling */
    #diagram-lightbox { transition: all 0.25s ease; backdrop-filter: blur(8px); }
    #diagram-lightbox.hidden { opacity: 0; pointer-events: none; }
    #diagram-lightbox img { transform: scale(0.98); transition: transform 0.25s ease; }
    #diagram-lightbox.active img { transform: scale(1); }

    .hub-grid > div { animation: slideUpFade 0.45s ease forwards; opacity: 0; }
    @keyframes slideUpFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .hub-glass { background: linear-gradient(180deg, rgba(7,10,30,0.6), rgba(6,8,25,0.6)); border-radius: 14px; border: 1px solid rgba(255,255,255,0.04); }
    .diagram-card { padding: 1rem; }
    .diagram-card:hover { box-shadow: 0 8px 30px -12px rgba(0,0,0,0.7); transform: translateY(-6px); }
    .diagram-image-wrapper, #diagram-image-preview-img { border-radius: 12px; }
    .diagram-image-wrapper { height: 260px; }
    .diagram-title { font-size: 1.05rem; font-weight: 800; letter-spacing: -0.01em; text-transform: none; }
    .btn-hub { font-weight:700; font-size:0.85rem; padding:0.5rem 0.9rem; border-radius:10px; }
    .btn-hub.bg-primary, .btn-hub.bg-blue-600 { background: linear-gradient(180deg,#2563eb,#1d4ed8); box-shadow: 0 6px 20px rgba(29,78,216,0.18); }
    #diagram-code-preview { background: #0f1720; border-radius: 12px; max-height: 520px; overflow:auto; padding: 1rem; color: #d1d9de; }

    /* ─── PAGE LOAD ─── */
    .diagrams-page {
        opacity: 0;
        transition: opacity 260ms ease;
    }
    .diagrams-page.is-ready { opacity: 1; }

    /* ─── LIGHT MODE ─── */
    [data-theme="light"] .diagrams-page {
        background: var(--lm-bg) !important;
        color: var(--lm-text);
    }
    [data-theme="light"] .hub-glass {
        background: #ffffff !important;
        border-color: var(--lm-border) !important;
        box-shadow: 0 8px 24px -12px rgba(31,41,55,0.14) !important;
    }
    [data-theme="light"] .diagram-card:hover { box-shadow: 0 8px 30px -12px rgba(31,41,55,0.18) !important; }
    [data-theme="light"] .text-white { color: var(--lm-text) !important; }
    [data-theme="light"] .text-white\/90 { color: rgba(31,41,55,0.90) !important; }
    [data-theme="light"] .text-white\/50 { color: var(--lm-muted) !important; }
    [data-theme="light"] .text-white\/40 { color: rgba(31,41,55,0.50) !important; }
    [data-theme="light"] .text-white\/30 { color: rgba(31,41,55,0.38) !important; }
    [data-theme="light"] .text-white\/20 { color: rgba(31,41,55,0.28) !important; }
    [data-theme="light"] .text-white\/10 { color: rgba(31,41,55,0.14) !important; }
    [data-theme="light"] .border-white\/5 { border-color: var(--lm-border) !important; }
    [data-theme="light"] .border-white\/10 { border-color: rgba(31,41,55,0.12) !important; }
    [data-theme="light"] .bg-white\/5 { background: var(--lm-surface) !important; }
    [data-theme="light"] .diagram-card .bg-black\/60 { background: rgba(31,41,55,0.07) !important; color: #6366F1 !important; border-color: var(--lm-border) !important; }
    [data-theme="light"] .border-dashed { border-color: var(--lm-border) !important; }
    [data-theme="light"] .diagram-image-wrapper .text-white\/10 { color: rgba(31,41,55,0.10) !important; }
    [data-theme="light"] button.diagram-edit { color: rgba(31,41,55,0.45) !important; }
    [data-theme="light"] button.diagram-edit:hover { color: var(--lm-text) !important; background: rgba(31,41,55,0.06) !important; }
    [data-theme="light"] button.diagram-delete { color: rgba(239,68,68,0.38) !important; }
    [data-theme="light"] button.diagram-delete:hover { color: #ef4444 !important; }
    [data-theme="light"] #diagram-modal-inline > div { background: #ffffff !important; border-color: var(--lm-border) !important; }
    /* VS Code-style editor sub-elements */
    [data-theme="light"] #diagram-modal-inline input,
    [data-theme="light"] #diagram-modal-inline textarea { background: transparent !important; border-color: var(--lm-border) !important; color: var(--lm-text) !important; }
    [data-theme="light"] #diagram-modal-inline input::placeholder,
    [data-theme="light"] #diagram-modal-inline textarea::placeholder { color: rgba(31,41,55,0.35) !important; }
    [data-theme="light"] #diagram-modal-inline .bg-black\/40 { background: var(--lm-surface) !important; border-color: var(--lm-border) !important; }
    [data-theme="light"] #diagram-modal-inline .text-white\/20,
    [data-theme="light"] #diagram-modal-inline .text-white\/30,
    [data-theme="light"] #diagram-modal-inline .text-white\/40 { color: rgba(31,41,55,0.45) !important; }
    [data-theme="light"] #diagram-modal-inline .text-white/80 { color: rgba(31,41,55,0.80) !important; }
    [data-theme="light"] #diagram-modal-inline .text-white { color: var(--lm-text) !important; }
    [data-theme="light"] #diagram-modal-inline .border-white/10 { border-color: var(--lm-border) !important; }
    [data-theme="light"] #diagram-modal-inline label[for="diagram-pdf-inline"] { background: #f1f5f9 !important; color: #1f2937 !important; border: 1px solid rgba(31, 41, 55, 0.15) !important; transition: all 0.2s ease !important; }
    [data-theme="light"] #diagram-modal-inline label[for="diagram-pdf-inline"]:hover { background: #e2e8f0 !important; }
    [data-theme="light"] #diagram-modal-inline #diagram-pdf-open-btn { background: linear-gradient(to right, #2563eb, #1d4ed8) !important; color: #ffffff !important; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important; border: none !important; transition: all 0.2s ease !important; }
    [data-theme="light"] #diagram-modal-inline #diagram-pdf-open-btn:hover { background: linear-gradient(to right, #1d4ed8, #1e40af) !important; }
    [data-theme="light"] #diagram-modal-inline #diagram-save-inline { color: #ffffff !important; }
    [data-theme="light"] #diagram-code-preview { background: #f3f4f6 !important; color: #1F2937 !important; }
    [data-theme="light"] #ai-suggest-modal > div { background: #ffffff !important; border-color: var(--lm-border) !important; }
    [data-theme="light"] #ai-prompt { background: #ffffff !important; border-color: var(--lm-border) !important; color: var(--lm-text) !important; }
    [data-theme="light"] #ai-prompt::placeholder { color: rgba(31,41,55,0.35) !important; }
    [data-theme="light"] #ai-chat-messages .bg-blue-600\/20 { background: rgba(99,102,241,0.08) !important; border-color: rgba(99,102,241,0.18) !important; }
    [data-theme="light"] #ai-chat-messages .text-blue-200 { color: #3730a3 !important; }
    [data-theme="light"] #ai-chat-messages .bg-white\/5 { background: var(--lm-surface) !important; border-color: var(--lm-border) !important; }
    [data-theme="light"] #diagram-create-inline { background: var(--lm-text) !important; color: #ffffff !important; }
    [data-theme="light"] #diagram-create-inline:hover { background: #3b82f6 !important; }
    [data-theme="light"] a.btn-hub.bg-gray-800 { background: var(--lm-surface) !important; color: var(--lm-text) !important; border-color: var(--lm-border) !important; }
    [data-theme="light"] a.btn-hub.bg-gray-800:hover { background: #e2e8f0 !important; }
    [data-theme="light"] #ai-chat-messages::-webkit-scrollbar { width: 4px; }
    [data-theme="light"] #ai-chat-messages::-webkit-scrollbar-thumb { background: rgba(31,41,55,0.18); border-radius: 10px; }

    /* ─── AI TYPE SELECTOR BUTTONS ─── */
    .ai-type-btn {
        color: rgba(255,255,255,0.38);
        background: transparent;
        border: 1px solid transparent;
    }
    .ai-type-btn:hover {
        color: rgba(255,255,255,0.75);
        background: rgba(255,255,255,0.05);
    }
    .ai-type-btn.active-type {
        color: #a5b4fc;
        background: rgba(99,102,241,0.18);
        border-color: rgba(99,102,241,0.28);
    }

    /* ─── AI MODAL LIGHT MODE ─── */
    [data-theme="light"] #ai-suggest-modal > div {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.10) !important;
    }
    [data-theme="light"] #ai-suggest-modal [style*="rgba(5,7,20"] { background: rgba(248,250,252,0.95) !important; }
    [data-theme="light"] #ai-suggest-modal [style*="rgba(99,102,241,0.08)"] { background: rgba(99,102,241,0.05) !important; }
    [data-theme="light"] .ai-type-btn { color: rgba(31,41,55,0.45) !important; }
    [data-theme="light"] .ai-type-btn:hover { color: #1F2937 !important; background: rgba(31,41,55,0.05) !important; }
    [data-theme="light"] .ai-type-btn.active-type { color: #4F46E5 !important; background: rgba(99,102,241,0.10) !important; border-color: rgba(99,102,241,0.22) !important; }
    [data-theme="light"] #ai-prompt {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.14) !important;
        color: #1F2937 !important;
    }
    [data-theme="light"] #ai-prompt::placeholder { color: rgba(31,41,55,0.35) !important; }
    [data-theme="light"] #ai-active-type-badge { color: #4F46E5 !important; background: rgba(99,102,241,0.10) !important; border-color: rgba(99,102,241,0.22) !important; }
    [data-theme="light"] #ai-quick-prompts button { color: rgba(31,41,55,0.50) !important; }
    [data-theme="light"] #ai-quick-prompts button:hover { color: #1F2937 !important; background: rgba(31,41,55,0.05) !important; }

    /* ─── User message bubble: visible in light mode ─── */
    [data-theme="light"] #ai-chat-messages .flex.justify-end > div {
        background: #C7D2FE !important;
        border-color: #818CF8 !important;
    }
    [data-theme="light"] #ai-chat-messages .flex.justify-end p {
        color: #3730a3 !important;
    }

    /* ─── Chat area right panel: darker in light mode ─── */
    [data-theme="light"] #ai-suggest-modal .flex-1.flex.flex-col {
        background: #EEF2F7 !important;
    }
    [data-theme="light"] #ai-chat-messages {
        background: transparent !important;
    }
    [data-theme="light"] #ai-suggest-modal [style*="rgba(255,255,255,0.05)"],
    [data-theme="light"] #ai-suggest-modal textarea#ai-prompt {
        background: #ffffff !important;
        border-color: rgba(31,41,55,0.14) !important;
    }
    [data-theme="light"] #ai-suggest-modal .border-t {
        border-color: rgba(31,41,55,0.10) !important;
        background: #EEF2F7 !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .diagrams-page { transition: none !important; }
        .hub-grid > div { animation: none !important; opacity: 1; }
    }
</style>
    <div id="diagrams-page" class="diagrams-page pt-8 px-6 pb-20">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-black text-white">Diagram Hub — {{ $board->name }}</h1>
                    <p class="text-white/50 mt-1">Manage diagrams attached to this board.</p>
                </div>
                {{-- Top-right control buttons intentionally removed per UI request --}}
            </div>

            {{-- Debug banner and diagnostics removed; cleaned view --}}

            @include('dashboard.planning.diagrams._hub', ['board' => $board, 'diagrams' => $diagrams, 'showHeader' => false, 'showControls' => true])
        </div>
    </div>
@endsection
