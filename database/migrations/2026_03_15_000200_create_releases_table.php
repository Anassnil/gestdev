<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 32)->index(); // superset, epic, schedule
            $table->string('version')->nullable();
            $table->date('target_date')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->integer('position')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('releases');
    }
};
