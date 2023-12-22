<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FirstUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'username' => 'firstuser',
            'email' => 'first@example.com',
            'phone' => '0',
            'password' => Hash::make('rahasia'),
            'role_id' => 1,
            'division_id' => null
        ]);
    }
}
