<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->unsignedBigInteger('default_branch_id')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->unique(['owner_id', 'slug']);
            $table->index(['owner_id', 'visibility']);
            $table->index(['owner_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
