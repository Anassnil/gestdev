<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('priority')->nullable()->after('assignee_id');
            $table->integer('points')->nullable()->after('priority');
            $table->string('type')->nullable()->after('points');
            $table->date('due_date')->nullable()->after('type');
            $table->json('dependencies')->nullable()->after('due_date');
            $table->json('tags')->nullable()->after('dependencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['priority','points','type','due_date','dependencies','tags']);
        });
    }
};
