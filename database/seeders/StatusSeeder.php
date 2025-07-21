<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $statuses = [
            [
                'name' => 'Active',
            ],
            [
                'name' => 'Inactive',
            ],
            [
                'name' => 'Transferred Out',
            ],
            [
                'name' => 'Lost to Follow Up',
            ],
            [
                'name' => 'Died',
            ],
        ];

        Status::insert($statuses);
    }
}
