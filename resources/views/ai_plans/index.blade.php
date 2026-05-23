@extends('layouts.dashboard')

@section('dashboard-content')
<div class="p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">AI Plans</h1>
        <a href="{{ route('ai.plans.create') }}" class="btn-cyber px-4 py-2 bg-indigo-600 text-white rounded">New Plan</a>
    </div>

    <div class="space-y-4">
        @foreach($plans as $p)
            <div class="p-4 bg-white/5 rounded-lg border border-white/5">
                <div class="flex justify-between items-center">
                    <div>
                        <a href="{{ route('ai.plans.show', $p->id) }}" class="font-bold text-white">{{ $p->title ?? 'AI Plan #' . $p->id }}</a>
                        <div class="text-sm text-white/60">{{ Str::limit($p->input_text, 160) }}</div>
                    </div>
                    <div class="text-sm text-white/40">{{ $p->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $plans->links() }}</div>
</div>
@endsection
