<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DataValidationImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private $fileType;

    private $issues = [];

    private $patientNumbers = [];

    private $sampleRows = [];

    private $recordCount = 0;

    private $startTime;

    private $lastProgressTime;

    public function __construct($fileType)
    {
        $this->fileType = $fileType;
        $this->startTime = microtime(true);
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                // Optional: Add progress monitoring
                $this->lastProgressTime = microtime(true);
            },
        ];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->recordCount++;
            $rowNumber = $this->recordCount + 1; // +1 for header row

            // Add progress monitoring every 1000 rows
            if ($this->recordCount % 1000 === 0) {
                $currentTime = microtime(true);
                $elapsed = $currentTime - $this->lastProgressTime;

                // Reset time tracking
                $this->lastProgressTime = $currentTime;
            }

            // Collect patient numbers
            if (! empty($row['PatientNumber'])) {
                $pn = trim($row['PatientNumber']);
                $this->patientNumbers[$pn] = true; // Using keys for deduplication
            }

            // Check for blank PatientNumber
            if (empty($row['PatientNumber'])) {
                $this->collectIssue('blank_patient_number', $rowNumber);
            }

            // Validate dates for patients file
            if ($this->fileType === 'patients') {
                $this->validateDates($row, $rowNumber);
            }

            // Validate numeric fields for visits file
            if ($this->fileType === 'visits') {
                $this->validateNumerics($row, $rowNumber);
            }

            // Collect sample rows (first 5)
            if ($this->recordCount <= 5) {
                $this->sampleRows[] = $row;
            }
        }
    }

    private function collectIssue($type, $rowNumber, $field = null, $value = null)
    {
        if (! isset($this->issues[$type])) {
            $this->issues[$type] = [
                'count' => 0,
                'examples' => [],
                'field' => $field,
            ];
        }

        $this->issues[$type]['count']++;

        if (count($this->issues[$type]['examples']) < 10) {
            $example = ['row' => $rowNumber];
            if ($value !== null) {
                $example['value'] = $value;
            }
            $this->issues[$type]['examples'][] = $example;
        }
    }

    private function validateDates($row, $rowNumber)
    {
        $dateFields = ['DateBorn', 'StartARTDate', 'FirstPositiveHIVTest'];
        foreach ($dateFields as $field) {
            if (! empty($row[$field]) && ! $this->isValidDate($row[$field])) {
                $this->collectIssue('invalid_date', $rowNumber, $field, $row[$field]);
            }
        }
    }

    private function validateNumerics($row, $rowNumber)
    {
        $numericFields = ['Weight', 'HeightChildren', 'BMI', 'CD4', 'ViralLoad', 'TLC', 'CD4percent'];
        foreach ($numericFields as $field) {
            if (! empty($row[$field]) && ! is_numeric($row[$field])) {
                $this->collectIssue('non_numeric', $rowNumber, $field, $row[$field]);
            }
        }
    }

    private function isValidDate($date)
    {
        if (empty($date) || is_numeric($date)) {
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

    public function chunkSize(): int
    {
        return 1000;
    }

    // Getters for results
    public function getIssues()
    {
        return $this->issues;
    }

    public function getPatientNumbers()
    {
        return array_keys($this->patientNumbers);
    }

    public function getSampleRows()
    {
        return $this->sampleRows;
    }

    public function getRecordCount()
    {
        return $this->recordCount;
    }
}
