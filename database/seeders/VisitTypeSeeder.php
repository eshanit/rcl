<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\VisitType;
use Illuminate\Database\Seeder;

class VisitTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $visitTypes = [
            ['name' => 'Start ART', 'code' => '231'],
            ['name' => 'Follow up consultation', 'code' => '232'],
            ['name' => 'Pharmacy Pick Up', 'code' => '233'],
            ['name' => 'Restart after interruption', 'code' => '234'],
            ['name' => 'NA', 'code' => 'NA'],
        ];

        VisitType::insert($visitTypes);
    }
}
