<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genders = [
            ['code' => 'm', 'name' => 'muž'],
            ['code' => 'f', 'name' => 'žena'],
        ];

        DB::table('genders')->insert($genders);
    }
}
