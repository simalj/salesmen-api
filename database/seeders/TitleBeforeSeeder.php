<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TitleBeforeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $titlesBefore = [
            'Bc.', 'Mgr.', 'Ing.', 'JUDr.', 'MVDr.', 'MUDr.', 'PaedDr.',
            'prof.', 'doc.', 'dipl.', 'MDDr.', 'Dr.', 'Mgr. art.', 'ThLic.',
            'PhDr.', 'PhMr.', 'RNDr.', 'ThDr.', 'RSDr.', 'arch.', 'PharmDr.'
        ];

        $data = [];
        foreach ($titlesBefore as $title) {
            $data[] = [
                'code' => $title,
                'name' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('titles_before')->insert($data);
    }
}
