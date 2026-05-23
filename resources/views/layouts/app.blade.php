<!DOCTYPE html>
<html lang="en" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Midnight | Software Management') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        /* ── Theme Variables ─────────────────────────────────────── */
        :root, [data-theme="dark"] {
            --bg-body: #02010A;
            --bg-surface: #01020a;
            --bg-glass: rgba(4, 5, 46, 0.5);
            --bg-glass-solid: rgba(4, 5, 46, 0.7);
            --bg-input: rgba(255,255,255,0.05);
            --bg-card: rgba(13, 15, 70, 0.3);
            --bg-card-hover: rgba(13, 15, 70, 0.5);
            --border-glass: rgba(255, 255, 255, 0.1);
            --border-subtle: rgba(255, 255, 255, 0.05);
            --border-input: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --text-faint: rgba(255, 255, 255, 0.3);
            --blob-1: #140152;
            --blob-2: #22007C;
            --accent: #0D00A4;
            --accent-hover: #22007C;
            --shadow-card: 0 30px 60px -12px rgba(0, 0, 0, 0.5);
            --shadow-glow: 0 0 20px rgba(13, 0, 164, 0.4);
            --selection-bg: #0D00A4;
            --scrollbar-thumb: rgba(255,255,255,0.1);
            --scrollbar-track: transparent;
        }

        [data-theme="light"] {
            --bg-body: #F8FAFC;
            --bg-surface: #F1F5F9;
            --bg-glass: rgba(241, 245, 249, 0.82);
            --bg-glass-solid: rgba(241, 245, 249, 0.94);
            --bg-input: #F8FAFC;
            --bg-card: #F1F5F9;
            --bg-card-hover: #E6E9FF;
            --border-glass: rgba(31, 41, 55, 0.10);
            --border-subtle: rgba(31, 41, 55, 0.06);
            --border-input: rgba(31, 41, 55, 0.16);
            --text-primary: #1F2937;
            --text-secondary: rgba(31, 41, 55, 0.82);
            --text-muted: rgba(31, 41, 55, 0.64);
            --text-faint: rgba(31, 41, 55, 0.48);
            --blob-1: rgba(99, 102, 241, 0.10);
            --blob-2: rgba(99, 102, 241, 0.07);
            --accent: #6366F1;
            --accent-hover: #5558E8;
            --shadow-card: 0 10px 24px -8px rgba(31, 41, 55, 0.14);
            --shadow-glow: 0 0 18px rgba(99, 102, 241, 0.20);
            --selection-bg: #E6E9FF;
            --scrollbar-thumb: #C8D2E8;
            --scrollbar-track: transparent;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-body); 
            color: var(--text-primary); 
            transition: all 0.3s ease; 
            letter-spacing: -0.01em; 
        }

        /* ── Glass Base ───────────────────────────────────────────── */
        .glass {
            background: rgba(4, 5, 46, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* ── Diagram Hub Components ──────────────────────────────── */
        .hub-glass { 
            background: var(--bg-glass-solid); 
            backdrop-filter: blur(12px); 
            border: 1px solid var(--border-glass); 
            box-shadow: var(--shadow-card);
            border-radius: 24px;
        }

        .preview-stage {
            background: radial-gradient(circle at center, rgba(13, 0, 164, 0.03) 0%, transparent 70%);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
        }

        #diagram-code-inline {
            background: #0d0f14;
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #9cdcfe;
            font-family: 'JetBrains Mono', monospace;
            line-height: 1.6;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
        }

        .edit-mode-overlay {
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        #diagram-image-preview:hover .edit-mode-overlay { opacity: 1; }

        /* ── Modal Overlay Base ──────────────────────────────────── */
        .modal-blur {
            background: rgba(2, 6, 23, 0.52);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        /* ── Light Mode Fixes ────────────────────────────────────── */
        [data-theme="light"] .text-white { color: var(--text-primary) !important; }
        [data-theme="light"] .text-white\/90, [data-theme="light"] .text-white\/80 { color: var(--text-primary) !important; }
        [data-theme="light"] .text-white\/70, [data-theme="light"] .text-white\/60, [data-theme="light"] .text-white\/30 { color: var(--text-secondary) !important; }
        [data-theme="light"] .text-white\/45 { color: rgba(31, 41, 55, 0.58) !important; }
        [data-theme="light"] .text-white\/35 { color: rgba(31, 41, 55, 0.52) !important; }
        [data-theme="light"] .text-white\/50 { color: rgba(31, 41, 55, 0.64) !important; }
        [data-theme="light"] .text-white\/40 { color: rgba(31, 41, 55, 0.56) !important; }
        [data-theme="light"] .text-white\/25 { color: rgba(31, 41, 55, 0.40) !important; }
        [data-theme="light"] .text-white\/20 { color: rgba(31, 41, 55, 0.30) !important; }
        [data-theme="light"] .text-white\/10 { color: rgba(31, 41, 55, 0.18) !important; }
        [data-theme="light"] .bg-blue-600 { background-color: var(--accent) !important; }
        [data-theme="light"] .bg-white\/5 { background-color: #F1F5F9 !important; border: 1px solid #D8DFED !important; }
        [data-theme="light"] .bg-white\/10 { background-color: #E6E9FF !important; }
        [data-theme="light"] .bg-black\/40 { background-color: #E6E9FF !important; }
        [data-theme="light"] .bg-black\/60 { background-color: rgba(31, 41, 55, 0.08) !important; }
        [data-theme="light"] .bg-black\/70 { background-color: rgba(31, 41, 55, 0.28) !important; }
        [data-theme="light"] .bg-black\/80 { background-color: rgba(31, 41, 55, 0.32) !important; }
        [data-theme="light"] .bg-black\/90 { background-color: rgba(31, 41, 55, 0.35) !important; }
        [data-theme="light"] .bg-black\/95 { background-color: rgba(31, 41, 55, 0.38) !important; }
        [data-theme="light"] .modal-blur {
            background: rgba(31, 41, 55, 0.28) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
        }
        [data-theme="light"] #diagram-modal-inline > div { background-color: #F8FAFC !important; }

        /* ── Light Mode Input Readability (Chat/Search) ─────────── */
        [data-theme="light"] input.bg-white\/5,
        [data-theme="light"] textarea.bg-white\/5,
        [data-theme="light"] select.bg-white\/5,
        [data-theme="light"] input.bg-white\/10,
        [data-theme="light"] textarea.bg-white\/10,
        [data-theme="light"] select.bg-white\/10,
        [data-theme="light"] #sidebar-search,
        [data-theme="light"] #message-search,
        [data-theme="light"] #chat-input {
            color: #1F2937 !important;
            -webkit-text-fill-color: #1F2937 !important;
            caret-color: #6366F1 !important;
        }

        [data-theme="light"] input.bg-white\/5::placeholder,
        [data-theme="light"] textarea.bg-white\/5::placeholder,
        [data-theme="light"] select.bg-white\/5::placeholder,
        [data-theme="light"] input.bg-white\/10::placeholder,
        [data-theme="light"] textarea.bg-white\/10::placeholder,
        [data-theme="light"] select.bg-white\/10::placeholder,
        [data-theme="light"] #sidebar-search::placeholder,
        [data-theme="light"] #message-search::placeholder,
        [data-theme="light"] #chat-input::placeholder {
            color: rgba(31, 41, 55, 0.55) !important;
            opacity: 1 !important;
        }

        /* ── Light Mode Readonly/Disabled Inputs ───────────────── */
        [data-theme="light"] input[readonly],
        [data-theme="light"] input:read-only,
        [data-theme="light"] textarea[readonly],
        [data-theme="light"] textarea:read-only,
        [data-theme="light"] select[readonly] {
            color: #1F2937 !important;
            -webkit-text-fill-color: #1F2937 !important;
            background-color: #E6E9FF !important;
            border-color: #C8D2E8 !important;
            opacity: 1 !important;
        }

        [data-theme="light"] input:disabled,
        [data-theme="light"] textarea:disabled,
        [data-theme="light"] select:disabled {
            color: #4B5563 !important;
            -webkit-text-fill-color: #4B5563 !important;
            background-color: #F8FAFC !important;
            border-color: #D8DFED !important;
            opacity: 1 !important;
        }

        /* ── Light Mode Floating Chat Widget ─────────────────────── */
        [data-theme="light"] #fcw-panel {
            background: #F8FAFC !important;
            border-color: #C8D2E8 !important;
            box-shadow: 0 16px 40px rgba(31, 41, 55, 0.16) !important;
        }
        [data-theme="light"] #fcw-header {
            background: #E6E9FF !important;
            border-bottom-color: #C8D2E8 !important;
        }
        [data-theme="light"] #fcw-title {
            color: #1F2937 !important;
        }
        [data-theme="light"] #fcw-inbox,
        [data-theme="light"] #fcw-chat,
        [data-theme="light"] #fcw-messages {
            background-color: #FFFFFF !important;
        }
        [data-theme="light"] #fcw-form {
            background-color: #F8FAFC !important;
            border-top-color: #C8D2E8 !important;
        }
        [data-theme="light"] #fcw-input {
            background-color: #F1F5F9 !important;
            border-color: #C8D2E8 !important;
            color: #1F2937 !important;
        }
        [data-theme="light"] #fcw-input::placeholder {
            color: rgba(31, 41, 55, 0.55) !important;
        }
        [data-theme="light"] #fcw-inbox-list button:hover {
            background-color: #E6E9FF !important;
        }

        /* ── Light Mode Borders ──────────────────────────────────── */
        [data-theme="light"] .glass {
            background: #F1F5F9 !important;
            backdrop-filter: none !important;
            border: 1px solid #D8DFED !important;
            box-shadow: 0 1px 3px rgba(31, 41, 55, 0.08), 0 1px 2px rgba(31, 41, 55, 0.05) !important;
        }
        [data-theme="light"] .border-white\/5 { border-color: #DDE3EF !important; }
        [data-theme="light"] .border-white\/10 { border-color: #C8D2E8 !important; }
        [data-theme="light"] .border-white\/20 { border-color: #C8D2E8 !important; }
        [data-theme="light"] .hover\:border-blue-500\/40:hover { border-color: var(--accent) !important; }
        [data-theme="light"] .hover\:border-yellow-500\/40:hover { border-color: #EAB308 !important; }
        [data-theme="light"] .hover\:border-green-500\/40:hover { border-color: #22C55E !important; }
        [data-theme="light"] .hover\:border-white\/20:hover { border-color: #94A3B8 !important; }
        [data-theme="light"] .hover\:bg-white\/5:hover { background-color: #F1F5F9 !important; }
        [data-theme="light"] .hover\:bg-white\/10:hover { background-color: #E6E9FF !important; }
        [data-theme="light"] .border-dashed { border-color: #C8D2E8 !important; }
        [data-theme="light"] .shadow-lg { box-shadow: 0 4px 12px rgba(31, 41, 55, 0.10) !important; }
        [data-theme="light"] .shadow-2xl { box-shadow: 0 8px 24px rgba(31, 41, 55, 0.12) !important; }

        /* ── Light Mode Backgrounds ──────────────────────────────── */
        [data-theme="light"] .bg-\[\#02010A\], [data-theme="light"] .bg-\[\#01020a\] { background-color: #F8FAFC !important; }
        [data-theme="light"] .bg-\[\#0D00A4\] { background-color: var(--accent) !important; }
        [data-theme="light"] .bg-\[\#140152\] { background-color: rgba(99, 102, 241, 0.10) !important; }
        [data-theme="light"] .bg-\[\#22007C\] { background-color: rgba(99, 102, 241, 0.07) !important; }
        [data-theme="light"] .bg-blue-500\/10 { background-color: rgba(99, 102, 241, 0.10) !important; }
        [data-theme="light"] .bg-yellow-500\/10 { background-color: rgba(234, 179, 8, 0.08) !important; }
        [data-theme="light"] .bg-green-500\/10 { background-color: rgba(34, 197, 94, 0.08) !important; }
        [data-theme="light"] .bg-blue-400\/10 { background-color: rgba(99, 102, 241, 0.10) !important; }
        [data-theme="light"] .bg-blue-500\/20 { background-color: rgba(99, 102, 241, 0.16) !important; }
        [data-theme="light"] .bg-gray-600 { background-color: #CBD5E1 !important; }
        [data-theme="light"] .bg-gray-500 { background-color: #94A3B8 !important; }
        [data-theme="light"] .border-\[\#02010A\] { border-color: #F1F5F9 !important; }

        /* ── Light Mode Accent Contrast ─────────────────────────── */
        [data-theme="light"] .text-blue-400 { color: #6366F1 !important; }
        [data-theme="light"] .text-blue-300 { color: #5558E8 !important; }
        [data-theme="light"] .text-violet-300 { color: #6D28D9 !important; }
        [data-theme="light"] .text-emerald-300 { color: #047857 !important; }
        [data-theme="light"] .text-amber-300 { color: #B45309 !important; }
        [data-theme="light"] .text-rose-300 { color: #BE123C !important; }
        [data-theme="light"] .text-cyan-300 { color: #0E7490 !important; }

        [data-theme="light"] .bg-blue-600\/20 { background-color: #DBEAFE !important; }
        [data-theme="light"] .bg-violet-600\/20 { background-color: #EDE9FE !important; }
        [data-theme="light"] .bg-emerald-600\/20 { background-color: #D1FAE5 !important; }
        [data-theme="light"] .bg-amber-600\/20 { background-color: #FEF3C7 !important; }
        [data-theme="light"] .bg-rose-600\/20 { background-color: #FFE4E6 !important; }
        [data-theme="light"] .bg-cyan-600\/20 { background-color: #CFFAFE !important; }

        [data-theme="light"] .border-blue-500\/30 { border-color: #93C5FD !important; }
        [data-theme="light"] .border-violet-500\/30 { border-color: #C4B5FD !important; }
        [data-theme="light"] .border-emerald-500\/30 { border-color: #86EFAC !important; }
        [data-theme="light"] .border-amber-500\/30 { border-color: #FCD34D !important; }
        [data-theme="light"] .border-rose-500\/30 { border-color: #FDA4AF !important; }
        [data-theme="light"] .border-cyan-500\/30 { border-color: #67E8F9 !important; }

        /* ── Light Mode — Open Planning button ───────────────────── */
        [data-theme="light"] a.bg-white { background-color: #FFFFFF !important; border: 1px solid #E2E8F0 !important; }

        /* ── Animations ──────────────────────────────────────────── */
        @keyframes slideUpFade { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .hub-grid > div { animation: slideUpFade 0.45s ease forwards; opacity: 0; }
        
        /* ── Theme Toggle ────────────────────────────────────────── */
        .theme-toggle { position: relative; width: 52px; height: 28px; border-radius: 14px; border: 1px solid var(--border-glass); background: var(--bg-glass); cursor: pointer; }
        .theme-toggle .toggle-thumb { position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: var(--accent); transition: transform 0.3s ease; display: flex; align-items: center; justify-content: center; }
        [data-theme="light"] .theme-toggle .toggle-thumb { transform: translateX(24px); background: #f59e0b; }

        /* ── Responsive Design ──────────────────────────────────── */
        /* Mobile First - Base (xs/mobile): 320px+ */
        html { scroll-behavior: smooth; }
        
        * { 
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Responsive Typography ──────────────────────────────── */
        @media (max-width: 640px) {
            body { font-size: 14px; }
            h1 { font-size: 1.75rem; line-height: 1.2; }
            h2 { font-size: 1.5rem; }
            h3 { font-size: 1.25rem; }
        }

        @media (min-width: 641px) and (max-width: 1024px) {
            body { font-size: 15px; }
            h1 { font-size: 2rem; }
            h2 { font-size: 1.75rem; }
            h3 { font-size: 1.5rem; }
        }

        @media (min-width: 1025px) {
            h1 { font-size: 2.25rem; }
            h2 { font-size: 2rem; }
            h3 { font-size: 1.75rem; }
        }

        /* ── Responsive Spacing ─────────────────────────────────── */
        @media (max-width: 640px) {
            .space-y-8 { gap: 1.5rem !important; }
            .p-6 { padding: 1rem !important; }
            .gap-6 { gap: 1rem !important; }
            .gap-8 { gap: 1.5rem !important; }
        }

        /* ── Responsive Containers ──────────────────────────────── */
        @media (max-width: 640px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        @media (min-width: 641px) and (max-width: 1024px) {
            .container {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }

        /* ── Touch-Friendly Buttons ──────────────────────────────– */
        @media (max-width: 768px) {
            button, a[role="button"], .btn {
                min-height: 44px;
                min-width: 44px;
            }
            
            input, textarea, select {
                min-height: 44px;
                font-size: 16px;
            }
        }

        /* ── Responsive Grid Adjustments ────────────────────────── */
        @media (max-width: 640px) {
            .grid-cols-1,
            .grid-cols-2,
            .grid-cols-3,
            .grid-cols-4,
            .grid-cols-6 {
                grid-template-columns: 1fr !important;
            }
        }

        @media (min-width: 641px) and (max-width: 1024px) {
            .grid-cols-3,
            .grid-cols-4,
            .grid-cols-6 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        /* ── Responsive Flex ────────────────────────────────────── */
        @media (max-width: 640px) {
            .flex-row { flex-direction: column; }
            .items-center { align-items: stretch; }
        }

        /* ── Responsive Modal ────────────────────────────────────– */
        @media (max-width: 640px) {
            .modal-content {
                max-height: 90vh;
                max-width: 95vw;
                margin: auto;
            }
        }

        /* ── Responsive Images ──────────────────────────────────── */
        img, video {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* ── Prevent Horizontal Scroll ────────────────────────── */
        @media (max-width: 768px) {
            * {
                max-width: 100vw;
            }
        }

        /* ── Force Mobile Layout ────────────────────────────────– */
        @media screen and (max-width: 768px) {
            /* Hide sidebar on mobile */
            aside {
                display: none !important;
            }

            /* Main content takes full width */
            main {
                grid-column: 1 / -1 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* Grid should be single column on mobile */
            .grid-cols-12 {
                grid-template-columns: 1fr !important;
            }

            .grid-cols-3,
            .grid-cols-1.md\:grid-cols-3 {
                grid-template-columns: 1fr !important;
            }

            /* Override all padding */
            .pt-20 {
                padding-top: 1rem !important;
            }

            .px-6 {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }

            .max-w-7xl {
                max-width: 100% !important;
                padding: 0 !important;
            }

            /* Reduce margins and gaps */
            .gap-6 {
                gap: 1rem !important;
            }

            .gap-4 {
                gap: 0.75rem !important;
            }

            /* Text sizing */
            h1 {
                font-size: 1.5rem !important;
            }

            h2 {
                font-size: 1.25rem !important;
            }

            /* Make buttons full width on mobile where needed */
            .flex.gap-3 {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }

            .flex.gap-3 > a {
                width: 100% !important;
                justify-content: center !important;
            }

            /* Glass cards should take full width */
            .glass {
                width: 100% !important;
            }

            /* Fix sticky positioning */
            .sticky {
                position: static !important;
            }

            /* Mobile header styling */
            .md\:hidden {
                display: block !important;
            }

            /* Dashboard content spacing */
            [class*="space-y"] {
                gap: 1rem !important;
            }

            /* Mobile grid adjustments */
            .grid {
                grid-template-columns: 1fr !important;
            }

            /* Reduce padding inside cards on mobile */
            .p-5 {
                padding: 1rem !important;
            }

            .p-4 {
                padding: 0.75rem !important;
            }

            .p-6 {
                padding: 1rem !important;
            }

            /* Flex direction for mobile */
            .flex.flex-col.md\:flex-row {
                flex-direction: column !important;
            }

            /* Ensure content is readable */
            body {
                font-size: 14px !important;
            }
        }
    </style>
    <script>
        (function() {
            var theme = localStorage.getItem('gestdev-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    @stack('styles')
</head>
<body class="selection:bg-indigo-500/30 overflow-x-hidden">

    @yield('content')

    <script>
        function toggleTheme() {
            var html = document.documentElement;
            var current = html.getAttribute('data-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('gestdev-theme', next);
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: next } }));
        }
    </script>

    @yield('scripts')
    @stack('modals')
</body>
</html>