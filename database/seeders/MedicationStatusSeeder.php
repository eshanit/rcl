<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\MedicationStatus;
use Illuminate\Database\Seeder;

class MedicationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $statuses = [
            ['name' => 'Start/Restart'],
            ['name' => 'Continued'],
            ['name' => 'Stop'],
            ['name' => 'No'],
            ['name' => 'NA'],
        ];

        MedicationStatus::insert($statuses);
    }
}
