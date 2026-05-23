@extends('layouts.dashboard')

@section('dashboard-content')
<div class="p-8 max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">New AI Plan</h1>

    <form id="ai-plan-form" method="POST" action="{{ route('ai.plans.store') }}">
        @csrf
        <div class="mb-4">
            <label class="label-cyber block mb-2">Title</label>
            <input id="title" name="title" class="w-full px-4 py-2 rounded bg-black/40 border border-white/10 text-white" placeholder="Short title (optional)">
        </div>

        <div class="mb-4">
            <label class="label-cyber block mb-2">Project Idea</label>
            <textarea id="idea" name="idea" rows="8" class="w-full px-4 py-3 rounded bg-black/40 border border-white/10 text-white" placeholder="Describe the project idea..." required></textarea>
        </div>

        <div id="ai-status" class="mb-4 text-sm"></div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('ai.plans.index') }}" class="btn-cyber px-4 py-2 text-white/60">Cancel</a>
            <button id="ai-submit" type="submit" class="btn-cyber px-6 py-2 bg-amber-500 text-black rounded">Analyze</button>
        </div>
    </form>

    <script>
    (function(){
        const form = document.getElementById('ai-plan-form');
        const submit = document.getElementById('ai-submit');
        const status = document.getElementById('ai-status');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            status.innerHTML = '';
            submit.disabled = true;
            const originalText = submit.innerHTML;
            submit.innerHTML = 'Analyzing...';

            const payload = {
                title: document.getElementById('title').value,
                idea: document.getElementById('idea').value
            };

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                if(!res.ok) throw new Error('Server error');
                const data = await res.json();
                if(data.ok){
                    if(data.parsed){
                        // structured JSON parsed — redirect to show
                        window.location = '/ai/plans/' + data.plan_id;
                    } else {
                        status.innerHTML = '<div class="p-3 rounded bg-yellow-900/30 text-yellow-300">Warning: AI output could not be parsed into fully structured JSON. The textual architecture overview was saved. <a class="underline" href="/ai/plans/' + data.plan_id + '">Review plan</a></div>';
                        // show link to review
                    }
                } else {
                    throw new Error('AI processing failed');
                }
            } catch(err){
                status.innerHTML = '<div class="p-3 rounded bg-red-900/30 text-red-300">Error: ' + (err.message || 'Request failed') + '</div>';
            } finally {
                submit.disabled = false;
                submit.innerHTML = originalText;
            }
        });
    })();
    </script>
</div>
@endsection
