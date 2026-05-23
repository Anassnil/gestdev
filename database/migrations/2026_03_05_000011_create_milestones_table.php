<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roadmap_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->date('due_at')->nullable();
            $table->boolean('completed')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('milestones');
    }
};
