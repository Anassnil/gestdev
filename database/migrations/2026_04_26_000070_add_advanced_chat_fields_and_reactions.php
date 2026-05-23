<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('reply_to_id')->nullable()->after('receiver_id')->constrained('messages')->nullOnDelete();
            $table->timestamp('edited_at')->nullable()->after('read_at');
            $table->softDeletes();
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 24);
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji']);
            $table->index(['message_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_id');
            $table->dropColumn(['edited_at', 'deleted_at']);
        });
    }
};
