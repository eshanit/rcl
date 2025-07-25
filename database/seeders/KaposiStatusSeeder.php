<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\KaposiStatus;
use Illuminate\Database\Seeder;

class KaposiStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $statuses = [
            ['name' => 'Yes - not under KS-chemotherapy', 'code' => '322'],
            ['name' => 'Yes - under KS-chemotherapy', 'code' => '323'],
            ['name' => 'No', 'code' => 'No'],
        ];

        KaposiStatus::insert($statuses);

    }
}
