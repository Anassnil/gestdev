<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_version_id')->constrained('model_versions')->cascadeOnDelete();
            $table->string('environment');
            $table->string('status')->default('inactive');
            $table->string('endpoint_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployments');
    }
};
