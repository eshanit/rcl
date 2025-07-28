<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\ArtPreExposure;
use App\Models\ArtStartPlace;
use App\Models\ArvSwitchReason;
use App\Models\DiagnosisType;
use App\Models\Facility;
use App\Models\KaposiStatus;
use App\Models\Medication;
use App\Models\MedicationStatus;
use App\Models\Patient;
use App\Models\SideEffect;
use App\Models\Site;
use App\Models\TbStatus;
use App\Models\TransferType;
use App\Models\Visit;
use App\Models\VisitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportDataController extends Controller
{
    private $mappings = [];

    private $batchSize = 500; // Adjust based on your server's memory

    public function import(Request $request)
    {
        $patientsPath = session('patients_file_path');
        $visitsPath = session('visits_file_path');
        $batchId = (string) Str::uuid();

        if (! ($patientsPath && file_exists($patientsPath))) {
            return response()->json(['status' => 'error', 'message' => 'Patients file not found'], 400);
        }

        if (! ($visitsPath && file_exists($visitsPath))) {
            return response()->json(['status' => 'error', 'message' => 'Visits file not found'], 400);
        }

        $this->preloadMappings();

        try {
            $patientIdMap = $this->processPatientsData($patientsPath, $batchId);
            $visitCount = $this->processVisitsData($visitsPath, $patientIdMap, $batchId);

            // Clean up files
            @unlink($patientsPath);
            @unlink($visitsPath);
            session()->forget(['patients_file_path', 'visits_file_path', 'import_progress']);

            return response()->json([
                'status' => 'success',
                'message' => 'Data imported successfully',
                'stats' => [
                    'patients' => count($patientIdMap),
                    'visits' => $visitCount,
                ],
            ]);

        } catch (\Exception $e) {
            Patient::where('batch_id', $batchId)->delete();
            Visit::where('batch_id', $batchId)->delete();

            return response()->json([
                'status' => 'error',
                'message' => 'Import failed: '.$e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : [],
            ], 500);
        }
    }

    public function progress(Request $request)
    {
        return response()->json(
            Cache::get('import_progress', [
                'patients' => ['processed' => 0, 'total' => 0],
                'visits' => ['processed' => 0, 'total' => 0],
            ])
        );
    }

    private function preloadMappings()
    {
        $this->mappings = [
            'sites' => Site::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [
                strtolower(trim($name)) => $id,
            ])->all(),

            'diagnosis_types' => DiagnosisType::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [
                strtolower(trim($name)) => $id,
            ])->all(),

            'art_pre_exposures' => ArtPreExposure::pluck('id', 'code')->all(),
            'art_start_places' => ArtStartPlace::pluck('id', 'code')->all(),
            'facilities' => Facility::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [
                strtolower(trim($name)) => $id,
            ])->all(),

            'visit_types' => VisitType::pluck('id', 'code')->all(),
            'transfer_types' => TransferType::pluck('id', 'code')->all(),
            'arv_switch_reasons' => ArvSwitchReason::pluck('id', 'code')->all(),
            'tb_statuses' => TbStatus::pluck('id', 'code')->all(),
            'kaposi_statuses' => KaposiStatus::pluck('id', 'code')->all(),
            'side_effects' => SideEffect::pluck('id', 'code')->all(),
            'medications' => Medication::pluck('id', 'code')->all(),
            'medication_statuses' => MedicationStatus::pluck('id', 'name')->all(),
        ];
    }

    private function processPatientsData($filePath, $batchId)
    {
        $patientIdMap = [];
        $batch = [];
        $rowCount = 0;

        (new FastExcel)->import($filePath, function ($row) use (&$batch, &$patientIdMap, &$rowCount, &$batchId) {
            $batch[] = $row;
            $rowCount++;

            if (count($batch) >= $this->batchSize) {
                $this->insertPatientBatch($batch, $patientIdMap, $batchId);
                $batch = [];
                gc_collect_cycles(); // Force garbage collection
            }
        });

        if (! empty($batch)) {
            $this->insertPatientBatch($batch, $patientIdMap, $batchId);
            $this->updateProgress('patients', count($batch));
        }

        return $patientIdMap;
    }

    private function insertPatientBatch($rows, &$patientIdMap, $batchId)
    {
        $patientsToInsert = [];
        $initialVisitsToInsert = [];
        $seenInBatch = [];

        foreach ($rows as $row) {
            $patientNumber = $row['PatientNumber'];

            if (isset($patientIdMap[$patientNumber]) || isset($seenInBatch[$patientNumber])) {
                continue;
            }

            $seenInBatch[$patientNumber] = true;
            $siteName = strtolower(trim($row['Site'] ?? ''));

            $patientsToInsert[] = [
                'p_number' => $patientNumber,
                'np_number' => $row['NationalPatientNumber'] ?? null,
                'date_of_birth' => $this->validateDate($row['DateBorn']) ?? null,
                'gender' => $row['Sex'] ?? null,
                'height' => is_numeric($row['HeightAdults']) ? (int) $row['HeightAdults'] : null,
                'site_id' => $this->mappings['sites'][$siteName] ?? null,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $diag1 = strtolower(trim($row['Diag1stVisit1'] ?? ''));
            $diag2 = strtolower(trim($row['Diag1stVisit2'] ?? ''));
            $diag3 = strtolower(trim($row['Diag1stVisit3'] ?? ''));
            $diag4 = strtolower(trim($row['Diag1stVisit4'] ?? ''));

            $initialVisitsToInsert[$patientNumber] = [
                'first_positive_hiv' => $this->validateDate($row['FirstPositiveHIVTest']) ?? null,
                'who_stage' => $row['WHOStage1stVisit'] ?? null,
                'previous_tb_tt' => $row['PreviousTbTt'] ?? null,
                'cd4_baseline' => $row['CD4Baseline'] ?? null,
                'diagnosis_1' => $this->mappings['diagnosis_types'][$diag1] ?? null,
                'diagnosis_2' => $this->mappings['diagnosis_types'][$diag2] ?? null,
                'diagnosis_3' => $this->mappings['diagnosis_types'][$diag3] ?? null,
                'diagnosis_4' => $this->mappings['diagnosis_types'][$diag4] ?? null,
                'art_pre_exposure_id' => $this->mappings['art_pre_exposures'][$row['ARTPreExposure']] ?? null,
                'art_start_place_id' => $this->mappings['art_start_places'][$row['StartARTPlace']] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($patientsToInsert)) {
            return;
        }

        DB::beginTransaction();
        try {
            DB::table('patients')->insert($patientsToInsert);

            $insertedPatients = Patient::whereIn('p_number', array_column($patientsToInsert, 'p_number'))
                ->pluck('id', 'p_number');

            $initialVisits = [];
            foreach ($insertedPatients as $pNumber => $id) {
                if (isset($initialVisitsToInsert[$pNumber])) {
                    $initialVisits[] = array_merge(
                        ['patient_id' => $id],
                        $initialVisitsToInsert[$pNumber]
                    );
                    $patientIdMap[$pNumber] = $id;
                }
            }

            if (! empty($initialVisits)) {
                DB::table('initial_visits')->insert($initialVisits);
            }
            $this->updateProgress('patients', count($patientsToInsert));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processVisitsData($filePath, $patientIdMap, $batchId)
    {
        $visitCount = 0;
        $batch = [];
        $rowCount = 0;

        (new FastExcel)->import($filePath, function ($row) use (&$batch, &$visitCount, $patientIdMap, &$rowCount, &$batchId) {
            $batch[] = $row;
            $rowCount++;

            if (count($batch) >= $this->batchSize) {
                $visitCount += $this->insertVisitBatch($batch, $patientIdMap, $batchId);
                $this->updateProgress('visits', count($batch));
                $batch = [];
                gc_collect_cycles(); // Force garbage collection
            }
        });

        if (! empty($batch)) {
            $visitCount += $this->insertVisitBatch($batch, $patientIdMap, $batchId);
            $this->updateProgress('visits', count($batch));
        }

        return $visitCount;
    }

    private function insertVisitBatch($rows, $patientIdMap, $batchId)
    {
        $visitsToInsert = [];
        $visitDetailsToInsert = [];
        $medicationsToInsert = [];
        $visitCount = 0;

        foreach ($rows as $index => $row) {
            $patientId = $patientIdMap[$row['PatientNumber']] ?? null;
            if (! $patientId) {
                continue;
            }

            $facilityName = strtolower(trim($row['FacilityName'] ?? ''));
            $facilityId = $this->mappings['facilities'][$facilityName] ?? null;

            if (! $facilityId) {
                throw new \Exception("Invalid Facility Name: '$facilityName' for Patient Number: {$row['PatientNumber']}");
            }

            $tempVisitId = $index + 1; // Temporary ID based on row index

            $visitsToInsert[] = [
                'patient_id' => $patientId,
                'instance' => $row['VisitNumber'] ?? null,
                'app_visit_date' => $this->validateDate($row['AppointedVisitDate']),
                'actual_visit_date' => $this->validateDate($row['ActualVisitDate']),
                'next_visit_date' => $this->validateDate($row['NextVisitDate']),
                'transfer_smart' => $row['TransferWithinSMART'] ?? null,
                'facility_id' => $facilityId,
                'visit_type_id' => $this->mappings['visit_types'][$row['VisitType']] ?? null,
                'transfer_type_id' => $this->mappings['transfer_types'][$row['Transfer']] ?? null,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $diag1 = strtolower(trim($row['DiagNew1'] ?? ''));
            $diag2 = strtolower(trim($row['DiagNew2'] ?? ''));

            $visitDetailsToInsert[] = [
                'adherent' => $row['Adherent'] ?? null,
                'weight' => is_numeric($row['Weight']) ? (float) $row['Weight'] : null,
                'height_children' => is_numeric($row['HeightChildren']) ? (float) $row['HeightChildren'] : null,
                'pregnant' => $row['Pregnant'] ?? null,
                'cd4' => ! empty($row['CD4']) && is_numeric($row['CD4']) ? (int) $row['CD4'] : null,
                'cd4_perc' => ! empty($row['CD4Perc']) && is_numeric($row['CD4Perc']) ? (int) $row['CD4Perc'] : null,
                'viral_load' => ! empty($row['ViralLoad']) && is_numeric($row['ViralLoad']) ? (int) $row['ViralLoad'] : null,
                'tlc' => ! empty($row['TLC']) && is_numeric($row['TLC']) ? (int) $row['TLC'] : null,
                'sputum_tb_test' => $row['SputumTBTest'] ?? null,
                'alt' => ! empty($row['ALT']) && is_numeric($row['ALT']) ? (int) $row['ALT'] : null,
                'creatinine' => $row['Creatinine'] ?? null,
                'creatinine_2' => $row['Creatinin2'] ?? null,
                'haemoglobin' => $row['Haemoglobin'] ?? null,
                'arv2' => $row['ARV2'] ?? null,
                'arv2_name' => $row['ARV2Name'] ?? null,
                'new_who_stage' => ! empty($row['NewWHOStage']) && is_numeric($row['NewWHOStage']) ? (int) $row['NewWHOStage'] : null,
                'grupo_apoio' => $row['GrupoApoio'] ?? null,
                'hbsag' => $row['HBsAg'] ?? null,
                'ac_anti_vhc' => $row['AcAntiVHC'] ?? null,
                'tas' => $row['TAS'] ?? null,
                'tad' => $row['TAD'] ?? null,
                'plaquetas' => $row['Plaquetas'] ?? null,
                'ast_got' => $row['AST_GOT'] ?? null,
                'arv_switch_reason_id' => $this->mappings['arv_switch_reasons'][$row['ReasonARVSwitch']] ?? null,
                'tb_status_id' => $this->mappings['tb_statuses'][$row['TbStatus']] ?? null,
                'kaposi_status_id' => $this->mappings['kaposi_statuses'][$row['KaposiSarkoma']] ?? null,
                'diagnosis_1' => $this->mappings['diagnosis_types'][$diag1] ?? null,
                'diagnosis_2' => $this->mappings['diagnosis_types'][$diag2] ?? null,
                'side_effect_id' => $this->mappings['side_effects'][$row['SideEffect']] ?? null,
                // 'created_at' => now(),
                // 'updated_at' => now(),
            ];

            $this->processMedications($row, $tempVisitId, $medicationsToInsert);
        }

        if (empty($visitsToInsert)) {
            return 0;
        }

        DB::beginTransaction();
        try {
            // Insert visits
            DB::table('visits')->insert($visitsToInsert);
            $firstId = DB::getPdo()->lastInsertId();
            $ids = range($firstId, $firstId + count($visitsToInsert) - 1);

            // Assign real visit IDs to details
            foreach ($visitDetailsToInsert as $i => &$detail) {
                $detail['visit_id'] = $ids[$i];
            }

            // Assign real visit IDs to medications
            foreach ($medicationsToInsert as &$med) {
                $realIndex = $med['visit_id'] - 1;
                $med['visit_id'] = $ids[$realIndex] ?? null;
            }

            // Filter out invalid medications
            $validMedications = array_filter($medicationsToInsert, function ($med) {
                return $med['visit_id'] !== null;
            });

            // Insert details and medications
            DB::table('visit_details')->insert($visitDetailsToInsert);

            if (! empty($validMedications)) {
                DB::table('visit_medications')->insert($validMedications);
            }

            DB::commit();
            $this->updateProgress('visits', count($visitsToInsert));

            return count($visitsToInsert);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processMedications($row, $tempVisitId, &$medicationsToInsert)
    {
        $medicationColumns = [
            'Cotrimox', 'x3TC', 'TDF', 'NVP', 'AZT', 'EFV', 'ABC',
            'D4T', 'DDI', 'LPVr', 'SQV', 'IDV', 'NFV', 'RTV', 'ATV', 'FTC', 'DTG',
        ];

        foreach ($medicationColumns as $column) {
            $statusValue = $row[$column] ?? null;

            if ($statusValue && isset($this->mappings['medication_statuses'][$statusValue])) {
                $medicationsToInsert[] = [
                    'visit_id' => $tempVisitId,
                    'medication_id' => $this->mappings['medications'][$column] ?? null,
                    'medication_status_id' => $this->mappings['medication_statuses'][$statusValue],
                ];
            }
        }
    }

    private function initializeProgressTracking($patientsPath, $visitsPath)
    {
        $countPatients = $this->countFileLines($patientsPath) - 1;
        $countVisits = $this->countFileLines($visitsPath) - 1;

        $progress = [
            'patients' => ['processed' => 0, 'total' => $countPatients],
            'visits' => ['processed' => 0, 'total' => $countVisits],
            'started_at' => now(),
        ];

        // Store in session AND cache for cross-request access
        Session::put('import_progress', $progress);
        Cache::put('import_progress', $progress, now()->addMinutes(15));
    }

    private function countFileLines($filePath)
    {
        $file = new SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $lines = $file->key() + 1;
        unset($file);

        return $lines;
    }

    private function updateProgress($type, $count)
    {
        $progress = Cache::get('import_progress', [
            'patients' => ['processed' => 0, 'total' => 0],
            'visits' => ['processed' => 0, 'total' => 0],
        ]);

        if (isset($progress[$type])) {
            $progress[$type]['processed'] += $count;
            Cache::put('import_progress', $progress, now()->addMinutes(15));
            Session::put('import_progress', $progress);
        }
    }

    private function validateDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            if ($dateString instanceof \DateTimeImmutable) {
                $dateColumnValue = $dateString->format('Y-m-d'); // Format it to string
            } elseif ($dateString instanceof \DateTime) {
                $dateColumnValue = $dateString->format('Y-m-d'); // Handle DateTime as well
            }

            return $dateColumnValue;

        } catch (Exception $e) {
            return null;
        }
    }
}
