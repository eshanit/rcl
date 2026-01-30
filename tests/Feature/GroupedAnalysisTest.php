<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupedAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $cohort;
    protected $site;
    protected $facility;
    protected $patients;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole('researcher'); // Can access aggregated data

        // Create test hierarchy
        $this->cohort = Cohort::factory()->create();
        $this->site = Site::factory()->create(['cohort_id' => $this->cohort->id]);
        $this->facility = Facility::factory()->create(['site_id' => $this->site->id]);

        // Create test patients and visits
        $this->patients = Patient::factory()->count(10)->create([
            'site_id' => $this->site->id
        ]);

        foreach ($this->patients as $patient) {
            Visit::factory()->count(3)->create([
                'patient_id' => $patient->id,
                'facility_id' => $this->facility->id,
            ]);
        }
    }

    /** @test */
    public function it_returns_grouped_analysis_data()
    {
        $this->actingAs($this->user);

        $response = $this->get('/reports/grouped-analysis');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'summary' => [
                        'total_patients',
                        'active_patients',
                        'total_visits',
                        'average_age'
                    ],
                    'retention' => [
                        'rates' => [],
                        'ltfu_count',
                        'reengaged_count'
                    ],
                    'deaths' => [
                        'total_deaths',
                        'death_rate'
                    ]
                ]);
    }

    /** @test */
    public function it_filters_by_cohort()
    {
        $this->actingAs($this->user);

        $response = $this->get("/reports/grouped-analysis?cohort_id={$this->cohort->id}");

        $response->assertStatus(200);

        // Verify the response contains data filtered by cohort
        $data = $response->json();
        $this->assertGreaterThan(0, $data['summary']['total_patients']);
    }

    /** @test */
    public function it_filters_by_site()
    {
        $this->actingAs($this->user);

        $response = $this->get("/reports/grouped-analysis?site_id={$this->site->id}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals(10, $data['summary']['total_patients']); // Our test data
    }

    /** @test */
    public function it_filters_by_facility()
    {
        $this->actingAs($this->user);

        $response = $this->get("/reports/grouped-analysis?facility_id={$this->facility->id}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals(10, $data['summary']['total_patients']);
    }

    /** @test */
    public function it_filters_by_date_range()
    {
        $this->actingAs($this->user);

        $startDate = '2023-01-01';
        $endDate = '2023-12-31';

        $response = $this->get("/reports/grouped-analysis?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);

        // Should return data without errors
        $data = $response->json();
        $this->assertIsArray($data);
    }

    /** @test */
    public function it_calculates_retention_rates_correctly()
    {
        $this->actingAs($this->user);

        $response = $this->get('/reports/grouped-analysis');

        $response->assertStatus(200);

        $data = $response->json();

        // Verify retention rates structure
        $this->assertArrayHasKey('rates', $data['retention']);
        $this->assertArrayHasKey('ltfu_count', $data['retention']);
        $this->assertArrayHasKey('reengaged_count', $data['retention']);

        // Retention rates should be numeric
        foreach ($data['retention']['rates'] as $rate) {
            $this->assertIsNumeric($rate);
            $this->assertGreaterThanOrEqual(0, $rate);
            $this->assertLessThanOrEqual(100, $rate);
        }
    }

    /** @test */
    public function it_calculates_mortality_statistics()
    {
        $this->actingAs($this->user);

        $response = $this->get('/reports/grouped-analysis');

        $response->assertStatus(200);

        $data = $response->json();

        // Verify mortality data structure
        $this->assertArrayHasKey('total_deaths', $data['deaths']);
        $this->assertArrayHasKey('death_rate', $data['deaths']);
        $this->assertArrayHasKey('avg_age_at_death', $data['deaths']);

        // Values should be numeric or null
        $this->assertTrue(
            is_numeric($data['deaths']['total_deaths']) ||
            is_null($data['deaths']['total_deaths'])
        );
    }

    /** @test */
    public function it_provides_filter_options()
    {
        $this->actingAs($this->user);

        $response = $this->get('/reports/grouped-analysis');

        $response->assertStatus(200);

        $data = $response->json();

        // Should include filter options
        $this->assertArrayHasKey('cohorts', $data);
        $this->assertArrayHasKey('sites', $data);
        $this->assertArrayHasKey('facilities', $data);

        // Cohorts should include our test cohort
        $cohortIds = array_column($data['cohorts'], 'id');
        $this->assertContains($this->cohort->id, $cohortIds);
    }

    /** @test */
    public function it_handles_empty_filters_gracefully()
    {
        $this->actingAs($this->user);

        // Create a cohort with no patients
        $emptyCohort = Cohort::factory()->create();

        $response = $this->get("/reports/grouped-analysis?cohort_id={$emptyCohort->id}");

        $response->assertStatus(200);

        $data = $response->json();

        // Should return zero values, not errors
        $this->assertEquals(0, $data['summary']['total_patients']);
        $this->assertIsArray($data['retention']['rates']);
    }

    /** @test */
    public function it_caches_filtered_results()
    {
        $this->actingAs($this->user);

        // First request
        $response1 = $this->get("/reports/grouped-analysis?cohort_id={$this->cohort->id}");
        $response1->assertStatus(200);

        // Second request with same filters should be fast (cached)
        $startTime = microtime(true);
        $response2 = $this->get("/reports/grouped-analysis?cohort_id={$this->cohort->id}");
        $endTime = microtime(true);

        $response2->assertStatus(200);

        // Should be very fast if cached (< 0.1 seconds)
        $this->assertLessThan(0.1, $endTime - $startTime);
    }

    /** @test */
    public function it_validates_filter_parameters()
    {
        $this->actingAs($this->user);

        // Invalid cohort ID
        $response = $this->get('/reports/grouped-analysis?cohort_id=invalid');

        $response->assertStatus(200); // Should handle gracefully, not crash
    }

    /** @test */
    public function it_provides_demographic_breakdown()
    {
        $this->actingAs($this->user);

        $response = $this->get('/reports/grouped-analysis');

        $response->assertStatus(200);

        $data = $response->json();

        // Should include demographic data
        $this->assertArrayHasKey('demographics', $data);

        // Demographics should include age and gender distributions
        $this->assertArrayHasKey('age_distribution', $data['demographics']);
        $this->assertArrayHasKey('gender_distribution', $data['demographics']);
    }
}