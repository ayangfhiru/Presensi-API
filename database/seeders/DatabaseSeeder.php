<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            FirstUserSeeder::class,
            DivisionSeeder::class,
            UserSeeder::class
        ]);
    }
}
