@extends('layouts.dashboard')

@section('dashboard-content')
<div class="p-8 max-w-5xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ $plan->title ?? 'AI Plan #' . $plan->id }}</h1>
        <div class="text-sm text-white/40">{{ $plan->created_at->toDayDateTimeString() }}</div>
    </div>

    <div class="mb-6 p-4 bg-white/5 rounded">
        <h3 class="font-bold">Input</h3>
        <p class="text-sm text-white/60">{{ $plan->input_text }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-4 bg-white/5 rounded">
            <h4 class="font-bold mb-2">Features</h4>
            <ul class="list-disc ml-4 text-sm text-white/60">
                @foreach($plan->result_json['features'] ?? [] as $f)
                    <li>{{ $f }}</li>
                @endforeach
            </ul>
        </div>

        <div class="p-4 bg-white/5 rounded">
            <h4 class="font-bold mb-2">Modules</h4>
            <ul class="list-disc ml-4 text-sm text-white/60">
                @foreach($plan->result_json['modules'] ?? [] as $m)
                    <li>{{ is_array($m) ? json_encode($m) : $m }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mt-6 p-4 bg-white/5 rounded">
        <h4 class="font-bold mb-2">Database Entities</h4>
        <pre class="text-sm text-white/60">{{ json_encode($plan->result_json['database_entities'] ?? [], JSON_PRETTY_PRINT) }}</pre>
    </div>

    <div class="mt-6 p-4 bg-white/5 rounded">
        <h4 class="font-bold mb-2">API Endpoints</h4>
        <pre class="text-sm text-white/60">{{ json_encode($plan->result_json['api_endpoints'] ?? [], JSON_PRETTY_PRINT) }}</pre>
    </div>

    <div class="mt-6 p-4 bg-white/5 rounded">
        <h4 class="font-bold mb-2">Architecture Overview</h4>
        <p class="text-sm text-white/60">{{ $plan->result_json['architecture_overview'] ?? '' }}</p>
    </div>

    <div class="mt-6 p-4 bg-white/5 rounded">
        <h4 class="font-bold mb-2">Improvements</h4>
        <ul class="list-disc ml-4 text-sm text-white/60">
            @foreach($plan->result_json['improvements'] ?? [] as $i)
                <li>{{ $i }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
