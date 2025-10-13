<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaritalStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maritalStatuses = [
            [
                'code' => 'single',
                'name_m' => 'slobodný',
                'name_f' => 'slobodná', 
                'name_general' => 'slobodný / slobodná'
            ],
            [
                'code' => 'married',
                'name_m' => 'ženatý',
                'name_f' => 'vydatá',
                'name_general' => 'ženatý / vydatá'
            ],
            [
                'code' => 'divorced', 
                'name_m' => 'rozvedený',
                'name_f' => 'rozvedená',
                'name_general' => 'rozvedený / rozvedená'
            ],
            [
                'code' => 'widowed',
                'name_m' => 'vdovec', 
                'name_f' => 'vdova',
                'name_general' => 'vdovec / vdova'
            ],
        ];

        DB::table('marital_statuses')->insert($maritalStatuses);
    }
}
