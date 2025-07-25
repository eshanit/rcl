<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\ArtPreExposure;
use App\Models\ArtStartPlace;
use App\Models\ArvSwitchReason;
use App\Models\DiagnosisType;
use App\Models\Facility;
use App\Models\InitialVisit;
use App\Models\KaposiStatus;
use App\Models\Medication;
use App\Models\MedicationStatus;
use App\Models\Patient;
use App\Models\SideEffect;
use App\Models\Site;
use App\Models\TbStatus;
use App\Models\TransferType;
use App\Models\Visit;
use App\Models\VisitDetail;
use App\Models\VisitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportDataControllerCopy extends Controller
{
    // Preload all mappings for faster lookups
    private $mappings = [];

    public function import(Request $request)
    {
        // Get uploaded file paths from session
        $patientsPath = session('patients_file_path');
        $visitsPath = session('visits_file_path');

        // Verify files exist
        if (! $patientsPath || ! file_exists($patientsPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Patients file not found',
            ], 400);
        }

        if (! $visitsPath || ! file_exists($visitsPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Visits file not found',
            ], 400);
        }

        // Preload all necessary mappings
        $this->preloadMappings();

        DB::beginTransaction();

        try {
            // Process Patients Data
            $patientIdMap = $this->processPatientsData($patientsPath);

            // Process Visits Data
            $this->processVisitsData($visitsPath, $patientIdMap);

            DB::commit();

            // Clean up files
            @unlink($patientsPath);
            @unlink($visitsPath);
            session()->forget(['patients_file_path', 'visits_file_path']);

            return response()->json([
                'status' => 'success',
                'message' => 'Data imported successfully',
                'stats' => [
                    'patients' => count($patientIdMap),
                    'visits' => count($visitIdMap),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Import failed: '.$e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : [],
            ], 500);
        }
    }

    private function preloadMappings()
    {
        // Normalize names by trimming and lowercasing
        $this->mappings = [
            'sites' => Site::all()->mapWithKeys(function ($site) {
                return [strtolower(trim($site->name)) => $site->id];
            })->all(),

            'diagnosis_types' => DiagnosisType::all()->mapWithKeys(function ($diagnosis) {
                return [strtolower(trim($diagnosis->name)) => $diagnosis->id];
            })->all(),

            'art_pre_exposures' => ArtPreExposure::pluck('id', 'code')->all(),
            'art_start_places' => ArtStartPlace::pluck('id', 'code')->all(),

            'facilities' => Facility::all()->mapWithKeys(function ($facility) {
                return [strtolower(trim($facility->name)) => $facility->id];
            })->all(),

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

    private function processPatientsData($filePath)
    {
        ini_set('max_execution_time', 1200);

        $patientIdMap = [];
        $patientsData = (new FastExcel)->import(str_replace('\\', '/', $filePath));

        foreach ($patientsData as $row) {
            // Get normalized site name
            $siteName = strtolower(trim($row['Site'] ?? ''));

            // Create Patient
            $patient = Patient::create([
                'p_number' => $row['PatientNumber'],
                'np_number' => $row['NationalPatientNumber'] ?? null,
                'date_of_birth' => $row['DateBorn'] ?? null,
                'gender' => $row['Sex'] ?? null,
                'height' => is_numeric($row['HeightAdults']) ? (int) $row['HeightAdults'] : null,
                'site_id' => $this->mappings['sites'][$siteName] ?? null,
            ]);

            $patientIdMap[$row['PatientNumber']] = $patient->id;

            // Get normalized diagnosis values
            $diag1 = strtolower(trim($row['Diag1stVisit1'] ?? ''));
            $diag2 = strtolower(trim($row['Diag1stVisit2'] ?? ''));
            $diag3 = strtolower(trim($row['Diag1stVisit3'] ?? ''));
            $diag4 = strtolower(trim($row['Diag1stVisit4'] ?? ''));

            // Create Initial Visit
            $initialVisit = new InitialVisit([
                'first_positive_hiv' => $row['FirstPositiveHIVTest'] ?? null,
                'who_stage' => $row['WHOStage1stVisit'] ?? null,
                'previous_tb_tt' => $row['PreviousTbTt'] ?? null,
                'cd4_baseline' => $row['CD4Baseline'] ?? null,
                'diagnosis_1' => $this->mappings['diagnosis_types'][$diag1] ?? null,
                'diagnosis_2' => $this->mappings['diagnosis_types'][$diag2] ?? null,
                'diagnosis_3' => $this->mappings['diagnosis_types'][$diag3] ?? null,
                'diagnosis_4' => $this->mappings['diagnosis_types'][$diag4] ?? null,
                'art_pre_exposure_id' => $this->mappings['art_pre_exposures'][$row['ARTPreExposure']] ?? null,
                'art_start_place_id' => $this->mappings['art_start_places'][$row['StartARTPlace']] ?? null,
            ]);

            $patient->initialVisit()->save($initialVisit);
        }

        return $patientIdMap;
    }

    private function processVisitsData($filePath, $patientIdMap)
    {
        ini_set('max_execution_time', 1200);

        $visitsData = (new FastExcel)->import($filePath);
        $medicationsToInsert = [];

        foreach ($visitsData as $row) {
            // Get normalized values
            $facilityName = strtolower(trim($row['FacilityName'] ?? ''));
            $facilityId = $this->mappings['facilities'][$facilityName] ?? null;

            // Log facility information
            \Log::info("Facility Name: '$facilityName', Facility ID: ".($facilityId !== null ? $facilityId : 'null'));

            // Check for a valid facility ID
            if ($facilityId === null) {
                throw new \Exception("Invalid Facility Name: '$facilityName' for Patient Number: {$row['PatientNumber']}");
            }

            // Get normalized diagnosis values
            $diag1 = strtolower(trim($row['DiagNew1'] ?? ''));
            $diag2 = strtolower(trim($row['DiagNew2'] ?? ''));
            // Create Visit
            $visit = Visit::create([
                'patient_id' => $patientIdMap[$row['PatientNumber']] ?? null,
                'instance' => $row['VisitNumber'] ?? null,
                'app_visit_date' => $this->validateDate($row['AppointedVisitDate']),
                'actual_visit_date' => $this->validateDate($row['ActualVisitDate']),
                'next_visit_date' => $this->validateDate($row['NextVisitDate']),
                'transfer_smart' => $row['TransferWithinSMART'] ?? null,
                'facility_id' => $facilityId,
                'visit_type_id' => $this->mappings['visit_types'][$row['VisitType']] ?? null,
                'transfer_type_id' => $this->mappings['transfer_types'][$row['Transfer']] ?? null,
            ]);

            // Create Visit Detail
            $visitDetail = new VisitDetail([
                'adherent' => $row['Adherent'] ?? null,
                'weight' => is_numeric($row['Weight']) ? (float) $row['Weight'] : null,
                'height_children' => is_numeric($row['HeightChildren']) ? (float) $row['HeightChildren'] : null,
                'pregnant' => $row['Pregnant'] ?? null,
                'tlc' => ! empty($row['TLC']) && is_numeric($row['TLC']) ? (int) $row['TLC'] : null,
                'spatum_tb_test' => $row['SputumTBTest'] ?? null,
                'alt' => ! empty($row['ALT']) && is_numeric($row['ALT']) ? (int) $row['ALT'] : null,
                'creatinine' => $row['Creatinine'] ?? null,
                'creatinine_2' => $row['Creatinine2'] ?? null,
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
            ]);

            $visit->visitDetails()->save($visitDetail);

            // Prepare medications data
            $this->processMedications($visit->id, $row, $medicationsToInsert);
        }

        // Batch insert medications
        if (! empty($medicationsToInsert)) {
            DB::table('visit_medications')->insert($medicationsToInsert);
        }
    }

    private function processMedications($visitId, $row, &$medicationsToInsert)
    {
        $medicationColumns = [
            'Cotrimox', 'x3TC', 'TDF', 'NVP', 'AZT', 'EFV', 'ABC',
            'D4T', 'DDI', 'LPVr', 'SQV', 'IDV', 'NFV', 'RTV', 'ATV', 'FTC', 'DTG',
        ];

        foreach ($medicationColumns as $column) {
            $statusValue = $row[$column] ?? null;

            if ($statusValue && isset($this->mappings['medication_statuses'][$statusValue])) {
                $medicationsToInsert[] = [
                    'visit_id' => $visitId,
                    'medication_id' => $this->mappings['medications'][$column] ?? null,
                    'medication_status_id' => $this->mappings['medication_statuses'][$statusValue],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    private function validateDate($date)
    {
        // If $date is a DateTime or DateTimeImmutable object, convert it to a string
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d');
        }

        if (empty($date)) {
            return null;
        }

        // Create a DateTime object from the date string
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);

        // Check if the date is valid and matches the format
        if ($dateTime && $dateTime->format('Y-m-d') === $date) {
            // Define a sensible date range
            $minDate = new \DateTime('1900-01-01'); // Minimum date
            $maxDate = new \DateTime('2100-12-31'); // Maximum date

            // Check if the date falls within the specified range
            if ($dateTime >= $minDate && $dateTime <= $maxDate) {
                return $dateTime->format('Y-m-d');
            }
        }

        return null; // Return null for invalid dates
    }
}
