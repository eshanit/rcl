<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\ArtPreExposure;
use Illuminate\Database\Seeder;

class ArtPreExposureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $artPreExposures = [
            ['name' => 'No', 'code' => 'No'],
            ['name' => 'Prevention of Mother to Child Transmission', 'code' => '102'],
            ['name' => 'Post exposure Prophylaxis', 'code' => '103'],
            ['name' => 'Interrupted ART', 'code' => '104'],
            ['name' => 'Other', 'code' => '105'],
            ['name' => 'NA', 'code' => 'NA'],
        ];

        ArtPreExposure::insert($artPreExposures);
    }
}
