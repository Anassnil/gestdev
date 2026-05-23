<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('requirements', function (Blueprint $table) {
            if (!Schema::hasColumn('requirements', 'estimate')) {
                $table->unsignedInteger('estimate')->nullable()->after('position');
            }
            if (!Schema::hasColumn('requirements', 'acceptance_criteria')) {
                $table->text('acceptance_criteria')->nullable()->after('estimate');
            }
        });
    }

    public function down()
    {
        Schema::table('requirements', function (Blueprint $table) {
            if (Schema::hasColumn('requirements', 'acceptance_criteria')) {
                $table->dropColumn('acceptance_criteria');
            }
            if (Schema::hasColumn('requirements', 'estimate')) {
                $table->dropColumn('estimate');
            }
        });
    }
};
