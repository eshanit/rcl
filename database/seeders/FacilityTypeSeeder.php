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
            ],
            [
                'name' => 'Peripheral',
                'code' => '222',
            ],
            [
                'name' => 'Not Applicable',
                'code' => 'NA',
            ],
        ];

        FacilityType::insert($facilityTypes);
    }
}
