<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\SideEffect;
use Illuminate\Database\Seeder;

class SideEffectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $effects = [
            ['name' => 'GI-tract / abdomen toxicity', 'code' => '331'],
            ['name' => 'Blood toxicity (anaemia…)', 'code' => '332'],
            ['name' => 'Peripheral neuropathy', 'code' => '333'],
            ['name' => 'Abnormal fat redistribution', 'code' => '334'],
            ['name' => 'Skin reaction', 'code' => '335'],
            ['name' => 'Liver toxicity (high ALT)', 'code' => '336'],
            ['name' => 'Other', 'code' => '337'],
            ['name' => 'NA', 'code' => 'NA'],
        ];

        SideEffect::insert($effects);
    }
}
