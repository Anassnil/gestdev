<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('deployment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->text('message');
            $table->string('level')->default('info');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_logs');
    }
};
