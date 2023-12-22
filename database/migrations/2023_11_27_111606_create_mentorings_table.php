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
        Schema::create('mentorings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mentor_id')->nullable(false);
            $table->unsignedBigInteger('participant_id')->nullable(false)->unique('mentoring_participant_unique');
            $table->timestamps();

            $table->foreign('mentor_id')->on('users')->references('id');
            $table->foreign('participant_id')->on('users')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentorings');
    }
};
