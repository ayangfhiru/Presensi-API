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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->nullable(false)->unique('user_username_unique');
            $table->string('email', 100)->nullable(false)->unique('user_email_unique');
            $table->string('phone', 20)->nullable()->unique('user_phone_unique');
            $table->string('password')->nullable(false);
            $table->unsignedBigInteger('role_id')->nullable(false);
            $table->unsignedBigInteger('division_id')->nullable();
            $table->timestamps();

            $table->foreign('role_id')->on('roles')->references('id');
            $table->foreign('division_id')->on('divisions')->references('id');
        });

        // DB::table('users')->insert([
        //     'username' => 'administrator',
        //     'email' => 'xyz@xample.com',
        //     'password' => Hash::make('admin123'),
        //     'role_id' => 1
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
