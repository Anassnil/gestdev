<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('avatar_path');
            $table->text('bio')->nullable()->after('position');
            $table->json('tech_stack')->nullable()->after('bio');
            $table->json('experience')->nullable()->after('tech_stack');
            $table->json('education')->nullable()->after('experience');
            $table->string('github_url')->nullable()->after('education');
            $table->string('linkedin_url')->nullable()->after('github_url');
            $table->string('website_url')->nullable()->after('linkedin_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'position', 'bio', 'tech_stack', 'experience',
                'education', 'github_url', 'linkedin_url', 'website_url',
            ]);
        });
    }
};
