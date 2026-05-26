@extends('layouts.dashboard')

@section('dashboard-content')
    @include('dashboard.planning._permission')
    <div class="pt-6 px-0">
        <div class="max-w-3xl mx-auto">
            @unless($BOARD_CAN_EDIT)
                <div class="p-12 rounded-2xl bg-black/40 text-center text-white/80">
                    <h2 class="text-xl font-bold mb-3">Access Denied</h2>
                    <p class="mb-6">You don't have permission to create roadmaps on this board.</p>
                    <a href="{{ route('dashboard.planning.roadmaps.index', $board) }}" class="px-5 py-3 bg-indigo-600 rounded-lg text-white font-bold">Return</a>
                </div>
            @else
            <h1 class="text-2xl font-bold">New Roadmap — {{ $board->name }}</h1>
            <p class="text-white/60">Create a new product roadmap.</p>

            <form method="POST" action="{{ route('dashboard.planning.roadmaps.store', $board) }}" class="mt-6 bg-[#02010A] p-6 rounded-2xl">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Title</label>
                    <input name="title" required class="w-full px-3 py-2 rounded-md bg-[#0B0A12] border border-white/5">
                </div>
                <div class="mt-3">
                    <label class="block text-sm mb-1">Description</label>
                    <textarea name="description" rows="4" class="w-full px-3 py-2 rounded-md bg-[#0B0A12] border border-white/5"></textarea>
                </div>
                <div class="mt-4 flex items-center">
                    <button class="px-4 py-2 bg-[#0D00A4] rounded-md">Create</button>
                    <a href="{{ route('dashboard.planning.roadmaps.index', $board) }}" class="ml-2 px-3 py-2 bg-white/5 rounded-md">Cancel</a>
                </div>
            </form>
            @endunless
        </div>
    </div>
@endsection
