<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\LazyCollection;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;

class UploadAndValidateDataController extends Controller
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

        $result = $this->validateFile($request, 'patients_file', $this->patientHeaders, 'patients');

        $file = $request->file('patients_file');
        $filePath = $file->store('temp-uploads');

        // Store absolute path in session
        session(['patients_file_path' => storage_path('app/public/'.$filePath)]);
        session()->forget('cross_validation');
        // Store in session
        session(['patients_validation' => $result]);

        return $result;

    }

    /**
     * Upload and validate visits file
     */
    public function uploadVisits(Request $request)
    {
        if (! $request->hasFile('visits_file')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file was uploaded.',
            ], 422);
        }

        $result = $this->validateFile($request, 'visits_file', $this->visitsHeaders, 'visits');

        $file = $request->file('visits_file');
        $filePath = $file->store('temp-uploads');

        // Store absolute path in session
        session(['visits_file_path' => storage_path('app/public/'.$filePath)]);

        session()->forget('cross_validation');
        // Store in session
        session(['visits_validation' => $result]);

        return $result;
    }

    /**
     * Validate cross-file consistency
     */
    public function validateFiles(Request $request)
    {

        // dd($request->all());

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
            // $patientNumbers = $this->extractPatientNumbersFromArray($request->patients_data);
            // $visitsPatientNumbers = $this->extractPatientNumbersFromArray($request->visits_data);

            $patientNumbers = $request->patients_data;
            $visitsPatientNumbers = $request->visits_data;

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

            $response = [
                'status' => count($issues) ? 'warning' : 'success',
                'message' => count($issues)
                    ? 'Cross-file validation completed with issues'
                    : 'Cross-file validation successful',
                'orphaned_visits_count' => count($orphanedNumbers),
                'patients_without_visits_count' => count($patientsWithoutVisits),
                'issues' => $issues,
                'stats' => [
                    'total_patients' => count($patientNumbers),
                    'total_visits' => '-',
                    'unique_patient_visits' => count($visitsPatientNumbers),
                ],
            ];

            // Store in session
            session(['cross_validation' => $response]);

            return Inertia::render('uploads/PatientData', [
                'validationResult' => $response,
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
     * Header validation using FastExcel
     */
    private function validateHeaders($filePath, array $requiredHeaders)
    {
        try {
            // Get first row only for headers
            $normalizedPath = str_replace('\\', '/', $filePath);
            $actualHeaders = array_keys((new FastExcel)->import($normalizedPath)->first() ?: []);

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
                'valid' => empty($originalMissing),
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

    // Helper methods for issue collection
    private function collectIssue(array &$issues, string $type, int $rowNumber, ?string $field = null, $value = null)
    {
        if (! isset($issues[$type])) {
            $issues[$type] = [
                'count' => 0,
                'examples' => [],
                'field' => $field,
            ];
        }

        $issues[$type]['count']++;

        if (count($issues[$type]['examples']) < 10) {
            $example = ['row' => $rowNumber];
            if ($value !== null) {
                $example['value'] = $value;
            }
            $issues[$type]['examples'][] = $example;
        }
    }

    private function validateDates(array $row, int $rowNumber, array &$issues)
    {
        $dateFields = ['DateBorn', 'StartARTDate', 'FirstPositiveHIVTest'];
        foreach ($dateFields as $field) {
            if (! empty($row[$field]) && ! $this->isValidDate($row[$field])) {
                $this->collectIssue($issues, 'invalid_date', $rowNumber, $field, $row[$field]);
            }
        }
    }

    private function validateNumerics(array $row, int $rowNumber, array &$issues)
    {
        $numericFields = ['Weight', 'HeightChildren', 'BMI', 'CD4', 'ViralLoad', 'TLC', 'CD4percent'];
        foreach ($numericFields as $field) {
            if (! empty($row[$field]) && ! is_numeric((float) $row[$field]) && strtoupper($row[$field]) !== 'NA') {
                $this->collectIssue($issues, 'non_numeric', $rowNumber, $field, $row[$field]);
            }
        }
    }

    private function isValidDate($date)
    {
        if (empty($date) || is_numeric($date)) {
            return true;
        }

        // If already a DateTime or DateTimeImmutable, consider it valid
        if ($date instanceof \DateTimeInterface) {
            return true;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d && $d->format($format) === $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format issues for response
     */
    private function formatIssues(array $issues): array
    {
        $formatted = [];
        foreach ($issues as $type => $data) {
            $issue = ['type' => $type, 'count' => $data['count']];

            switch ($type) {
                case 'blank_patient_number':
                    $issue['message'] = 'Rows with blank PatientNumber';
                    $issue['example_rows'] = array_column($data['examples'], 'row');
                    break;

                case 'invalid_date':
                    $issue['message'] = "Invalid date format for {$data['field']}";
                    $issue['examples'] = $data['examples'];
                    break;

                case 'non_numeric':
                    $issue['message'] = "Non-numeric values for {$data['field']}";
                    $issue['examples'] = $data['examples'];
                    break;
            }

            $formatted[] = $issue;
        }

        return $formatted;
    }

    /**
     * Generic file validation method
     */
    private function validateFile(Request $request, $fieldName, $requiredHeaders, $fileType)
    {
        // Increase time limit to 15 minutes as a safety net
        set_time_limit(900);

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
            $filePath = $file->store('temp-uploads');
            $fullPath = Storage::path($filePath);

            // Step 1: Validate headers first
            $headerStatus = $this->validateHeaders($fullPath, $requiredHeaders);

            if (! $headerStatus['valid']) {
                Storage::delete($filePath);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Header validation failed',
                    'missing_headers' => $headerStatus['missing'] ?? [],
                    'extra_headers' => $headerStatus['extra'] ?? [],
                    'required_headers' => $requiredHeaders,
                    'actual_headers' => $headerStatus['actual'] ?? [],
                    'header_error' => $headerStatus['error'] ?? null,
                ], 422);
            }

            // Step 2: Process data efficiently
            $result = $this->processLargeFile($fullPath, $fileType);

            // Clean up temporary file
            Storage::delete($filePath);

            return Inertia::render('uploads/PatientData', [
                'validationResult' => [
                    'status' => ! empty($result['issues']) ? 'warning' : 'success',
                    'message' => ! empty($result['issues'])
                        ? ucfirst($fileType).' file validated with issues'
                        : ucfirst($fileType).' file validated successfully',
                    'file_type' => $fileType,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'record_count' => $result['record_count'],
                    'unique_patient_numbers' => count($result['patient_numbers']),
                    'issues' => $result['issues'],
                    'data_sample' => $result['sample_rows'],
                    'headers' => $headerStatus['actual'],
                    'patient_numbers' => $result['patient_numbers'],
                ],
            ]);

        } catch (\Exception $e) {
            // Clean up if file was stored
            if (isset($filePath) && Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'File validation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process large Excel files efficiently
     */
    private function processLargeFile($filePath, $fileType)
    {
        $issues = [];
        $patientNumbers = [];
        $sampleRows = [];
        $recordCount = 0;
        $startTime = microtime(true);

        // Use LazyCollection for memory-efficient processing
        LazyCollection::make(function () use ($filePath) {
            $normalizedPath = str_replace('\\', '/', $filePath);

            return (new FastExcel)->import($normalizedPath);
        })->each(function ($row) use ($fileType, &$issues, &$patientNumbers, &$sampleRows, &$recordCount, $startTime) {
            $recordCount++;
            $rowNumber = $recordCount + 1; // +1 for header row

            // Collect patient numbers
            if (! empty($row['PatientNumber'])) {
                $pn = trim($row['PatientNumber']);
                $patientNumbers[$pn] = true;
            }

            // Check for blank PatientNumber
            if (empty($row['PatientNumber'])) {
                $this->collectIssue($issues, 'blank_patient_number', $rowNumber);
            }

            // Validate dates for patients file
            if ($fileType === 'patients') {
                $this->validateDates($row, $rowNumber, $issues);
            }

            // Validate numeric fields for visits file
            if ($fileType === 'visits') {
                $this->validateNumerics($row, $rowNumber, $issues);
            }

            // Collect sample rows (first 5)
            if ($recordCount <= 5) {
                $sampleRows[] = $row;
            }

            // Progress reporting every 5000 rows
            if ($recordCount % 5000 === 0) {
                $elapsed = microtime(true) - $startTime;
                error_log("Processed $recordCount rows in {$elapsed} seconds");
            }
        });

        return [
            'issues' => $this->formatIssues($issues),
            'patient_numbers' => array_keys($patientNumbers),
            'sample_rows' => $sampleRows,
            'record_count' => $recordCount,
        ];
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
}
