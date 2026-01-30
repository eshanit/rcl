<?php

namespace Tests\Feature;

use App\Imports\DataValidationImport;
use App\Models\Patient;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataImportTest extends TestCase
{
    use RefreshDatabase;

    protected $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = Site::factory()->create();
    }

    /** @test */
    public function it_imports_valid_patient_data()
    {
        Storage::fake('local');

        // Create a fake Excel file with valid patient data
        $file = UploadedFile::fake()->create('patients.xlsx');

        $data = [
            ['p_number', 'date_of_birth', 'gender', 'height', 'site_id'],
            ['P001', '1990-01-01', 'M', '170', $this->site->id],
            ['P002', '1985-05-15', 'F', '165', $this->site->id],
        ];

        // Mock the Excel import
        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        // Verify no issues were found
        $this->assertEmpty($import->getIssues());
        $this->assertEquals(2, $import->getRecordCount());
    }

    /** @test */
    public function it_detects_duplicate_patient_numbers()
    {
        Storage::fake('local');

        $data = [
            ['p_number', 'date_of_birth', 'gender', 'height', 'site_id'],
            ['P001', '1990-01-01', 'M', '170', $this->site->id],
            ['P001', '1985-05-15', 'F', '165', $this->site->id], // Duplicate
        ];

        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertNotEmpty($issues);
        $this->assertStringContains('Duplicate patient number', $issues[0]);
    }

    /** @test */
    public function it_validates_patient_height_range()
    {
        Storage::fake('local');

        $data = [
            ['p_number', 'date_of_birth', 'gender', 'height', 'site_id'],
            ['P001', '1990-01-01', 'M', '300', $this->site->id], // Too tall
            ['P002', '1985-05-15', 'F', '-10', $this->site->id], // Negative
        ];

        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertCount(2, $issues);
        $this->assertStringContains('Invalid height', $issues[0]);
        $this->assertStringContains('Invalid height', $issues[1]);
    }

    /** @test */
    public function it_validates_date_formats()
    {
        Storage::fake('local');

        $data = [
            ['p_number', 'date_of_birth', 'gender', 'height', 'site_id'],
            ['P001', '1990-13-45', 'M', '170', $this->site->id], // Invalid date
            ['P002', 'not-a-date', 'F', '165', $this->site->id], // Not a date
        ];

        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertCount(2, $issues);
        $this->assertStringContains('Invalid date format', $issues[0]);
    }

    /** @test */
    public function it_validates_medical_measurements_in_visit_details()
    {
        Storage::fake('local');

        $data = [
            ['visit_id', 'weight', 'cd4', 'viral_load', 'tlc'],
            [1, '300', '1500', '1000000', '5000'], // Unrealistic weight
            [2, '60', '-50', '50000', '2000'], // Negative CD4
        ];

        $import = new DataValidationImport('visit_details');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertNotEmpty($issues);
        $this->assertStringContains('Unrealistic weight', $issues[0]);
        $this->assertStringContains('Invalid CD4 count', $issues[1]);
    }

    /** @test */
    public function it_validates_art_start_date_logic()
    {
        Storage::fake('local');

        $data = [
            ['patient_id', 'art_start_date', 'first_positive_hiv'],
            [1, '1980-01-01', '1990-01-01'], // ART before HIV diagnosis
            [2, '1990-01-01', '1985-01-01'], // Valid
        ];

        $import = new DataValidationImport('initial_visits');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertCount(1, $issues);
        $this->assertStringContains('ART start date before HIV diagnosis', $issues[0]);
    }

    /** @test */
    public function it_detects_missing_required_fields()
    {
        Storage::fake('local');

        $data = [
            ['p_number', 'date_of_birth', 'gender', 'height', 'site_id'],
            ['', '1990-01-01', 'M', '170', $this->site->id], // Missing p_number
            ['P002', '', 'F', '165', $this->site->id], // Missing date_of_birth
        ];

        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertCount(2, $issues);
        $this->assertStringContains('Missing required field', $issues[0]);
    }

    /** @test */
    public function it_validates_gender_values()
    {
        Storage::fake('local');

        $data = [
            ['p_number', 'date_of_birth', 'gender', 'height', 'site_id'],
            ['P001', '1990-01-01', 'X', '170', $this->site->id], // Invalid gender
            ['P002', '1985-05-15', 'Male', '165', $this->site->id], // Wrong format
        ];

        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertCount(2, $issues);
        $this->assertStringContains('Invalid gender', $issues[0]);
    }

    /** @test */
    public function it_handles_large_datasets_efficiently()
    {
        Storage::fake('local');

        // Create a large dataset
        $data = [['p_number', 'date_of_birth', 'gender', 'height', 'site_id']];

        for ($i = 1; $i <= 1000; $i++) {
            $data[] = [
                "P{$i}",
                '1990-01-01',
                'M',
                '170',
                $this->site->id
            ];
        }

        $startTime = microtime(true);

        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        $endTime = microtime(true);
        $processingTime = $endTime - $startTime;

        // Should process 1000 records in reasonable time (< 5 seconds)
        $this->assertLessThan(5, $processingTime);
        $this->assertEquals(1000, $import->getRecordCount());
    }

    /** @test */
    public function it_provides_sample_rows_for_error_context()
    {
        Storage::fake('local');

        $data = [
            ['p_number', 'date_of_birth', 'gender', 'height', 'site_id'],
            ['P001', 'invalid-date', 'M', '170', $this->site->id],
            ['P002', '1990-01-01', 'M', '170', $this->site->id],
            ['P003', 'also-invalid', 'F', '165', $this->site->id],
        ];

        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        $sampleRows = $import->getSampleRows();
        $this->assertNotEmpty($sampleRows);
        $this->assertCount(2, $sampleRows); // Should show first few error rows
    }

    /** @test */
    public function it_validates_who_stage_values()
    {
        Storage::fake('local');

        $data = [
            ['patient_id', 'who_stage', 'diagnosis_1'],
            [1, 5, null], // Invalid WHO stage
            [2, 0, null], // Invalid WHO stage
            [3, 3, null], // Valid
        ];

        $import = new DataValidationImport('initial_visits');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertCount(2, $issues);
        $this->assertStringContains('Invalid WHO stage', $issues[0]);
    }

    /** @test */
    public function it_detects_potential_pii_in_data()
    {
        Storage::fake('local');

        $data = [
            ['p_number', 'date_of_birth', 'gender', 'height', 'site_id'],
            ['P001', '1990-01-01', 'M', '170', $this->site->id],
            ['1234567890123456', '1985-05-15', 'F', '165', $this->site->id], // Looks like SSN
        ];

        $import = new DataValidationImport('patients');
        $collection = collect($data);

        $import->collection($collection);

        $issues = $import->getIssues();
        $this->assertNotEmpty($issues);
        $this->assertStringContains('Potential PII detected', $issues[0]);
    }
}