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
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silveira',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Cohort::insert($cohorts);
    }
}
