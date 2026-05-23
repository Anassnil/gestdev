<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'sprint_id')) {
                $table->foreignId('sprint_id')->nullable()->constrained('sprints')->nullOnDelete();
            }
            // Do not attempt to alter existing `points` column here to avoid data truncation issues.
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'sprint_id')) {
                $table->dropConstrainedForeignId('sprint_id');
            }
        });
    }
};
