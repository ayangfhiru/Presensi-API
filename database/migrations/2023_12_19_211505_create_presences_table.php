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
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('date')->nullable(false);
            $table->unsignedBigInteger('entry_time')->nullable(false);
            $table->unsignedBigInteger('exit_time')->nullable();
            $table->boolean('status')->default(false);
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->unsignedBigInteger('mentoring_id')->nullable(false);
            $table->timestamps();

            $table->foreign('user_id')->on('users')->references('id');
            $table->foreign('mentoring_id')->on('mentorings')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
