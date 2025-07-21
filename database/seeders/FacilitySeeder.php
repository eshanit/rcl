<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $facilities = [
            ['facility_type_id' => 2, 'name' => 'Bota HC'],
            ['facility_type_id' => 2, 'name' => 'Chinyabako HC'],
            ['facility_type_id' => 2, 'name' => 'Fube HC'],
            ['facility_type_id' => 2, 'name' => 'Harava HC'],
            ['facility_type_id' => 2, 'name' => 'Jerera Satellite HC'],
            ['facility_type_id' => 2, 'name' => 'Madhloro HC'],
            ['facility_type_id' => 2, 'name' => 'Mageza HC'],
            ['facility_type_id' => 2, 'name' => 'Mushaya HC'],
            ['facility_type_id' => 1, 'name' => 'Musiso Hosp'],
            ['facility_type_id' => 2, 'name' => 'Nemauku HC'],
            ['facility_type_id' => 2, 'name' => 'Nhema HC'],
            ['facility_type_id' => 2, 'name' => 'Siyawareva HC'],
            ['facility_type_id' => 2, 'name' => 'Svuure HC'],
            ['facility_type_id' => 2, 'name' => 'Veza HC'],
            ['facility_type_id' => 2, 'name' => 'Zibwowa HC'],
            ['facility_type_id' => 2, 'name' => 'Zinguvo HC'],
            ['facility_type_id' => 2, 'name' => 'Bikita HC'],
            ['facility_type_id' => 2, 'name' => 'Chikuku HC'],
            ['facility_type_id' => 2, 'name' => 'Chirorwe HC'],
            ['facility_type_id' => 2, 'name' => 'Chitasa HC'],
            ['facility_type_id' => 2, 'name' => 'Dewure 1 HC'],
            ['facility_type_id' => 2, 'name' => 'Dewure 2 HC'],
            ['facility_type_id' => 2, 'name' => 'Gangare HC'],
            ['facility_type_id' => 2, 'name' => 'Hozvi HC'],
            ['facility_type_id' => 2, 'name' => 'Marozva HC'],
            ['facility_type_id' => 2, 'name' => 'Mukore HC'],
            ['facility_type_id' => 2, 'name' => 'Mungezi HC'],
            ['facility_type_id' => 2, 'name' => 'Murwira HC'],
            ['facility_type_id' => 2, 'name' => 'Mutikizizi HC'],
            ['facility_type_id' => 2, 'name' => 'Negovano HC'],
            ['facility_type_id' => 2, 'name' => 'Ngorima HC'],
            ['facility_type_id' => 2, 'name' => 'Nyika HC'],
            ['facility_type_id' => 2, 'name' => 'Pfupajena HC'],
            ['facility_type_id' => 1, 'name' => 'Silveira Hosp'],
            ['facility_type_id' => 3, 'name' => 'NA'],
        ];

        Facility::insert($facilities);
    }
}
