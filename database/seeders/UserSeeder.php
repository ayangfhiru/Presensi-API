<?php

namespace Database\Seeders;

// use App\Models\User;
use App\Models\Role;
use App\Models\User;
use App\Models\Division;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(5)->create();
    }
}
