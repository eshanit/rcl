<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $medications = [
            ['name' => 'Cotrimox', 'code' => 'Cotrimox'],
            ['name' => 'X3TC', 'code' => 'x3TC'],
            ['name' => 'TDF', 'code' => 'TDF'],
            ['name' => 'NVP', 'code' => 'NVP'],
            ['name' => 'AZT', 'code' => 'AZT'],
            ['name' => 'EFV', 'code' => 'EFV'],
            ['name' => 'ABC', 'code' => 'ABC'],
            ['name' => 'D4T', 'code' => 'D4T'],
            ['name' => 'DDI', 'code' => 'DDI'],
            ['name' => 'LPVr', 'code' => 'LPVr'],
            ['name' => 'SQV', 'code' => 'SQV'],
            ['name' => 'IDV', 'code' => 'IDV'],
            ['name' => 'NFV', 'code' => 'NFV'],
            ['name' => 'RTV', 'code' => 'RTV'],
            ['name' => 'ATV', 'code' => 'ATV'],
            ['name' => 'FTC', 'code' => 'FTC'],
            ['name' => 'DTG', 'code' => 'DTG'],
        ];

        Medication::insert($medications);
    }
}
