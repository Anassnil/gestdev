<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('associates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('associate_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship_type', 30)->default('associate');
            $table->timestamps();

            $table->unique(['user_id', 'associate_user_id']);
            $table->index(['user_id', 'relationship_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('associates');
    }
};
