<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            CohortSeeder::class,
            SiteSeeder::class,
            FacilityTypeSeeder::class,
            FacilitySeeder::class,
            DiagnosisTypeSeeder::class,
            ArtPreExposureSeeder::class,
            ArtStartPlaceSeeder::class,
            StatusSeeder::class,
            VisitTypeSeeder::class,
            TransferTypeSeeder::class,
            MedicationSeeder::class,
            ArvSwitchReasonSeeder::class,
            TbStatusSeeder::class,
            SideEffectSeeder::class,
            KaposiStatusSeeder::class,
            MedicationStatusSeeder::class,
        ]);
    }
}
