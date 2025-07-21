<?php

namespace Database\Seeders;

use App\Models\DiagnosisType;
use Illuminate\Database\Seeder;

class DiagnosisTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $diagnosisTypes = [
            ['name' => 'ANGC'],
            ['name' => 'ASY'],
            ['name' => 'BACTINF'],
            ['name' => 'BLO'],
            ['name' => 'CANDO'],
            ['name' => 'CANDOR'],
            ['name' => 'COC'],
            ['name' => 'CRYPTO'],
            ['name' => 'DIA'],
            ['name' => 'ENC'],
            ['name' => 'FEV'],
            ['name' => 'FTT'],
            ['name' => 'FUNI'],
            ['name' => 'GINEC'],
            ['name' => 'HSM'],
            ['name' => 'HSV'],
            ['name' => 'HZO'],
            ['name' => 'KS'],
            ['name' => 'LEUKO'],
            ['name' => 'LPO'],
            ['name' => 'LYAD'],
            ['name' => 'MANUMO'],
            ['name' => 'MANUSE'],
            ['name' => 'MOL'],
            ['name' => 'ORULC'],
            ['name' => 'PCP'],
            ['name' => 'PGL'],
            ['name' => 'PMLE'],
            ['name' => 'PNEU'],
            ['name' => 'PPE'],
            ['name' => 'RESPIN'],
            ['name' => 'SDERM'],
            ['name' => 'STOM'],
            ['name' => 'TBEXTP'],
            ['name' => 'TBPILM'],
            ['name' => 'TBPULM'],
            ['name' => 'TEX'],
            ['name' => 'WAS'],
            ['name' => 'WLM'],
            ['name' => 'WLO'],
            ['name' => 'WLS'],
            ['name' => 'ZOS'],
            ['name' => 'NA'],
        ];

        DiagnosisType::insert($diagnosisTypes);

    }
}
