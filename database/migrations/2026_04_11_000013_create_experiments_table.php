<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->string('status')->default('created');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('experiments');
    }
};
