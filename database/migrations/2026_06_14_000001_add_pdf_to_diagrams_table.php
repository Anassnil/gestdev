<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('diagrams', function (Blueprint $table) {
            if (! Schema::hasColumn('diagrams', 'pdf')) {
                $table->string('pdf')->nullable()->after('image');
            }
        });
    }

    public function down()
    {
        Schema::table('diagrams', function (Blueprint $table) {
            if (Schema::hasColumn('diagrams', 'pdf')) {
                $table->dropColumn('pdf');
            }
        });
    }
};
