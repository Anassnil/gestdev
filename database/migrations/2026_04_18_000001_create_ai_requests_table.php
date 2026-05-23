<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type', 50)->index();          // uml, architecture, tasks, test_cases, improvements
            $table->text('input');
            $table->longText('output')->nullable();
            $table->string('status', 20)->default('pending'); // pending, success, failed, cached
            $table->string('model')->nullable();           // gpt-4o-mini etc.
            $table->unsignedSmallInteger('tokens_used')->nullable();
            $table->unsignedSmallInteger('retries')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('meta')->nullable();               // extra context (board_id, format, temperature…)
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
