<?php

namespace Database\Seeders;

use App\Models\Cohort;
use Illuminate\Database\Seeder;

class CohortSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cohorts = [
            [
                'name' => 'Musiso',
            ],
            [
                'name' => 'Silveira',
            ],
        ];

        Cohort::insert($cohorts);
    }
}
