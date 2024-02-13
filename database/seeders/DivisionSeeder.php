<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('divisions')->insert([
            'name' => 'Backend'
        ]);
        DB::table('divisions')->insert([
            'name' => 'Frontend'
        ]);
        DB::table('divisions')->insert([
            'name' => 'UI/UX'
        ]);
        DB::table('divisions')->insert([
            'name' => 'Database Administrator'
        ]);
        DB::table('divisions')->insert([
            'name' => 'Web Developer'
        ]);
        
    }
}
