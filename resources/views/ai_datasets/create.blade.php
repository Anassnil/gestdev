@extends('layouts.dashboard')

@section('dashboard-content')
<div class="pt-6 sm:pt-12 px-2 sm:px-6 pb-20">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white">Upload Dataset</h1>
                <p class="text-white/60 text-sm md:text-base">Add a new training dataset</p>
            </div>
            <a href="{{ route('ai.datasets.index') }}" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all">
                Back
            </a>
        </div>

        <!-- Upload Form -->
        <form method="POST" action="{{ route('ai.datasets.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Dataset Name -->
            <div>
                <label for="name" class="block text-white font-semibold mb-2">Dataset Name *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    placeholder="e.g., Customer Purchase History" 
                    class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 outline-none focus:border-blue-500/40 transition-all"
                    required
                >
                @error('name')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Dataset Type -->
            <div>
                <label for="type" class="block text-white font-semibold mb-2">Dataset Type *</label>
                <select 
                    id="type" 
                    name="type" 
                    class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white outline-none focus:border-blue-500/40 transition-all"
                    required
                >
                    <option value="" class="bg-[#01020a]">-- Select Type --</option>
                    <option value="training" class="bg-[#01020a]">Training Data</option>
                    <option value="validation" class="bg-[#01020a]">Validation Data</option>
                    <option value="test" class="bg-[#01020a]">Test Data</option>
                    <option value="custom" class="bg-[#01020a]">Custom</option>
                </select>
                @error('type')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-white font-semibold mb-2">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="4"
                    placeholder="Describe your dataset (optional)" 
                    class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 outline-none focus:border-blue-500/40 transition-all resize-none"
                ></textarea>
                @error('description')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- File Upload -->
            <div>
                <label for="file" class="block text-white font-semibold mb-2">Upload File * (Max 100MB)</label>
                <div class="relative">
                    <input 
                        type="file" 
                        id="file" 
                        name="file" 
                        accept=".csv,.json,.parquet,.xlsx"
                        class="hidden"
                        required
                        onchange="updateFileName(this)"
                    >
                    <label for="file" class="block px-4 py-6 rounded-xl border-2 border-dashed border-white/20 hover:border-blue-500/40 cursor-pointer text-center transition-all">
                        <div id="upload-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto mb-2 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-white font-semibold">Drag and drop or click to upload</p>
                            <p class="text-white/60 text-sm">CSV, JSON, Parquet, or Excel</p>
                        </div>
                        <div id="file-name" class="hidden text-green-400 font-semibold"></div>
                    </label>
                </div>
                @error('file')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Box -->
            <div class="glass rounded-2xl p-4 border border-white/10">
                <h3 class="text-white font-semibold mb-2">Supported Formats</h3>
                <ul class="text-white/70 text-sm space-y-1">
                    <li>• <strong>CSV</strong> - Comma-separated values (recommended)</li>
                    <li>• <strong>JSON</strong> - JSON format</li>
                    <li>• <strong>Parquet</strong> - Apache Parquet format</li>
                    <li>• <strong>Excel</strong> - .xlsx files</li>
                </ul>
            </div>

            <!-- Submit -->
            <div class="flex gap-3">
                <button 
                    type="submit" 
                    class="flex-1 px-6 py-3 bg-blue-600 rounded-xl font-bold text-white hover:bg-blue-700 transition-all"
                >
                    Upload Dataset
                </button>
                <a 
                    href="{{ route('ai.datasets.index') }}" 
                    class="flex-1 px-6 py-3 bg-white/5 border border-white/10 rounded-xl font-bold text-white hover:bg-white/10 transition-all text-center"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function updateFileName(input) {
        const uploadText = document.getElementById('upload-text');
        const fileName = document.getElementById('file-name');
        
        if (input.files && input.files[0]) {
            uploadText.classList.add('hidden');
            fileName.textContent = '✓ ' + input.files[0].name;
            fileName.classList.remove('hidden');
        } else {
            uploadText.classList.remove('hidden');
            fileName.classList.add('hidden');
        }
    }

    // Drag and drop support
    const fileInput = document.getElementById('file');
    const dropZone = fileInput.parentElement;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('border-blue-500/40');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('border-blue-500/40');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        fileInput.files = e.dataTransfer.files;
        updateFileName(fileInput);
    });
</script>
@endsection
