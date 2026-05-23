<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repository_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained('repositories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['admin', 'maintainer', 'developer'])->default('developer');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['repository_id', 'user_id']);
            $table->index(['repository_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repository_collaborators');
    }
};
