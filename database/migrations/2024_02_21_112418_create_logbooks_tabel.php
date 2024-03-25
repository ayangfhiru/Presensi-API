<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logbooks', function (Blueprint $table) {
            $table->id();
            $table->text('note')->nullable(false);
            $table->text('image')->nullable();
            $table->boolean('status')->default(false);
            $table->unsignedBigInteger('date')->nullable(false);
            $table->unsignedBigInteger('project_id')->nullable(false);
            $table->timestamps();

            $table->foreign('project_id')->on('projects')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbooks');
    }
};
