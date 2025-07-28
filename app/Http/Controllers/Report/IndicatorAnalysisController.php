<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\InitialVisit;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\VisitDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IndicatorAnalysisController extends Controller
{
    // Define age groups in months
    const AGE_GROUPS = [
        '≤2 months' => [0, 2],
        '3-12 months' => [3, 12],
        '13-24 months' => [13, 24],
        '25-59 months' => [25, 59],
        '5-9 years' => [60, 119],
        '10-14 years' => [120, 179],
        '15-19 years' => [180, 239],
        '20-24 years' => [240, 299],
        '25-29 years' => [300, 359],
        '30-34 years' => [360, 419],
        '35-39 years' => [420, 479],
        '40-44 years' => [480, 539],
        '45-49 years' => [540, 599],
        '50+ years' => [600, null],
    ];

    public function index()
    {
        return Inertia::render('report/IndicatorAnalysis', [
            'cohorts' => Cohort::all(),
        ]);
    }

    // Main analysis endpoint
    public function analyze(Request $request, $indicator)
    {
        // Get optional filters
        $cohortId = $request->input('cohort_id');
        $siteId = $request->input('site_id');
        $facilityId = $request->input('facility_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $method = 'calculate'.str_replace(' ', '', $indicator);

        if (method_exists($this, $method)) {
            $report = $this->$method($cohortId, $siteId, $facilityId, $startDate, $endDate);
            
            return Inertia::render('report/IndicatorAnalysis', [
                'indicator' => $indicator,
                'report' => $report,
                'filters' => [
                    'cohort_id' => $cohortId,
                    'site_id' => $siteId,
                    'facility_id' => $facilityId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'cohorts' => Cohort::all(),
            ]);
        }

        return Inertia::render('report/IndicatorAnalysis', [
            'error' => 'Invalid indicator',
            'cohorts' => Cohort::all(),
        ]);
    }

    // Helper: Calculate age group
    protected function getAgeGroup($birthDate, $referenceDate = null)
    {
        if (! $birthDate) {
            return 'Unknown';
        }

        // Parse the birth date if it's a string
        $birthDate = is_string($birthDate) ? Carbon::parse($birthDate) : $birthDate;
        $reference = $referenceDate ? Carbon::parse($referenceDate) : now();
        $ageInMonths = $birthDate->diffInMonths($reference);

        foreach (self::AGE_GROUPS as $group => $range) {
            [$min, $max] = $range;
            if ($ageInMonths >= $min && ($max === null || $ageInMonths <= $max)) {
                return $group;
            }
        }

        return 'Unknown';
    }

    // Helper: Map gender to standardized values
    protected function mapGender($gender)
    {
        $gender = strtolower($gender);
        if (in_array($gender, ['male', 'm'])) {
            return 'male';
        }
        if (in_array($gender, ['female', 'f'])) {
            return 'female';
        }

        return 'other';
    }

    // 1. Total patients ever enrolled on ART
    protected function calculateTotalPatientsEverEnrolled($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $query = Patient::query();

        // Apply filters
        $this->applyFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        $patients = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($patients as $patient) {
            $ageGroup = $this->getAgeGroup($patient->date_of_birth);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Total patients ever enrolled on ART',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // 2. Patients newly initiated on ART
    protected function calculateNewlyInitiatedOnART($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $query = InitialVisit::with('patient')
            ->whereNotNull('art_start_date');

        // Apply date filters
        if ($startDate) {
            $query->where('art_start_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('art_start_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyInitialVisitFilters($query, $cohortId, $siteId, $facilityId);

        $initiations = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($initiations as $initiation) {
            $patient = $initiation->patient;
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $initiation->art_start_date);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients newly initiated on ART',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // 3. Patients retained after 12 months
    protected function calculatePatientsRetainedAfter12Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Patients who started ART at least 13 months ago
        $startDateThreshold = now()->subMonths(13);

        $query = InitialVisit::with('patient')
            ->where('art_start_date', '<=', $startDateThreshold)
            ->whereHas('patient.visits', function ($query) use ($startDateThreshold) {
                $query->where('actual_visit_date', '>=', $startDateThreshold);
            });

        // Apply date filters
        if ($startDate) {
            $query->where('art_start_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('art_start_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyInitialVisitFilters($query, $cohortId, $siteId, $facilityId);

        $retainedPatients = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($retainedPatients as $initiation) {
            $patient = $initiation->patient;
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $initiation->art_start_date);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients retained after 12 months',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // 4. Patients with suppressed viral load
    protected function calculatePatientsWithSuppressedViralLoad($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $query = VisitDetail::where('viral_load', '<', 1000)
            ->whereHas('visit', function ($query) use ($startDate, $endDate) {
                if ($startDate) {
                    $query->where('actual_visit_date', '>=', $startDate);
                }
                if ($endDate) {
                    $query->where('actual_visit_date', '<=', $endDate);
                }
            })
            ->with(['visit.patient']);

        // Apply other filters
        $this->applyVisitDetailFilters($query, $cohortId, $siteId, $facilityId);

        $suppressedVisits = $query->get();

        $results = $this->initializeResults();
        $total = 0;
        $uniquePatients = [];

        foreach ($suppressedVisits as $visitDetail) {
            $patient = $visitDetail->visit->patient;

            // Count unique patients
            if (in_array($patient->id, $uniquePatients)) {
                continue;
            }
            $uniquePatients[] = $patient->id;

            $ageGroup = $this->getAgeGroup($patient->date_of_birth);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients with suppressed viral load (<1000 copies)',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // 5. Patients screened for TB annually
    protected function calculatePatientsScreenedForTB($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Use database aggregation to get unique patients per year
        $query = VisitDetail::join('visits', 'visit_details.visit_id', '=', 'visits.id')
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->whereNotNull('visit_details.tb_status_id')
            ->select(
                'patients.id as patient_id',
                'patients.date_of_birth',
                'patients.gender',
                DB::raw('YEAR(visit_details.created_at) as year')
            )
            ->groupBy('patients.id', 'patients.date_of_birth', 'patients.gender', 'year');

        // Apply date filters
        if ($startDate) {
            $query->where('visits.actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('visits.actual_visit_date', '<=', $endDate);
        }

        // Apply other filters
        if ($cohortId) {
            $query->join('sites', 'patients.site_id', '=', 'sites.id')
                ->where('sites.cohort_id', $cohortId);
        }

        if ($siteId) {
            $query->where('patients.site_id', $siteId);
        }

        if ($facilityId) {
            $query->where('visits.facility_id', $facilityId);
        }

        $screenedPatients = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($screenedPatients as $patient) {
            $ageGroup = $this->getAgeGroup($patient->date_of_birth);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients screened for TB annually',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // 6. Missed appointment visits percentage
    protected function calculateMissedAppointmentVisits($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $query = Visit::whereNotNull('app_visit_date')
            ->whereNotNull('actual_visit_date')
            ->whereColumn('app_visit_date', '!=', 'actual_visit_date');

        // Apply filters
        $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        $missedVisits = $query->count();

        $totalQuery = Visit::whereNotNull('app_visit_date');
        $this->applyVisitFilters($totalQuery, $cohortId, $siteId, $facilityId, $startDate, $endDate);
        $totalVisits = $totalQuery->count();

        $percentage = $totalVisits > 0 ? round(($missedVisits / $totalVisits) * 100, 2) : 0;

        return [
            'indicator' => 'Missed appointment visits',
            'missed_visits' => $missedVisits,
            'total_visits' => $totalVisits,
            'percentage' => $percentage,
        ];
    }

    // 7. Pregnant women retained after 12 months
    protected function calculatePregnantWomenRetainedAfter12Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Find visits where pregnancy was recorded
        $query = VisitDetail::where('pregnant', 'yes')
            ->with(['visit.patient']);

        // Apply filters
        $this->applyVisitDetailFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        $pregnancyVisits = $query->get();

        $results = $this->initializeResults();
        $totalPregnant = 0;
        $retainedCount = 0;

        foreach ($pregnancyVisits as $visitDetail) {
            $patient = $visitDetail->visit->patient;
            $visitDate = Carbon::parse($visitDetail->visit->actual_visit_date);

            // Check if patient was retained 12 months after pregnancy visit
            $isRetained = $patient->visits()
                ->where('actual_visit_date', '>=', $visitDate->copy()->addMonths(12))
                ->exists();

            if ($isRetained) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $visitDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $retainedCount++;
            }

            $totalPregnant++;
        }

        $percentage = $totalPregnant > 0 ? round(($retainedCount / $totalPregnant) * 100, 2) : 0;

        return [
            'indicator' => 'Pregnant women retained after 12 months',
            'retained' => $retainedCount,
            'total_pregnant' => $totalPregnant,
            'percentage' => $percentage,
            'breakdown' => $results,
        ];
    }

    // Helper: Initialize results structure
    protected function initializeResults()
    {
        $results = [];
        foreach (array_keys(self::AGE_GROUPS) as $group) {
            $results[$group] = [
                'male' => 0,
                'female' => 0,
                'other' => 0,
                'total' => 0,
            ];
        }
        $results['Unknown'] = [
            'male' => 0,
            'female' => 0,
            'other' => 0,
            'total' => 0,
        ];

        return $results;
    }

    // Helper: Apply filters to patient query
    protected function applyFilters($query, $cohortId, $siteId, $facilityId, $startDate = null, $endDate = null)
    {
        if ($cohortId) {
            $query->whereHas('site.cohort', function ($q) use ($cohortId) {
                $q->where('id', $cohortId);
            });
        }

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        if ($facilityId) {
            $query->whereHas('visits', function ($q) use ($facilityId) {
                $q->where('facility_id', $facilityId);
            });
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
    }

    // Helper: Apply filters to initial visit query
    protected function applyInitialVisitFilters($query, $cohortId, $siteId, $facilityId)
    {
        if ($cohortId) {
            $query->whereHas('patient.site.cohort', function ($q) use ($cohortId) {
                $q->where('id', $cohortId);
            });
        }

        if ($siteId) {
            $query->whereHas('patient', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        if ($facilityId) {
            // Not directly applicable to initial visits
        }
    }

    // Helper: Apply filters to visit query
    protected function applyVisitFilters($query, $cohortId, $siteId, $facilityId, $startDate = null, $endDate = null)
    {
        if ($cohortId) {
            $query->whereHas('patient.site.cohort', function ($q) use ($cohortId) {
                $q->where('id', $cohortId);
            });
        }

        if ($siteId) {
            $query->whereHas('patient', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        if ($facilityId) {
            $query->where('facility_id', $facilityId);
        }

        if ($startDate) {
            $query->where('actual_visit_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('actual_visit_date', '<=', $endDate);
        }
    }

    // Helper: Apply filters to visit detail query
    protected function applyVisitDetailFilters($query, $cohortId, $siteId, $facilityId, $startDate = null, $endDate = null)
    {
        if ($cohortId) {
            $query->whereHas('visit.patient.site.cohort', function ($q) use ($cohortId) {
                $q->where('id', $cohortId);
            });
        }

        if ($siteId) {
            $query->whereHas('visit.patient', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        if ($facilityId) {
            $query->whereHas('visit', function ($q) use ($facilityId) {
                $q->where('facility_id', $facilityId);
            });
        }

        if ($startDate) {
            $query->whereHas('visit', function ($q) use ($startDate) {
                $q->where('actual_visit_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('visit', function ($q) use ($endDate) {
                $q->where('actual_visit_date', '<=', $endDate);
            });
        }
    }

    // Helper: Apply date filter
    protected function applyDateFilter($query, $column, $startDate, $endDate)
    {
        if ($startDate) {
            $query->where($column, '>=', $startDate);
        }
        if ($endDate) {
            $query->where($column, '<=', $endDate);
        }
    }
}
