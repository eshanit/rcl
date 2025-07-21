<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\FacilityType;
use Illuminate\Database\Seeder;

class FacilityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $facilityTypes = [
            [
                'name' => 'Hospital',
                'code' => '221',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Peripheral',
                'code' => '222',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Not Applicable',
                'code' => 'NA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        FacilityType::insert($facilityTypes);
    }
}
