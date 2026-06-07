<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->string('remote_full_name')->nullable()->after('slug')->comment('GitHub full name owner/repo');
            $table->text('remote_token')->nullable()->after('remote_full_name')->comment('Encrypted PAT for remote API access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropColumn(['remote_full_name', 'remote_token']);
        });
    }
};
