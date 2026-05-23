<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('requirements')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('functional');
            $table->string('priority')->default('medium');
            $table->string('status')->default('draft');
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('linked_feature_id')->nullable();
            $table->string('linked_feature_type')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('requirements');
    }
};
