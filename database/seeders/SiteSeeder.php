<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $sites = [
            [
                'name' => 'E',
                'cohort_id' => 1,
            ],
            [
                'name' => 'F',
                'cohort_id' => 1,
            ],
            [
                'name' => 'M',
                'cohort_id' => 1,
            ],
            [
                'name' => 'C',
                'cohort_id' => 2,
            ],
            [
                'name' => 'D',
                'cohort_id' => 2,
            ],
            [
                'name' => 'S',
                'cohort_id' => 2,
            ],

        ];

        Site::insert($sites);
    }
}
