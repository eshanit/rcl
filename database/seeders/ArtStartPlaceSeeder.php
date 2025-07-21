<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\ArtStartPlace;
use Illuminate\Database\Seeder;

class ArtStartPlaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $startArtPlaces = [
            ['name' => 'Within SMART program area', 'code' => '111'],
            ['name' => 'Outside SMART program area', 'code' => '112'],
            ['name' => 'NA', 'code' => 'NA'],
        ];

        ArtStartPlace::insert($startArtPlaces);
    }
}
