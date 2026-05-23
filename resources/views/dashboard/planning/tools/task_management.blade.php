@extends('layouts.dashboard')

@section('dashboard-content')
    <div class="p-12">
        <div class="max-w-3xl mx-auto text-white/90 bg-black/40 rounded-2xl p-8">
            <h1 class="text-3xl font-bold mb-4">Task Matrix removed</h1>
            <p class="mb-6">The Task Matrix section has been removed. Use the standard board views to manage tasks.</p>
            <a href="{{ route('dashboard.planning.show', $board) }}" class="inline-block px-5 py-3 bg-indigo-600 rounded-lg text-white font-bold">Return to Board</a>
        </div>
    </div>
@endsection