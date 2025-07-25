<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\ArvSwitchReason;
use Illuminate\Database\Seeder;

class ArvSwitchReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $reasons = [
            ['name' => 'Toxicity / Side effects', 'code' => '301'],
            ['name' => 'Treatment failure', 'code' => '302'],
            ['name' => 'Pregnancy', 'code' => '303'],
            ['name' => 'Start/Stop Tb treatment', 'code' => '304'],
            ['name' => 'New first line regimen', 'code' => '305'],
            ['name' => 'Out of stock', 'code' => '306'],
            ['name' => 'Other', 'code' => '307'],
            ['name' => 'NA', 'code' => 'NA'],
        ];

        ArvSwitchReason::insert($reasons);
    }
}
