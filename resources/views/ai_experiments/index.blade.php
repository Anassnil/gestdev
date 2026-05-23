@extends('layouts.dashboard')
@section('dashboard-content')
<div class="pt-12 px-6 pb-20">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-black text-white">Experiments</h1>
                <p class="text-white/40 mt-1">Link models and datasets to run experiments.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ai.experiments.multi_compare') }}"
                   class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-sm text-white/70 whitespace-nowrap">
                    ⚖ Multi-Compare
                </a>
                <form method="POST" action="{{ route('ai.experiments.store') }}" class="flex gap-3">
                    @csrf
                    <select name="ai_model_id" class="px-3 py-2 rounded-xl bg-[#02010A] border border-white/10 text-white">
                        @foreach(App\Models\AIModel::all() as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                    <select name="dataset_id" class="px-3 py-2 rounded-xl bg-[#02010A] border border-white/10 text-white">
                        @foreach(App\Models\Dataset::all() as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 bg-[#0D00A4] rounded-xl font-bold">Create</button>
                </form>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($experiments as $e)
                <a href="{{ route('ai.experiments.show', $e) }}" class="glass p-4 rounded-2xl flex justify-between items-center hover:shadow-lg transition">
                    <div>
                        <div class="font-bold text-white">Experiment #{{ $e->id }} — {{ $e->model->name }}</div>
                        <div class="text-sm text-white/40">Dataset: {{ $e->dataset->name }}</div>
                    </div>
                    <div class="text-sm text-white/30">{{ $e->status }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
