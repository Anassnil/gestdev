<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('pr_url')->nullable()->after('description');
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(false)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('pr_url');
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('ai_enabled');
        });
    }
};
