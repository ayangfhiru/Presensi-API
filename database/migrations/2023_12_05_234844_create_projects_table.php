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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project')->nullable(false);
            $table->boolean('status')->default(false);
            $table->unsignedBigInteger('date')->nullable(false);
            $table->unsignedBigInteger('mentoring_id')->nullable(false);
            $table->timestamps();

            $table->foreign('mentoring_id')->on('mentorings')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
