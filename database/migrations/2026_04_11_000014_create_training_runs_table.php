<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('training_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->json('parameters')->nullable();
            $table->string('status')->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_runs');
    }
};
