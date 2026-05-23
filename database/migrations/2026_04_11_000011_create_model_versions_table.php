<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->string('version')->nullable();
            $table->json('config')->nullable();
            $table->string('status')->default('created');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('model_versions');
    }
};
