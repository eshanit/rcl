<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Imports\DataValidationImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class UploadAndValidateDataControllerCopy extends Controller
{
    // Patient file headers
    private $patientHeaders = [
        'Country', 'Site', 'Counter', 'PatientNumber', 'NationalPatientNumber',
        'DateBorn', 'Sex', 'HeightAdults', 'FirstPositiveHIVTest', 'ARTPreExposure',
        'WHOStage1stVisit', 'Diag1stVisit1', 'Diag1stVisit2', 'Diag1stVisit3',
        'Diag1stVisit4', 'PreviousTbTt', 'StartARTPlace', 'StartARTDate',
        'CD4Baseline', 'Dead', 'Lost',
    ];

    // Visits file headers
    private $visitsHeaders = [
        'PatientNumber', 'VisitNumber', 'FacilityType', 'FacilityName',
        'TransferWithinSMART', 'VisitType', 'AppointedVisitDate', 'ActualVisitDate',
        'NextVisitDate', 'Transfer', 'Adherent', 'Weight', 'HeightChildren', 'BMI',
        'Pregnant', 'CD4', 'ViralLoad', 'TLC', 'CD4percent', 'SputumTBTest', 'ALT',
        'Creatinine', 'Creatinine2', 'Haemoglobin', 'Cotrimox', 'x3TC', 'TDF', 'NVP',
        'AZT', 'EFV', 'ABC', 'D4T', 'DDI', 'LPVr', 'SQV', 'IDV', 'NFV', 'RTV', 'ATV',
        'FTC', 'DTG', 'ARV2Name', 'ARV2', 'ReasonARVSwitch', 'TbStatus', 'KaposiSarkoma',
        'DiagNew1', 'DiagNew2', 'NewWHOStage', 'SideEffect', 'GrupoApoio', 'HBsAg',
        'AcAntiVHC', 'TAS', 'TAD', 'Plaquetas', 'AST_GOT',
    ];

    /**
     * Upload and validate patients file
     */
    public function uploadPatients(Request $request)
    {
        if (! $request->hasFile('patients_file')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file was uploaded.',
            ], 422);
        }

        return $this->validateFile($request, 'patients_file', $this->patientHeaders, 'patients');
    }

    /**
     * Upload and validate visits file
     */
    public function uploadVisits(Request $request)
    {
        return $this->validateFile($request, 'visits_file', $this->visitsHeaders, 'visits');
    }

    /**
     * Validate cross-file consistency
     */
    public function validateFiles(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'patients_data' => 'required|array',
            'visits_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $patientNumbers = $this->extractPatientNumbersFromArray($request->patients_data);
            $visitsPatientNumbers = $this->extractPatientNumbersFromArray($request->visits_data);

            // Find visits PatientNumbers that don't exist in patients file
            $orphanedNumbers = array_diff($visitsPatientNumbers, $patientNumbers);

            $issues = [];
            if (count($orphanedNumbers)) {
                $issues[] = [
                    'type' => 'orphaned_patient_numbers',
                    'message' => 'Some visits have PatientNumbers not found in patients file',
                    'count' => count($orphanedNumbers),
                    'examples' => array_slice($orphanedNumbers, 0, 10),
                ];
            }

            // Find patients without visits
            $patientsWithoutVisits = array_diff($patientNumbers, $visitsPatientNumbers);
            if (count($patientsWithoutVisits)) {
                $issues[] = [
                    'type' => 'patients_without_visits',
                    'message' => 'Some patients have no visit records',
                    'count' => count($patientsWithoutVisits),
                    'examples' => array_slice($patientsWithoutVisits, 0, 10),
                ];
            }

            return response()->json([
                'status' => count($issues) ? 'warning' : 'success',
                'message' => count($issues)
                    ? 'Cross-file validation completed with issues'
                    : 'Cross-file validation successful',
                'orphaned_visits_count' => count($orphanedNumbers),
                'patients_without_visits_count' => count($patientsWithoutVisits),
                'issues' => $issues,
                'stats' => [
                    'total_patients' => count($patientNumbers),
                    'total_visits' => count($request->visits_data),
                    'unique_patient_visits' => count($visitsPatientNumbers),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cross-file validation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generic file validation method
     */
    private function validateFile(Request $request, $fieldName, $requiredHeaders, $fileType)
    {
        // Increase time limit to 10 minutes (adjust as needed)
        set_time_limit(600);

        $validator = Validator::make($request->all(), [
            $fieldName => 'required|file|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file($fieldName);
            $headerStatus = $this->validateHeaders($file, $requiredHeaders);

            if (! $headerStatus['valid']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Header validation failed',
                    'missing_headers' => $headerStatus['missing'],
                    'extra_headers' => $headerStatus['extra'],
                    'required_headers' => $requiredHeaders,
                    'actual_headers' => $headerStatus['actual'],
                ], 422);
            }

            // Create import instance
            $import = new DataValidationImport($fileType);

            // Process with read-only optimization
            Excel::import($import, $file, null, \Maatwebsite\Excel\Excel::XLSX, [
                'readOnly' => true,
            ]);

            // Get results
            $issues = $import->getIssues();
            $patientNumbers = $import->getPatientNumbers();
            $sampleRows = $import->getSampleRows();
            $recordCount = $import->getRecordCount();

            // ... rest of your response code ...
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'File validation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enhanced header validation with detailed results
     */
    private function validateHeaders($file, array $requiredHeaders)
    {
        try {
            $actualHeaders = (new HeadingRowImport)->toArray($file)[0][0];

            // Normalize headers
            $normalize = function ($header) {
                return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $header));
            };

            $normalizedRequired = array_map($normalize, $requiredHeaders);
            $normalizedActual = array_map($normalize, $actualHeaders);

            // Find missing and extra headers
            $missing = array_diff($normalizedRequired, $normalizedActual);
            $extra = array_diff($normalizedActual, $normalizedRequired);

            // Map back to original header names
            $originalMissing = [];
            foreach ($missing as $norm) {
                foreach ($requiredHeaders as $orig) {
                    if ($normalize($orig) === $norm) {
                        $originalMissing[] = $orig;
                        break;
                    }
                }
            }

            $originalExtra = [];
            foreach ($extra as $norm) {
                foreach ($actualHeaders as $orig) {
                    if ($normalize($orig) === $norm) {
                        $originalExtra[] = $orig;
                        break;
                    }
                }
            }

            return [
                'valid' => empty($missing),
                'missing' => $originalMissing,
                'extra' => $originalExtra,
                'actual' => $actualHeaders,
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract patient numbers from data array
     */
    private function extractPatientNumbers(array $data, array $header)
    {
        $patientNumberIndex = array_search('PatientNumber', $header);

        if ($patientNumberIndex === false) {
            return [];
        }

        $patientNumbers = [];
        foreach ($data as $row) {
            if (isset($row[$patientNumberIndex]) && ! empty(trim($row[$patientNumberIndex]))) {
                $patientNumbers[] = trim($row[$patientNumberIndex]);
            }
        }

        // Return unique patient numbers
        return array_unique($patientNumbers);
    }

    /**
     * Extract patient numbers from array data (for cross-validation)
     */
    private function extractPatientNumbersFromArray(array $data)
    {
        $patientNumbers = [];
        foreach ($data as $row) {
            if (isset($row['PatientNumber']) && ! empty(trim($row['PatientNumber']))) {
                $patientNumbers[] = trim($row['PatientNumber']);
            }
        }

        return array_unique($patientNumbers);
    }

    /**
     * Find data quality issues
     */
    private function findDataIssues(array $data, array $header, string $fileType)
    {
        $issues = [];

        // Find blank PatientNumbers
        $patientNumberIndex = array_search('PatientNumber', $header);
        if ($patientNumberIndex !== false) {
            $blankPatientNumbers = [];
            foreach ($data as $index => $row) {
                if (empty(trim($row[$patientNumberIndex] ?? ''))) {
                    $blankPatientNumbers[] = $index + 2; // +2 because header row + 1-indexed
                }
            }

            if (count($blankPatientNumbers) > 0) {
                $issues[] = [
                    'type' => 'blank_patient_number',
                    'message' => 'Rows with blank PatientNumber',
                    'count' => count($blankPatientNumbers),
                    'example_rows' => array_slice($blankPatientNumbers, 0, 10),
                ];
            }
        }

        // Date validation for patients file
        if ($fileType === 'patients') {
            $dateFields = ['DateBorn', 'StartARTDate', 'FirstPositiveHIVTest'];
            foreach ($dateFields as $field) {
                $index = array_search($field, $header);
                if ($index !== false) {
                    $invalidDates = [];
                    foreach ($data as $rowIndex => $row) {
                        if (! empty($row[$index]) && ! $this->isValidDate($row[$index])) {
                            $invalidDates[] = [
                                'row' => $rowIndex + 2,
                                'value' => $row[$index],
                            ];
                        }
                    }

                    if (count($invalidDates) > 0) {
                        $issues[] = [
                            'type' => 'invalid_date_format',
                            'message' => "Invalid date format for $field",
                            'count' => count($invalidDates),
                            'examples' => array_slice($invalidDates, 0, 5),
                        ];
                    }
                }
            }
        }

        // Numeric field validation for visits file
        if ($fileType === 'visits') {
            $numericFields = ['Weight', 'HeightChildren', 'BMI', 'CD4', 'ViralLoad', 'TLC', 'CD4percent'];
            foreach ($numericFields as $field) {
                $index = array_search($field, $header);
                if ($index !== false) {
                    $nonNumeric = [];
                    foreach ($data as $rowIndex => $row) {
                        if (! empty($row[$index]) && ! is_numeric($row[$index])) {
                            $nonNumeric[] = [
                                'row' => $rowIndex + 2,
                                'value' => $row[$index],
                            ];
                        }
                    }

                    if (count($nonNumeric) > 0) {
                        $issues[] = [
                            'type' => 'non_numeric_value',
                            'message' => "Non-numeric values for $field",
                            'count' => count($nonNumeric),
                            'examples' => array_slice($nonNumeric, 0, 5),
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Validate date format
     */
    private function isValidDate($date)
    {
        if (empty($date)) {
            return true;
        }
        if (is_numeric($date)) {
            return true;
        } // Excel date serial number

        // Try different date formats
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d && $d->format($format) === $date) {
                return true;
            }
        }

        return false;
    }
}
