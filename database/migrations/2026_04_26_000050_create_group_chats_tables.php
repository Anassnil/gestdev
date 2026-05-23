<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_chats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('board_id')->nullable()->constrained('boards')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['created_by', 'created_at']);
        });

        Schema::create('group_chat_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_chat_id')->constrained('group_chats')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamps();

            $table->unique(['group_chat_id', 'user_id']);
            $table->index(['user_id', 'group_chat_id']);
        });

        Schema::create('group_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_chat_id')->constrained('group_chats')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['group_chat_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_chat_messages');
        Schema::dropIfExists('group_chat_members');
        Schema::dropIfExists('group_chats');
    }
};
