<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\PatientStatus;
use Illuminate\Database\Seeder;

class PatientStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $statuses = [
            [
                'name' => 'Active',
            ],
            [
                'name' => 'Inactive',
            ],
            [
                'name' => 'Transferred Out',
            ],
            [
                'name' => 'Lost to Follow Up',
            ],
            [
                'name' => 'Died',
            ],
        ];

        PatientStatus::insert($statuses);
    }
}
