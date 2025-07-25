<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\TransferType;
use Illuminate\Database\Seeder;

class TransferTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $transferTypes = [
            ['name' => 'Transferred in', 'code' => '241'],
            ['name' => 'Transferred out', 'code' => '242'],
        ];

        TransferType::insert($transferTypes);
    }
}
