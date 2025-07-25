<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\TbStatus;
use Illuminate\Database\Seeder;

class TbStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $status = [
            ['name' => 'TB Screening negative', 'code' => '312'],
            ['name' => 'TB screening positive, sputum or Xray requested', 'code' => '313'],
            ['name' => 'No TB screening', 'code' => '314'],
            ['name' => 'Under TB treatment', 'code' => '315'],
            ['name' => 'Prophylactic INH treatment', 'code' => '316'],
            ['name' => 'NA', 'code' => 'NA'],
        ];

        TbStatus::insert($status);
    }
}
