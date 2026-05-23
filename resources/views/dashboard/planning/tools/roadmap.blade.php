@extends('layouts.dashboard')

@section('dashboard-content')
    <div class="pt-6 px-0">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold">Product Roadmaps — {{ $board->name }}</h1>
            <p class="text-white/60">Visualize project timelines, goals, and strategic roadmap.</p>

            <div class="mt-6 bg-[#02010A] rounded-2xl p-6">
                <p class="text-sm text-white/70">This is a simple roadmap placeholder. You can create milestones and timelines here.</p>
                <p class="mt-4"><a href="{{ route('dashboard.planning.show', $board) }}" class="px-3 py-2 bg-[#0D00A4] rounded-md">Back</a></p>
            </div>
        </div>
    </div>
@endsection
