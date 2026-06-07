<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained('apis')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('api_environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained('apis')->cascadeOnDelete();
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->timestamps();
        });

        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained('apis')->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained('api_collections')->nullOnDelete();
            $table->string('name');
            $table->string('path');
            $table->enum('method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->default('GET');
            $table->text('description')->nullable();
            $table->string('version')->nullable();
            $table->enum('status', ['draft', 'active', 'deprecated'])->default('active');
            $table->timestamps();
            $table->index(['api_id', 'method']);
        });

        Schema::create('api_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained('apis')->cascadeOnDelete();
            $table->string('version');
            $table->date('release_date')->nullable();
            $table->enum('status', ['draft', 'active', 'deprecated'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_versions');
        Schema::dropIfExists('api_endpoints');
        Schema::dropIfExists('api_environments');
        Schema::dropIfExists('api_collections');
    }
};
