<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TitleAfterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $titlesAfter = [
            'CSc.', 'DrSc.', 'PhD.', 'ArtD.', 'DiS', 'DiS.art', 'FEBO', 
            'MPH', 'BSBA', 'MBA', 'DBA', 'MHA', 'FCCA', 'MSc.', 'FEBU', 'LL.M'
        ];

        $data = [];
        foreach ($titlesAfter as $title) {
            $data[] = [
                'code' => $title,
                'name' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('titles_after')->insert($data);
    }
}
