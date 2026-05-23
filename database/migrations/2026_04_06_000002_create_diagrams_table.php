<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('diagrams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('uml');
            $table->string('title');
            $table->string('image')->nullable();
            $table->text('code')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('diagrams');
    }
};
