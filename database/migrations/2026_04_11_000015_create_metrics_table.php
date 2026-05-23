<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_run_id')->constrained('training_runs')->cascadeOnDelete();
            $table->string('name');
            $table->double('value');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('metrics');
    }
};
