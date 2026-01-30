<?php

namespace Tests\Feature;

use App\Http\Middleware\HealthcareDataValidation;
use App\Models\Patient;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthcareDataValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new HealthcareDataValidation();
    }

    /** @test */
    public function it_allows_valid_patient_data()
    {
        $request = Request::create('/upload/patients', 'POST', [
            'patients' => [
                [
                    'p_number' => 'P001',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'M',
                    'height' => 170,
                    'site_id' => 1,
                ]
            ]
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_negative_height()
    {
        $request = Request::create('/upload/patients', 'POST', [
            'patients' => [
                [
                    'p_number' => 'P001',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'M',
                    'height' => -10,
                    'site_id' => 1,
                ]
            ]
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_rejects_future_birth_dates()
    {
        $request = Request::create('/upload/patients', 'POST', [
            'patients' => [
                [
                    'p_number' => 'P001',
                    'date_of_birth' => '2030-01-01',
                    'gender' => 'M',
                    'height' => 170,
                    'site_id' => 1,
                ]
            ]
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_validates_art_start_after_birth()
    {
        $request = Request::create('/upload/initial-visits', 'POST', [
            'initial_visits' => [
                [
                    'patient_id' => 1,
                    'art_start_date' => '1985-01-01', // Before birth
                    'first_positive_hiv' => '1986-01-01',
                ]
            ]
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_validates_hiv_test_before_art_start()
    {
        $request = Request::create('/upload/initial-visits', 'POST', [
            'initial_visits' => [
                [
                    'patient_id' => 1,
                    'art_start_date' => '1990-01-01',
                    'first_positive_hiv' => '1995-01-01', // After ART start
                ]
            ]
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_rejects_invalid_file_types()
    {
        Storage::fake('local');

        $request = Request::create('/upload/patients', 'POST', [], [], [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('test.exe', 100)
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_allows_valid_excel_files()
    {
        Storage::fake('local');

        $request = Request::create('/upload/patients', 'POST', [], [], [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('patients.xlsx', 100)
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_files_too_large()
    {
        Storage::fake('local');

        $request = Request::create('/upload/patients', 'POST', [], [], [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('large.xlsx', 100000) // 100MB
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_detects_pii_in_urls()
    {
        $request = Request::create('/patients/1234567890123456', 'GET'); // Looks like SSN

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_validates_medical_measurements()
    {
        $request = Request::create('/upload/visit-details', 'POST', [
            'visit_details' => [
                [
                    'visit_id' => 1,
                    'weight' => 300, // Unrealistic weight
                    'cd4' => -50, // Negative CD4
                    'viral_load' => 10000000, // Unrealistic viral load
                ]
            ]
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_prevents_duplicate_patient_numbers_in_same_request()
    {
        $request = Request::create('/upload/patients', 'POST', [
            'patients' => [
                [
                    'p_number' => 'P001',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'M',
                    'site_id' => 1,
                ],
                [
                    'p_number' => 'P001', // Duplicate
                    'date_of_birth' => '1991-01-01',
                    'gender' => 'F',
                    'site_id' => 1,
                ]
            ]
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }
}