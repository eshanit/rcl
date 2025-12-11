<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Facility;
use App\Models\InitialVisit;
use App\Models\Patient;
use App\Models\Site;
use App\Models\Visit;
use App\Models\VisitDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    const CHILD_AGE_GROUPS = [
        '≤2 months' => [0, 2],
        '3-12 months' => [3, 12],
        '13-24 months' => [13, 24],
        '25-59 months' => [25, 59],
        '5-9 years' => [60, 119],
        '10-14 years' => [120, 179],
        '15-18 years' => [180, 216],
    ];

    public function index()
    {
        return Inertia::render('report/IndicatorAnalysis', [
            'cohorts' => Cohort::all(),
            'sites' => \App\Models\Site::all(),
            'facilities' => \App\Models\Facility::all(),
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
                'sites' => \App\Models\Site::all(),
                'facilities' => \App\Models\Facility::all(),
            ]);
        }

        return Inertia::render('report/IndicatorAnalysis', [
            'error' => 'Invalid indicator',
            'cohorts' => Cohort::all(),
            'sites' => \App\Models\Site::all(),
            'facilities' => \App\Models\Facility::all(),
        ]);
    }

    // Helper: Calculate age group
    protected function getAgeGroup($birthDate, $referenceDate = null)
    {
        if (! $birthDate) {
            return 'Unknown';
        }

        $birthDate = is_string($birthDate) ? Carbon::parse($birthDate) : $birthDate;
        $reference = $referenceDate ? Carbon::parse($referenceDate) : now();

        // Add 1 day to the reference date to include patients near the boundary
        $adjustedReference = $reference->copy()->addDay();

        $ageInMonths = round($birthDate->diffInMonths($adjustedReference));

        foreach (self::AGE_GROUPS as $group => $range) {
            [$min, $max] = $range;
            if ($ageInMonths >= $min && ($max === null || $ageInMonths <= $max)) {
                return $group;
            }
        }

        return 'Unknown';
    }

    protected function getChildAgeGroup($birthDate, $referenceDate = null)
    {
        if (! $birthDate) {
            return 'Unknown';
        }

        // Parse the birth date if it's a string
        $birthDate = is_string($birthDate) ? Carbon::parse($birthDate) : $birthDate;
        $reference = $referenceDate ? Carbon::parse($referenceDate) : now();
        $ageInMonths = $birthDate->diffInMonths($reference);

        foreach (self::CHILD_AGE_GROUPS as $group => $range) {
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

    // Add this method to the IndicatorAnalysisController class

    protected function calculatePatientsWithUnknownAgeGroup($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $query = Patient::query();
        $this->applyFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);
        $patients = $query->get();

        // Filter patients with unknown age group
        $unknownAgePatients = $patients->filter(function ($patient) {
            return $this->getAgeGroup($patient->date_of_birth) === 'Unknown';
        });

        // Prepare results with gender breakdown
        $results = [
            'male' => 0,
            'female' => 0,
            'other' => 0,
            'total' => 0,
        ];

        foreach ($unknownAgePatients as $patient) {
            $gender = $this->mapGender($patient->gender);
            $results[$gender]++;
            $results['total']++;
        }
        // dd($unknownAgePatients->values()->toArray());

        return [
            'indicator' => 'Patients with unknown age group',
            'total' => $unknownAgePatients->count(),
            'patients' => $unknownAgePatients->values(), // Return patient collection
            'gender_breakdown' => $results,
        ];
    }

    protected function calculateTotalPatientsEverEnrolled($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $query = Patient::query();
        $this->applyFilters($query, $cohortId, $siteId, $facilityId);
        $patients = $query->get();

        $results = $this->initializeResults();
        $total = 0;
        $maleCount = 0;

        foreach ($patients as $patient) {
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, now());
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;

            if ($gender === 'male') {
                $maleCount++;
            }

            $total++;
        }

        // Calculate male proportion
        $maleProportion = $total > 0 ? round(($maleCount / $total) * 100, 2) : 0;

        // Add proportions to breakdown
        foreach ($results as $ageGroup => $data) {
            $groupTotal = $data['total'];

            $results[$ageGroup]['male_proportion'] = $groupTotal > 0
                ? round(($data['male'] / $groupTotal) * 100, 2)
                : 0;

            $results[$ageGroup]['female_proportion'] = $groupTotal > 0
                ? round(($data['female'] / $groupTotal) * 100, 2)
                : 0;
        }

        return [
            'indicator' => 'Total patients ever enrolled on ART',
            'total' => $total,
            'male_count' => $maleCount,
            'male_proportion' => $maleProportion,
            'breakdown' => $results,
        ];
    }

    // 2. Patients initiated on ART
    protected function calculateEnrolledOnART($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Query visits

        $query = InitialVisit::with('patient')
            ->whereNotNull('art_start_date')
            ->select('patient_id', 'art_start_date');

        // Apply date filters
        if ($startDate) {
            $query->where('art_start_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('art_start_date', '<=', $endDate);
        }

        // Apply cohort/site/facility filters
        $this->applyFilters($query, $cohortId, $siteId, $facilityId);

        // Get distinct patients (one row per patient)
        $query->groupBy('patient_id', 'art_start_date');

        $initialVisits = $query->get();

        $results = $this->initializeResults();
        $total = 0;
        $maleCount = 0;

        foreach ($initialVisits as $visit) {
            $patient = $visit->patient;
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $visit->art_start_date);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;

            if ($gender === 'male') {
                $maleCount++;
            }
            $total++;
        }

        // Calculate male proportion
        $maleProportion = $total > 0 ? round(($maleCount / $total) * 100, 2) : 0;

        // Add proportions to breakdown
        foreach ($results as $ageGroup => $data) {
            $groupTotal = $data['total'];

            $results[$ageGroup]['male_proportion'] = $groupTotal > 0
                ? round(($data['male'] / $groupTotal) * 100, 2)
                : 0;

            $results[$ageGroup]['female_proportion'] = $groupTotal > 0
                ? round(($data['female'] / $groupTotal) * 100, 2)
                : 0;
        }

        return [
            'indicator' => 'Patients Enrolled on ART',
            'total' => $total,
            'male_count' => $maleCount,
            'male_proportion' => $maleProportion,
            'breakdown' => $results,
        ];
    }

    // 3. Patients retained after 12 months
    protected function calculatePatientsRetainedAfter12Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Calculate threshold for ART initiation (12 months ago)
        $initiationThreshold = now()->subMonths(12);

        // Get initial ART visits (visit_type_id = 1) from at least 12 months ago
        $query = Visit::with('patient')
            ->where('visit_type_id', 1)
            ->where('actual_visit_date', '<=', $initiationThreshold);

        // Apply date filters to ART initiation
        if ($startDate) {
            $query->where('actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('actual_visit_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId);

        // Get distinct patients (one row per patient)
        // Group by patient to get unique initiations
        $query->groupBy([
            'visits.id',
            'visits.facility_id',
            'visits.instance',
            'visits.visit_type_id',
            'visits.app_visit_date',
            'visits.actual_visit_date',
            'visits.next_visit_date',
            'visits.transfer_smart',
            'visits.transfer_type_id',
            'visits.batch_id',
            'patient_id']);

        $initiations = $query->get();

        $results = $this->initializeResults();
        $totalRetained = 0;
        $totalEligible = count($initiations);

        foreach ($initiations as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = $initiation->actual_visit_date;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Check for transfer out status
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '!=', '3000-01-01')
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = Carbon::parse($lastVisit->next_visit_date);
                $daysLate = now()->diffInDays($nextVisitDue);

                if ($daysLate > 90) {
                    $isLTFU = true;
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalRetained++;
            }
        }

        // Calculate retention percentage
        $retentionPercentage = $totalEligible > 0
            ? round(($totalRetained / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Patients retained after 12 months',
            'retained' => $totalRetained,
            'total_eligible' => $totalEligible,
            'percentage' => $retentionPercentage,
            'breakdown' => $results,
        ];
    }

    // 3. Patients retained after 24 months
    protected function calculatePatientsRetainedAfter24Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Calculate threshold for ART initiation (24 months ago)
        $initiationThreshold = now()->subMonths(24);

        // Get initial ART visits (visit_type_id = 1) from at least 24 months ago
        $query = Visit::with('patient')
            ->where('visit_type_id', 1)
            ->where('actual_visit_date', '<=', $initiationThreshold);

        // Apply date filters to ART initiation
        if ($startDate) {
            $query->where('actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('actual_visit_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId);

        // Get distinct patients (one row per patient)
        // Group by patient to get unique initiations
        $query->groupBy([
            'visits.id',
            'visits.facility_id',
            'visits.instance',
            'visits.visit_type_id',
            'visits.app_visit_date',
            'visits.actual_visit_date',
            'visits.next_visit_date',
            'visits.transfer_smart',
            'visits.transfer_type_id',
            'visits.batch_id',
            'patient_id']);

        $initiations = $query->get();

        $results = $this->initializeResults();
        $totalRetained = 0;
        $totalEligible = count($initiations);

        foreach ($initiations as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = $initiation->actual_visit_date;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Check for transfer out status
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '!=', '3000-01-01')
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = Carbon::parse($lastVisit->next_visit_date);
                $daysLate = now()->diffInDays($nextVisitDue);

                if ($daysLate > 90) {
                    $isLTFU = true;
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalRetained++;
            }
        }

        // Calculate retention percentage
        $retentionPercentage = $totalEligible > 0
            ? round(($totalRetained / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Patients retained after 24 months',
            'retained' => $totalRetained,
            'total_eligible' => $totalEligible,
            'percentage' => $retentionPercentage,
            'breakdown' => $results,
        ];
    }

    // 4. Patients retained after 60 months
    protected function calculatePatientsRetainedAfter60Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Calculate threshold for ART initiation (24 months ago)
        $initiationThreshold = now()->subMonths(60);

        // Get initial ART visits (visit_type_id = 1) from at least 60 months ago
        $query = Visit::with('patient')
            ->where('visit_type_id', 1)
            ->where('actual_visit_date', '<=', $initiationThreshold);

        // Apply date filters to ART initiation
        if ($startDate) {
            $query->where('actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('actual_visit_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId);

        // Get distinct patients (one row per patient)
        // Group by patient to get unique initiations
        $query->groupBy([
            'visits.id',
            'visits.facility_id',
            'visits.instance',
            'visits.visit_type_id',
            'visits.app_visit_date',
            'visits.actual_visit_date',
            'visits.next_visit_date',
            'visits.transfer_smart',
            'visits.transfer_type_id',
            'visits.batch_id',
            'patient_id']);

        $initiations = $query->get();

        $results = $this->initializeResults();
        $totalRetained = 0;
        $totalEligible = count($initiations);

        foreach ($initiations as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = $initiation->actual_visit_date;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Check for transfer out status
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '!=', '3000-01-01')
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = Carbon::parse($lastVisit->next_visit_date);
                $daysLate = now()->diffInDays($nextVisitDue);

                if ($daysLate > 90) {
                    $isLTFU = true;
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalRetained++;
            }
        }

        // Calculate retention percentage
        $retentionPercentage = $totalEligible > 0
            ? round(($totalRetained / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Patients retained after 60 months',
            'retained' => $totalRetained,
            'total_eligible' => $totalEligible,
            'percentage' => $retentionPercentage,
            'breakdown' => $results,
        ];
    }

    // 5. Patients retained after 120 months
    protected function calculatePatientsRetainedAfter120Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Calculate threshold for ART initiation (24 months ago)
        $initiationThreshold = now()->subMonths(120);

        // Get initial ART visits (visit_type_id = 1) from at least 60 months ago
        $query = Visit::with('patient')
            ->where('visit_type_id', 1)
            ->where('actual_visit_date', '<=', $initiationThreshold);

        // Apply date filters to ART initiation
        if ($startDate) {
            $query->where('actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('actual_visit_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId);

        // Get distinct patients (one row per patient)
        // Group by patient to get unique initiations
        $query->groupBy([
            'visits.id',
            'visits.facility_id',
            'visits.instance',
            'visits.visit_type_id',
            'visits.app_visit_date',
            'visits.actual_visit_date',
            'visits.next_visit_date',
            'visits.transfer_smart',
            'visits.transfer_type_id',
            'visits.batch_id',
            'patient_id']);

        $initiations = $query->get();

        $results = $this->initializeResults();
        $totalRetained = 0;
        $totalEligible = count($initiations);

        foreach ($initiations as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = $initiation->actual_visit_date;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Check for transfer out status
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '!=', '3000-01-01')
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = Carbon::parse($lastVisit->next_visit_date);
                $daysLate = now()->diffInDays($nextVisitDue);

                if ($daysLate > 90) {
                    $isLTFU = true;
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalRetained++;
            }
        }

        // Calculate retention percentage
        $retentionPercentage = $totalEligible > 0
            ? round(($totalRetained / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Patients retained after 120 months',
            'retained' => $totalRetained,
            'total_eligible' => $totalEligible,
            'percentage' => $retentionPercentage,
            'breakdown' => $results,
        ];
    }

    // 6. Patients retained after 180 months
    protected function calculatePatientsRetainedAfter180Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Calculate threshold for ART initiation (180 months ago)
        $initiationThreshold = now()->subMonths(180);

        // Get initial ART visits (visit_type_id = 1) from at least 60 months ago
        $query = Visit::with('patient')
            ->where('visit_type_id', 1)
            ->where('actual_visit_date', '<=', $initiationThreshold);

        // Apply date filters to ART initiation
        if ($startDate) {
            $query->where('actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('actual_visit_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId);

        // Get distinct patients (one row per patient)
        // Group by patient to get unique initiations
        $query->groupBy([
            'visits.id',
            'visits.facility_id',
            'visits.instance',
            'visits.visit_type_id',
            'visits.app_visit_date',
            'visits.actual_visit_date',
            'visits.next_visit_date',
            'visits.transfer_smart',
            'visits.transfer_type_id',
            'visits.batch_id',
            'patient_id']);

        $initiations = $query->get();

        $results = $this->initializeResults();
        $totalRetained = 0;
        $totalEligible = count($initiations);

        foreach ($initiations as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = $initiation->actual_visit_date;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Check for transfer out status
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '!=', '3000-01-01')
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = Carbon::parse($lastVisit->next_visit_date);
                $daysLate = now()->diffInDays($nextVisitDue);

                if ($daysLate > 90) {
                    $isLTFU = true;
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalRetained++;
            }
        }

        // Calculate retention percentage
        $retentionPercentage = $totalEligible > 0
            ? round(($totalRetained / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Patients retained after 180 months',
            'retained' => $totalRetained,
            'total_eligible' => $totalEligible,
            'percentage' => $retentionPercentage,
            'breakdown' => $results,
        ];
    }

    // 7. Patients retained after 240 months
    protected function calculatePatientsRetainedAfter240Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Calculate threshold for ART initiation (240 months ago)
        $initiationThreshold = now()->subMonths(240);

        // Get initial ART visits (visit_type_id = 1) from at least 60 months ago
        $query = Visit::with('patient')
            ->where('visit_type_id', 1)
            ->where('actual_visit_date', '<=', $initiationThreshold);

        // Apply date filters to ART initiation
        if ($startDate) {
            $query->where('actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('actual_visit_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId);

        // Get distinct patients (one row per patient)
        // Group by patient to get unique initiations
        $query->groupBy([
            'visits.id',
            'visits.facility_id',
            'visits.instance',
            'visits.visit_type_id',
            'visits.app_visit_date',
            'visits.actual_visit_date',
            'visits.next_visit_date',
            'visits.transfer_smart',
            'visits.transfer_type_id',
            'visits.batch_id',
            'patient_id']);

        $initiations = $query->get();

        $results = $this->initializeResults();
        $totalRetained = 0;
        $totalEligible = count($initiations);

        foreach ($initiations as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = $initiation->actual_visit_date;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Check for transfer out status
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '!=', '3000-01-01')
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = Carbon::parse($lastVisit->next_visit_date);
                $daysLate = now()->diffInDays($nextVisitDue);

                if ($daysLate > 90) {
                    $isLTFU = true;
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalRetained++;
            }
        }

        // Calculate retention percentage
        $retentionPercentage = $totalEligible > 0
            ? round(($totalRetained / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Patients retained after 240 months',
            'retained' => $totalRetained,
            'total_eligible' => $totalEligible,
            'percentage' => $retentionPercentage,
            'breakdown' => $results,
        ];
    }

    // 8. Patients retained after 300 months
    protected function calculatePatientsRetainedAfter300Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Calculate threshold for ART initiation (300 months ago)
        $initiationThreshold = now()->subMonths(300);

        // Get initial ART visits (visit_type_id = 1) from at least 60 months ago
        $query = Visit::with('patient')
            ->where('visit_type_id', 1)
            ->where('actual_visit_date', '<=', $initiationThreshold);

        // Apply date filters to ART initiation
        if ($startDate) {
            $query->where('actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('actual_visit_date', '<=', $endDate);
        }

        // Apply other filters
        $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId);

        // Get distinct patients (one row per patient)
        // Group by patient to get unique initiations
        $query->groupBy([
            'visits.id',
            'visits.facility_id',
            'visits.instance',
            'visits.visit_type_id',
            'visits.app_visit_date',
            'visits.actual_visit_date',
            'visits.next_visit_date',
            'visits.transfer_smart',
            'visits.transfer_type_id',
            'visits.batch_id',
            'patient_id']);

        $initiations = $query->get();

        $results = $this->initializeResults();
        $totalRetained = 0;
        $totalEligible = count($initiations);

        foreach ($initiations as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = $initiation->actual_visit_date;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Check for transfer out status
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '!=', '3000-01-01')
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = Carbon::parse($lastVisit->next_visit_date);
                $daysLate = now()->diffInDays($nextVisitDue);

                if ($daysLate > 90) {
                    $isLTFU = true;
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalRetained++;
            }
        }

        // Calculate retention percentage
        $retentionPercentage = $totalEligible > 0
            ? round(($totalRetained / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Patients retained after 300 months',
            'retained' => $totalRetained,
            'total_eligible' => $totalEligible,
            'percentage' => $retentionPercentage,
            'breakdown' => $results,
        ];
    }

    protected function calculatePatientsLTFUAndReengaged($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Step 1: Get patients who had at least one visit with 90+ days lapse
        $ltfuPatients = Visit::select('patient_id', DB::raw('MAX(actual_visit_date) as last_visit_date'))
            ->whereNotNull('app_visit_date')
            ->whereNotNull('actual_visit_date')
            ->whereRaw('DATEDIFF(actual_visit_date, app_visit_date) >= 90')
            ->groupBy('patient_id');

        // Step 2: Create base query to find patients who returned after LTFU
        $query = Visit::query()
            ->select(
                'visits.patient_id',
                'patients.date_of_birth',
                'patients.gender',
                DB::raw('MIN(visits.actual_visit_date) as reengagement_date') // FIX: Use visits table
            )
            ->joinSub($ltfuPatients, 'ltfu', function ($join) {
                $join->on('visits.patient_id', '=', 'ltfu.patient_id');
            })
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            // Find visits that occurred after the LTFU visit
            ->whereColumn('visits.actual_visit_date', '>', 'ltfu.last_visit_date')
            ->groupBy('visits.patient_id', 'patients.date_of_birth', 'patients.gender'); // Include all non-aggregated columns

        // Apply filters
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
        if ($startDate) {
            $query->where('ltfu.last_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('ltfu.last_visit_date', '<=', $endDate);
        }

        $reengagedPatients = $query->get();

        // Initialize results
        $results = $this->initializeResults();
        $total = 0;

        foreach ($reengagedPatients as $record) {
            $ageGroup = $this->getAgeGroup($record->date_of_birth, $record->reengagement_date);
            $gender = $this->mapGender($record->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients lost to follow-up and re-engaged',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // 4. Patients with suppressed viral load
    // protected function calculatePatientsWithSuppressedViralLoad($cohortId, $siteId, $facilityId, $startDate, $endDate)
    // {
    //     $query = VisitDetail::where('viral_load', '<', 1000)
    //         ->whereHas('visit', function ($query) use ($startDate, $endDate) {
    //             if ($startDate) {
    //                 $query->where('actual_visit_date', '>=', $startDate);
    //             }
    //             if ($endDate) {
    //                 $query->where('actual_visit_date', '<=', $endDate);
    //             }
    //         })
    //         ->with(['visit.patient']);

    //     // Apply other filters
    //     $this->applyVisitDetailFilters($query, $cohortId, $siteId, $facilityId);

    //     $suppressedVisits = $query->get();

    //     $results = $this->initializeResults();
    //     $total = 0;
    //     $uniquePatients = [];

    //     foreach ($suppressedVisits as $visitDetail) {
    //         $patient = $visitDetail->visit->patient;

    //         // Count unique patients
    //         if (in_array($patient->id, $uniquePatients)) {
    //             continue;
    //         }
    //         $uniquePatients[] = $patient->id;

    //         $ageGroup = $this->getAgeGroup($patient->date_of_birth);
    //         $gender = $this->mapGender($patient->gender);

    //         $results[$ageGroup][$gender]++;
    //         $results[$ageGroup]['total']++;
    //         $total++;
    //     }

    //     return [
    //         'indicator' => 'Patients with suppressed viral load (<1000 copies)',
    //         'total' => $total,
    //         'breakdown' => $results,
    //     ];
    // }
protected function calculatePatientsWithSuppressedViralLoad($cohortId, $siteId, $facilityId, $startDate, $endDate)
{
    // Step 1: Build base query for patients
    $query = Patient::query()
        ->whereHas('visits', function ($visitQuery) use ($startDate, $endDate, $facilityId) {
            // Filter visits by date range
            $visitQuery->whereBetween('actual_visit_date', [
                $startDate ?? '1900-01-01',
                $endDate ?? now()
            ]);
            
            // Filter by facility if specified
            if ($facilityId) {
                $visitQuery->where('facility_id', $facilityId);
            }
            
            // Ensure the visit has a viral load test
            $visitQuery->whereHas('visitDetails', function ($detailQuery) {
                $detailQuery->whereNotNull('viral_load')
                    ->where('viral_load', '>=', 0);
            });
            
            // Get the latest visit in the date range
            $visitQuery->orderBy('actual_visit_date', 'desc')
                ->take(1);
        })
        ->with(['visits' => function ($visitQuery) use ($startDate, $endDate, $facilityId) {
            $visitQuery->whereBetween('actual_visit_date', [
                $startDate ?? '1900-01-01',
                $endDate ?? now()
            ]);
            
            if ($facilityId) {
                $visitQuery->where('facility_id', $facilityId);
            }
            
            $visitQuery->orderBy('actual_visit_date', 'desc')
                ->take(1)
                ->with('visitDetails');
        }]);

    // Apply cohort and site filters
    if ($cohortId) {
        $query->whereHas('site.cohort', function ($q) use ($cohortId) {
            $q->where('id', $cohortId);
        });
    }
    
    if ($siteId) {
        $query->where('site_id', $siteId);
    }

    // Get all patients
    $patients = $query->get();

    // Step 2: Process each patient to check their latest viral load
    $suppressedPatients = $patients->filter(function ($patient) {
        $latestVisit = $patient->visits->first();
        
        if (!$latestVisit || !$latestVisit->visitDetails) {
            return false;
        }
        
        $viralLoad = $latestVisit->visitDetails->viral_load;
        
        // Check if suppressed
        return $viralLoad !== null && $viralLoad >= 0 && $viralLoad < 1000;
    });

    // Step 3: Prepare breakdown
    $results = $this->initializeResults();
    $total = 0;

    foreach ($suppressedPatients as $patient) {
        $latestVisit = $patient->visits->first();
        $ageGroup = $this->getAgeGroup($patient->date_of_birth, $latestVisit->actual_visit_date);
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

    // 5. Patients with suppressed at 6 months viral load
    protected function calculateViralSuppressionAt6Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Define the 6-month window (5-7 months after ART initiation)
        $minMonths = 5;
        $maxMonths = 7;
        $targetMonth = 6;

        // Get ART initiations
        $initiations = InitialVisit::with('patient')
            ->whereNotNull('art_start_date')
            ->select('patient_id', 'art_start_date');

        // Apply filters to ART initiations
        $this->applyInitialVisitFilters($initiations, $cohortId, $siteId, $facilityId);
        $this->applyDateFilter($initiations, 'art_start_date', $startDate, $endDate);

        $artStarts = $initiations->get();

        $results = $this->initializeResults();
        $totalEligible = 0;
        $totalSuppressed = 0;
        $patientsWithVL = 0;

        foreach ($artStarts as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = Carbon::parse($initiation->art_start_date);

            // Only include patients who started ART at least 7 months ago
            if ($artStartDate->diffInMonths(now()) < $maxMonths) {
                continue;
            }

            $totalEligible++;

            // Calculate target date range for VL test
            $startWindow = $artStartDate->copy()->addMonths($minMonths);
            $endWindow = $artStartDate->copy()->addMonths($maxMonths);
            $targetDate = $artStartDate->copy()->addMonths($targetMonth);

            // Find VL tests within the window
            $vlTests = VisitDetail::where('viral_load', '>=', 0) // Valid numeric VL
                ->whereHas('visit', function ($q) use ($startWindow, $endWindow, $patient, $targetDate) {
                    $q->where('patient_id', $patient->id)
                        ->whereBetween('actual_visit_date', [$startWindow, $endWindow])
                        ->orderByRaw('ABS(DATEDIFF(actual_visit_date, ?))', [$targetDate]);
                })
                ->get();

            if ($vlTests->isEmpty()) {
                continue; // No VL test in window
            }

            $patientsWithVL++;
            $closestTest = $vlTests->first();

            if ($closestTest->viral_load < 1000) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalSuppressed++;
            }
        }

        // Calculate suppression rate
        $suppressionRate = $patientsWithVL > 0
            ? round(($totalSuppressed / $patientsWithVL) * 100, 2)
            : 0;

        $vlCoverage = $totalEligible > 0
            ? round(($patientsWithVL / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Viral suppression at 6 months after ART initiation',
            'suppressed' => $totalSuppressed,
            'with_vl_test' => $patientsWithVL,
            'eligible_patients' => $totalEligible,
            'suppression_rate' => $suppressionRate,
            'vl_coverage' => $vlCoverage,
            'breakdown' => $results,
        ];
    }

    protected function calculateViralSuppressionAt12Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Define the 12-month window (11-13 months after ART initiation)
        $minMonths = 11;
        $maxMonths = 13;
        $targetMonth = 12;

        // Get ART initiations
        $initiations = InitialVisit::with('patient')
            ->whereNotNull('art_start_date')
            ->select('patient_id', 'art_start_date');

        // Apply filters to ART initiations
        $this->applyInitialVisitFilters($initiations, $cohortId, $siteId, $facilityId);
        $this->applyDateFilter($initiations, 'art_start_date', $startDate, $endDate);

        $artStarts = $initiations->get();

        $results = $this->initializeResults();
        $totalEligible = 0;
        $totalSuppressed = 0;
        $patientsWithVL = 0;

        foreach ($artStarts as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = Carbon::parse($initiation->art_start_date);

            // Only include patients who started ART at least 13 months ago
            if ($artStartDate->diffInMonths(now()) < $maxMonths) {
                continue;
            }

            $totalEligible++;

            // Calculate target date range for VL test
            $startWindow = $artStartDate->copy()->addMonths($minMonths);
            $endWindow = $artStartDate->copy()->addMonths($maxMonths);
            $targetDate = $artStartDate->copy()->addMonths($targetMonth);

            // Find VL tests within the window
            $vlTests = VisitDetail::where('viral_load', '>=', 0) // Valid numeric VL
                ->whereHas('visit', function ($q) use ($startWindow, $endWindow, $patient, $targetDate) {
                    $q->where('patient_id', $patient->id)
                        ->whereBetween('actual_visit_date', [$startWindow, $endWindow]);
                    $q->orderByRaw('ABS(DATEDIFF(actual_visit_date, ?))', [$targetDate]);
                })
                ->get();

            if ($vlTests->isEmpty()) {
                continue; // No VL test in window
            }

            $patientsWithVL++;
            $closestTest = $vlTests->first();

            if ($closestTest->viral_load < 1000) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalSuppressed++;
            }
        }

        // Calculate suppression rate
        $suppressionRate = $patientsWithVL > 0
            ? round(($totalSuppressed / $patientsWithVL) * 100, 2)
            : 0;

        $vlCoverage = $totalEligible > 0
            ? round(($patientsWithVL / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Viral suppression at 12 months after ART initiation',
            'suppressed' => $totalSuppressed,
            'with_vl_test' => $patientsWithVL,
            'eligible_patients' => $totalEligible,
            'suppression_rate' => $suppressionRate,
            'vl_coverage' => $vlCoverage,
            'breakdown' => $results,
        ];
    }

    protected function calculateViralSuppressionAt24Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Define the 24-month window (23-25 months after ART initiation)
        $minMonths = 23;
        $maxMonths = 25;
        $targetMonth = 24;

        // Get ART initiations
        $initiations = InitialVisit::with('patient')
            ->whereNotNull('art_start_date')
            ->select('patient_id', 'art_start_date');

        // Apply filters to ART initiations
        $this->applyInitialVisitFilters($initiations, $cohortId, $siteId, $facilityId);
        $this->applyDateFilter($initiations, 'art_start_date', $startDate, $endDate);

        $artStarts = $initiations->get();

        $results = $this->initializeResults();
        $totalEligible = 0;
        $totalSuppressed = 0;
        $patientsWithVL = 0;

        foreach ($artStarts as $initiation) {
            $patient = $initiation->patient;
            $artStartDate = Carbon::parse($initiation->art_start_date);

            // Only include patients who started ART at least 13 months ago
            if ($artStartDate->diffInMonths(now()) < $maxMonths) {
                continue;
            }

            $totalEligible++;

            // Calculate target date range for VL test
            $startWindow = $artStartDate->copy()->addMonths($minMonths);
            $endWindow = $artStartDate->copy()->addMonths($maxMonths);
            $targetDate = $artStartDate->copy()->addMonths($targetMonth);

            // Find VL tests within the window
            $vlTests = VisitDetail::where('viral_load', '>=', 0) // Valid numeric VL
                ->whereHas('visit', function ($q) use ($startWindow, $endWindow, $patient, $targetDate) {
                    $q->where('patient_id', $patient->id)
                        ->whereBetween('actual_visit_date', [$startWindow, $endWindow]);
                    $q->orderByRaw('ABS(DATEDIFF(actual_visit_date, ?))', [$targetDate]);
                })
                ->get();

            if ($vlTests->isEmpty()) {
                continue; // No VL test in window
            }

            $patientsWithVL++;
            $closestTest = $vlTests->first();

            if ($closestTest->viral_load < 1000) {
                $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
                $gender = $this->mapGender($patient->gender);

                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
                $totalSuppressed++;
            }
        }

        // Calculate suppression rate
        $suppressionRate = $patientsWithVL > 0
            ? round(($totalSuppressed / $patientsWithVL) * 100, 2)
            : 0;

        $vlCoverage = $totalEligible > 0
            ? round(($patientsWithVL / $totalEligible) * 100, 2)
            : 0;

        return [
            'indicator' => 'Viral suppression at 24 months after ART initiation',
            'suppressed' => $totalSuppressed,
            'with_vl_test' => $patientsWithVL,
            'eligible_patients' => $totalEligible,
            'suppression_rate' => $suppressionRate,
            'vl_coverage' => $vlCoverage,
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
                DB::raw('YEAR(visits.actual_visit_date) as year')
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
        // Get the latest visit with appointment for each patient
        $latestVisits = Visit::select('patient_id', DB::raw('MAX(actual_visit_date) as latest_visit_date'))
            ->whereNotNull('app_visit_date')
            ->groupBy('patient_id');

        // Create base query for latest visits
        $baseQuery = Visit::query()
            ->select('visits.*', 'patients.date_of_birth', 'patients.gender')
            ->joinSub($latestVisits, 'latest_visits', function ($join) {
                $join->on('visits.patient_id', '=', 'latest_visits.patient_id')
                    ->on('visits.actual_visit_date', '=', 'latest_visits.latest_visit_date');
            })
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->whereNotNull('visits.app_visit_date')
            ->whereNotNull('visits.actual_visit_date');

        // Apply filters to the base query
        if ($cohortId) {
            $baseQuery->join('sites', 'patients.site_id', '=', 'sites.id')
                ->where('sites.cohort_id', $cohortId);
        }
        if ($siteId) {
            $baseQuery->where('patients.site_id', $siteId);
        }
        if ($facilityId) {
            $baseQuery->where('visits.facility_id', $facilityId);
        }
        if ($startDate) {
            $baseQuery->where('visits.actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $baseQuery->where('visits.actual_visit_date', '<=', $endDate);
        }

        // Get total appointments
        $totalVisits = $baseQuery->count();

        // Get missed appointments (90+ days late)
        $missedVisits = $baseQuery->clone()
            ->whereRaw('DATEDIFF(visits.actual_visit_date, visits.app_visit_date) >= 90')
            ->get();

        $missedCount = $missedVisits->count();

        // Create breakdown by age and gender
        $results = $this->initializeResults();

        foreach ($missedVisits as $visit) {
            $ageGroup = $this->getAgeGroup($visit->date_of_birth);
            $gender = $this->mapGender($visit->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
        }

        $percentage = $totalVisits > 0 ? round(($missedCount / $totalVisits) * 100, 2) : 0;

        return [
            'indicator' => 'Missed appointment visits (90+ days late)',
            'missed_visits' => $missedCount,
            'total_visits' => $totalVisits,
            'percentage' => $percentage,
            'breakdown' => $results,
        ];
    }

    // 7. Pregnant women retained after 12 months
    protected function calculatePregnantWomenRetainedAfter12Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {

        $period = 12; // months
        // Find visits where pregnancy was recorded
        $query = VisitDetail::where('pregnant', 'Yes')
            ->with(['visit.patient']);

        // Apply filters
        $this->applyVisitDetailFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        $pregnancyVisits = $query->get();

        // Step 1: Get total females per age group (denominator for proportion)
        $totalFemalesByAgeGroup = $this->getPregnancyEligibleFemalesByAgeGroup(
            $cohortId, $siteId, $facilityId, $startDate, $endDate, $period
        );

        // Initialize results with proportion support
        $breakdown = [];
        foreach (array_keys(self::AGE_GROUPS) as $group) {
            $breakdown[$group] = [
                'retained' => 0,
                'total_pregnant' => 0, // $totalFemalesByAgeGroup[$group] ?? 0,
                'proportion' => 0,
            ];
        }
        $breakdown['Unknown'] = [
            'retained' => 0,
            'total_pregnant' => $totalFemalesByAgeGroup['Unknown'] ?? 0,
            'proportion' => 0,
        ];

        $totalPregnant = 0;
        $retainedCount = 0;

        foreach ($pregnancyVisits as $visitDetail) {
            $patient = $visitDetail->visit->patient;
            $pregnancyDate = Carbon::parse($visitDetail->visit->actual_visit_date);
            $assessmentDate = $pregnancyDate->copy()->addMonths(12);
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $pregnancyDate);

            // Only assess if 12+ months passed
            $isEligible = $assessmentDate <= now();

            if ($isEligible) {
                $breakdown[$ageGroup]['total_pregnant']++;
                $totalPregnant++;
            } else {
                continue; // Skip ineligible pregnancies
            }

            // Only assess if 12 months have passed since pregnancy visit
            if ($assessmentDate > now()) {
                continue;
            }

            $totalPregnant++;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Exclude transferred out patients
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->where('actual_visit_date', '<=', $assessmentDate)
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->where('actual_visit_date', '<=', $assessmentDate)
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = $lastVisit->next_visit_date;
                if ($nextVisitDue && $nextVisitDue != '3000-01-01') {
                    $dueDate = Carbon::parse($nextVisitDue);
                    $daysLate = $assessmentDate->diffInDays($dueDate);

                    if ($daysLate > 90) {
                        $isLTFU = true;
                    }
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $breakdown[$ageGroup]['retained']++;
                $retainedCount++;
            }
        }

        // Calculate proportions
        foreach ($breakdown as $ageGroup => &$data) {
            if ($data['total_pregnant'] > 0) {
                $data['proportion'] = round(($data['retained'] / $data['total_pregnant']) * 100, 2);
            }
        }

        $percentage = $totalPregnant > 0 ? round(($retainedCount / $totalPregnant) * 100, 2) : 0;

        return [
            'indicator' => 'Pregnant women retained after 12 months',
            'retained' => $retainedCount,
            'total_pregnant' => $totalPregnant,
            'percentage' => $percentage,
            'breakdown' => $breakdown,
        ];
    }

    // 7. Pregnant women retained after 24 months
    protected function calculatePregnantWomenRetainedAfter24Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {

        $period = 24; // months

        // Find visits where pregnancy was recorded
        $query = VisitDetail::where('pregnant', 'Yes')
            ->with(['visit.patient']);

        // Apply filters
        $this->applyVisitDetailFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        $pregnancyVisits = $query->get();

        // Step 1: Get total females per age group (denominator for proportion)
        $totalFemalesByAgeGroup = $this->getPregnancyEligibleFemalesByAgeGroup(
            $cohortId, $siteId, $facilityId, $startDate, $endDate, $period
        );

        // Initialize results with proportion support
        $breakdown = [];
        foreach (array_keys(self::AGE_GROUPS) as $group) {
            $breakdown[$group] = [
                'retained' => 0,
                'total_pregnant' => 0, // $totalFemalesByAgeGroup[$group] ?? 0,
                'proportion' => 0,
            ];
        }
        $breakdown['Unknown'] = [
            'retained' => 0,
            'total_pregnant' => $totalFemalesByAgeGroup['Unknown'] ?? 0,
            'proportion' => 0,
        ];

        $totalPregnant = 0;
        $retainedCount = 0;

        foreach ($pregnancyVisits as $visitDetail) {
            $patient = $visitDetail->visit->patient;
            $pregnancyDate = Carbon::parse($visitDetail->visit->actual_visit_date);
            $assessmentDate = $pregnancyDate->copy()->addMonths(24);
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $pregnancyDate);

            // Only assess if 12+ months passed
            $isEligible = $assessmentDate <= now();

            if ($isEligible) {
                $breakdown[$ageGroup]['total_pregnant']++;
                $totalPregnant++;
            } else {
                continue; // Skip ineligible pregnancies
            }

            // Only assess if 12 months have passed since pregnancy visit
            if ($assessmentDate > now()) {
                continue;
            }

            $totalPregnant++;

            // Exclude dead patients
            if ($patient->patient_status_id == 5) {
                continue;
            }

            // Exclude transferred out patients
            $transferredOut = $patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->where('actual_visit_date', '<=', $assessmentDate)
                ->exists();

            if ($transferredOut) {
                continue;
            }

            // Check for LTFU status
            $lastVisit = $patient->visits()
                ->where('actual_visit_date', '<=', $assessmentDate)
                ->orderBy('actual_visit_date', 'desc')
                ->first();

            $isLTFU = false;
            if ($lastVisit) {
                $nextVisitDue = $lastVisit->next_visit_date;
                if ($nextVisitDue && $nextVisitDue != '3000-01-01') {
                    $dueDate = Carbon::parse($nextVisitDue);
                    $daysLate = $assessmentDate->diffInDays($dueDate);

                    if ($daysLate > 90) {
                        $isLTFU = true;
                    }
                }
            }

            // If not excluded and not LTFU, count as retained
            if (! $isLTFU) {
                $breakdown[$ageGroup]['retained']++;
                $retainedCount++;
            }
        }

        // Calculate proportions
        foreach ($breakdown as $ageGroup => &$data) {
            if ($data['total_pregnant'] > 0) {
                $data['proportion'] = round(($data['retained'] / $data['total_pregnant']) * 100, 2);
            }
        }

        $percentage = $totalPregnant > 0 ? round(($retainedCount / $totalPregnant) * 100, 2) : 0;

        return [
            'indicator' => 'Pregnant women retained after 24 months',
            'retained' => $retainedCount,
            'total_pregnant' => $totalPregnant,
            'percentage' => $percentage,
            'breakdown' => $breakdown,
        ];
    }

    protected function calculatePregnantWomenLTFUAfter12Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $period = 12; // months
        // Get eligible pregnant women counts per age group
        $eligiblePregnancies = $this->getPregnancyEligibleFemalesByAgeGroup(
            $cohortId, $siteId, $facilityId, $startDate, $endDate, $period
        );

        // Initialize results with eligible counts
        $breakdown = [];
        foreach (array_keys(self::AGE_GROUPS) as $group) {
            $breakdown[$group] = [
                'ltfu' => 0,
                'total_pregnant' => $eligiblePregnancies[$group] ?? 0,
                'proportion' => 0,
            ];
        }
        $breakdown['Unknown'] = [
            'ltfu' => 0,
            'total_pregnant' => $eligiblePregnancies['Unknown'] ?? 0,
            'proportion' => 0,
        ];

        $totalPregnant = array_sum($eligiblePregnancies);
        $totalLTFU = 0;

        // Now fetch actual patient data for LTFU determination
        $query = VisitDetail::where('pregnant', 'yes')
            ->with(['visit.patient'])
            ->whereHas('visit', function ($q) {
                $q->where('actual_visit_date', '<=', now()->subMonths(12));
            });

        $this->applyVisitDetailFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);
        $pregnancyVisits = $query->get();

        // Process each pregnancy visit
        foreach ($pregnancyVisits as $visitDetail) {
            $patient = $visitDetail->visit->patient;
            $pregnancyDate = Carbon::parse($visitDetail->visit->actual_visit_date);
            $assessmentDate = $pregnancyDate->copy()->addMonths(12);
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $pregnancyDate);

            // Skip if patient not in eligible count
            if (! isset($breakdown[$ageGroup]) || $breakdown[$ageGroup]['total_pregnant'] <= 0) {
                continue;
            }

            // Exclusions
            if ($patient->patient_status_id == 5) {
                continue;
            } // Dead

            if ($patient->visits()
                ->where('transfer_type_id', 2)
                ->where('next_visit_date', '3000-01-01')
                ->where('actual_visit_date', '<=', $assessmentDate)
                ->exists()) {
                continue;
            } // Transferred out

            // LTFU determination
            $lastVisit = $patient->visits()
                ->where('actual_visit_date', '<=', $assessmentDate)
                ->orderByDesc('actual_visit_date')
                ->first();

            $isLTFU = false;
            if ($lastVisit && $lastVisit->next_visit_date && $lastVisit->next_visit_date != '3000-01-01') {
                $dueDate = Carbon::parse($lastVisit->next_visit_date);
                $isLTFU = $assessmentDate->diffInDays($dueDate) > 90;
            }

            if ($isLTFU) {
                $breakdown[$ageGroup]['ltfu']++;
                $totalLTFU++;
            }
        }

        // Calculate proportions
        foreach ($breakdown as $ageGroup => &$data) {
            if ($data['total_pregnant'] > 0) {
                $data['proportion'] = round(($data['ltfu'] / $data['total_pregnant']) * 100, 2);
            }
        }

        $ltfuPercentage = $totalPregnant > 0 ? round(($totalLTFU / $totalPregnant) * 100, 2) : 0;

        return [
            'indicator' => 'Pregnant women LTFU after 12 months',
            'ltfu_count' => $totalLTFU,
            'total_pregnant' => $totalPregnant,
            'percentage' => $ltfuPercentage,
            'breakdown' => $breakdown,
        ];
    }

protected function calculatePregnantWomenDiedWithin12Months($cohortId, $siteId, $facilityId, $startDate, $endDate)
{
    $period = 12; // months
    // Get eligible pregnant women counts per age group
    $eligiblePregnancies = $this->getPregnancyEligibleFemalesByAgeGroup(
        $cohortId, $siteId, $facilityId, $startDate, $endDate, $period
    );

    // Initialize results with eligible counts
    $breakdown = [];
    foreach (array_keys(self::AGE_GROUPS) as $group) {
        $breakdown[$group] = [
            'died' => 0,
            'total_pregnant' => $eligiblePregnancies[$group] ?? 0,
            'proportion' => 0,
        ];
    }
    $breakdown['Unknown'] = [
        'died' => 0,
        'total_pregnant' => $eligiblePregnancies['Unknown'] ?? 0,
        'proportion' => 0,
    ];

    $totalPregnant = array_sum($eligiblePregnancies);
    $totalDied = 0;

    // Fetch pregnancy visits from 12+ months ago
    $query = VisitDetail::where('pregnant', 'Yes')
        ->with(['visit.patient'])
        ->whereHas('visit', function ($q) {
            $q->where('actual_visit_date', '<=', now()->subMonths(12));
        });

    $this->applyVisitDetailFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);
    $pregnancyVisits = $query->get();

    // Process each pregnancy visit
    foreach ($pregnancyVisits as $visitDetail) {
        $patient = $visitDetail->visit->patient;
        $pregnancyDate = Carbon::parse($visitDetail->visit->actual_visit_date);
        $assessmentDate = $pregnancyDate->copy()->addMonths(12);
        $ageGroup = $this->getAgeGroup($patient->date_of_birth, $pregnancyDate);

        // Skip if patient not in eligible count
        if (!isset($breakdown[$ageGroup]) || $breakdown[$ageGroup]['total_pregnant'] <= 0) {
            continue;
        }

        // Check if patient died within 12 months of pregnancy
        $diedWithinPeriod = false;

        // Check patient's main status record using status_date
        if ($patient->patient_status_id == 5) { // Dead
            $deathDate = $patient->status_date ? Carbon::parse($patient->status_date) : null;
            
            if ($deathDate && $deathDate->between($pregnancyDate, $assessmentDate)) {
                $diedWithinPeriod = true;
            }
        }

        // Alternative: If status_date is not available, use the last visit date as fallback
        if (!$diedWithinPeriod && $patient->patient_status_id == 5 && !$patient->status_date) {
            $lastVisit = $patient->visits()
                ->whereNotNull('actual_visit_date')
                ->where('actual_visit_date', '!=', '3000-01-01')
                ->where('actual_visit_date', '!=', '01-01-3000')
                ->orderBy('actual_visit_date', 'desc')
                ->first();
            
            if ($lastVisit && $lastVisit->actual_visit_date->between($pregnancyDate, $assessmentDate)) {
                $diedWithinPeriod = true;
            }
        }

        if ($diedWithinPeriod) {
            $breakdown[$ageGroup]['died']++;
            $totalDied++;
        }
    }

    // Calculate proportions
    foreach ($breakdown as $ageGroup => &$data) {
        if ($data['total_pregnant'] > 0) {
            $data['proportion'] = round(($data['died'] / $data['total_pregnant']) * 100, 2);
        }
    }

    $deathPercentage = $totalPregnant > 0 ? round(($totalDied / $totalPregnant) * 100, 2) : 0;

    return [
        'indicator' => 'Pregnant women died within 12 months',
        'died_count' => $totalDied,
        'total_pregnant' => $totalPregnant,
        'percentage' => $deathPercentage,
        'breakdown' => $breakdown,
    ];
}

    protected function calculateTimeToFirstViralLoad($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Get ART initiation dates from InitialVisits
        $initiations = InitialVisit::with('patient')
            ->whereNotNull('art_start_date')
            ->select('patient_id', 'art_start_date');

        // Apply filters to ART initiations
        $this->applyInitialVisitFilters($initiations, $cohortId, $siteId, $facilityId);
        $this->applyDateFilter($initiations, 'art_start_date', $startDate, $endDate);

        // Get first viral load test for each patient
        $viralLoadTests = VisitDetail::join('visits', 'visit_details.visit_id', '=', 'visits.id')
            ->whereNotNull('viral_load')
            ->where('viral_load', '>=', 0) // Ensure it's a valid numeric value
            ->select('visits.patient_id', DB::raw('MIN(visits.actual_visit_date) as first_vl_date'))
            ->groupBy('visits.patient_id');

        // Combine ART initiations with first viral load tests
        $query = DB::table(DB::raw("({$initiations->toSql()}) as art"))
            ->mergeBindings($initiations->getQuery())
            ->leftJoin(DB::raw("({$viralLoadTests->toSql()}) as vl"), 'art.patient_id', '=', 'vl.patient_id')
            ->mergeBindings($viralLoadTests->getQuery())
            ->join('patients', 'art.patient_id', '=', 'patients.id') // Join patients here
            ->select(
                'art.patient_id',
                'art.art_start_date',
                'vl.first_vl_date',
                'patients.date_of_birth',
                'patients.gender',
                DB::raw('DATEDIFF(vl.first_vl_date, art.art_start_date) as days_diff')
            )
            ->whereNotNull('vl.first_vl_date');

        // Apply additional data validation
        $query->where('vl.first_vl_date', '>=', 'art.art_start_date')
            ->where('vl.first_vl_date', '<=', now()) // Ensure VL date isn't in future
            ->where('art.art_start_date', '>=', DB::raw('patients.date_of_birth')); // ART after birth

        $results = $query->get();

        // Temporarily add debug code before filtering:
        foreach ($results as $row) {
            if ($row->days_diff == 286) { // Suspiciously high values
                Log::debug('High days_diff patient', [
                    'patient_id' => $row->patient_id,
                    'art_start_date' => $row->art_start_date,
                    'first_vl_date' => $row->first_vl_date,
                    'days_diff' => $row->days_diff,
                    'birth_date' => $row->date_of_birth,
                    'age_at_art' => Carbon::parse($row->date_of_birth)
                        ->diffInMonths($row->art_start_date),
                    'age_at_vl' => Carbon::parse($row->date_of_birth)
                        ->diffInMonths($row->first_vl_date),
                ]);
            }
        }

        // Filter out impossible values (more than 5 years)
        $filteredResults = $results->filter(function ($row) {
            return $row->days_diff >= 0 && $row->days_diff <= 1825; // 5 years
        });

        // Calculate overall average
        $totalPatients = $filteredResults->count();
        $totalDays = $filteredResults->sum('days_diff');
        $averageDays = $totalPatients > 0 ? round($totalDays / $totalPatients, 2) : 0;

        // Prepare breakdown by age and gender
        $breakdown = [];
        foreach (array_keys(self::AGE_GROUPS) as $group) {
            $breakdown[$group] = [
                'male' => ['count' => 0, 'total_days' => 0],
                'female' => ['count' => 0, 'total_days' => 0],
                'other' => ['count' => 0, 'total_days' => 0],
                'total' => ['count' => 0, 'total_days' => 0],
            ];
        }
        $breakdown['Unknown'] = [
            'male' => ['count' => 0, 'total_days' => 0],
            'female' => ['count' => 0, 'total_days' => 0],
            'other' => ['count' => 0, 'total_days' => 0],
            'total' => ['count' => 0, 'total_days' => 0],
        ];

        foreach ($filteredResults as $row) {
            $ageGroup = $this->getAgeGroup($row->date_of_birth, now());  // $this->getAgeGroup($row->date_of_birth, $row->art_start_date);
            $gender = $this->mapGender($row->gender);

            if (! isset($breakdown[$ageGroup])) {
                $ageGroup = 'Unknown';
            }

            $breakdown[$ageGroup][$gender]['count']++;
            $breakdown[$ageGroup][$gender]['total_days'] += $row->days_diff;
            $breakdown[$ageGroup]['total']['count']++;
            $breakdown[$ageGroup]['total']['total_days'] += $row->days_diff;
        }

        // Calculate averages for each group
        $averages = [];
        foreach ($breakdown as $ageGroup => $genders) {
            $averages[$ageGroup] = [
                'male' => $genders['male']['count'] ? round($genders['male']['total_days'] / $genders['male']['count'], 2) : 0,
                'female' => $genders['female']['count'] ? round($genders['female']['total_days'] / $genders['female']['count'], 2) : 0,
                'other' => $genders['other']['count'] ? round($genders['other']['total_days'] / $genders['other']['count'], 2) : 0,
                'total' => $genders['total']['count'] ? round($genders['total']['total_days'] / $genders['total']['count'], 2) : 0,
            ];
        }

        // Calculate distribution statistics
        $daysArray = $filteredResults->pluck('days_diff')->toArray();
        $medianDays = $this->calculateMedian($daysArray);
        $inTarget = $filteredResults->filter(fn ($row) => $row->days_diff <= 180)->count();

        return [
            'indicator' => 'Time to first Viral Load test',
            'average_days' => $averageDays,
            'median_days' => $medianDays,
            'total_patients' => $totalPatients,
            'patients_in_target' => $inTarget,
            'breakdown' => $averages,
        ];
    }

    //
    protected function calculateProportionOfChildrenOnART($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $query = Patient::query();
        $this->applyFilters($query, $cohortId, $siteId, $facilityId);
        $patients = $query->get();

        $results = $this->initializeChildResults();
        $total = 0;
        $childrenCount = 0;

        foreach ($patients as $patient) {
            $ageGroup = $this->getChildAgeGroup($patient->date_of_birth);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;

            // Check if patient is a child (<18 years)
            if ($this->isChild($patient->date_of_birth)) {
                $childrenCount++;
            }
        }

        // Calculate proportion
        $proportion = $total > 0 ? round(($childrenCount / $total) * 100, 2) : 0;

        // Add children proportion to each age group
        foreach ($results as $ageGroup => $data) {
            $groupTotal = $data['total'];
            $results[$ageGroup]['children_proportion'] = $groupTotal > 0
                ? round(($data['total'] / $groupTotal) * 100, 2)
                : 0;
        }

        return [
            'indicator' => 'Proportion of children on ART',
            'total_children' => $childrenCount,
            'total_patients' => $total,
            'proportion' => $proportion,
            'breakdown' => $results,
        ];
    }

    // Helper: Check if patient is a child (<18 years)
    protected function isChild($birthDate)
    {
        if (! $birthDate) {
            return false;
        }

        $birthDate = is_string($birthDate) ? Carbon::parse($birthDate) : $birthDate;

        return $birthDate->diffInYears(now()) < 18;
    }

    protected function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $mid = floor($count / 2);

        return $count % 2 === 0
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : $values[$mid];
    }

    protected function getPregnancyEligibleFemalesByAgeGroup($cohortId, $siteId, $facilityId, $startDate, $endDate, $period)
    {
        $query = Patient::query()
            ->where('gender', 'female')
            ->whereHas('visits', function ($q) use ($startDate, $endDate, $period) {
                $q->whereHas('visitDetails', function ($q) {
                    $q->where('pregnant', 'Yes');
                })
                    ->where('actual_visit_date', '<=', now()->subMonths($period));

                if ($startDate) {
                    $q->where('actual_visit_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('actual_visit_date', '<=', $endDate);
                }
            });

        $this->applyFilters($query, $cohortId, $siteId, $facilityId);
        $patients = $query->get();

        $results = [];
        foreach (array_keys(self::AGE_GROUPS) as $group) {
            $results[$group] = 0;
        }
        $results['Unknown'] = 0;

        foreach ($patients as $patient) {
            // Find the pregnancy visit to determine age at pregnancy
            $pregnancyVisit = $patient->visits->first(function ($visit) {
                // dd($visit->visitDetails->pregnant);
                return $visit->visitDetails && $visit->visitDetails->pregnant === 'Yes';
            });

            if (! $pregnancyVisit) {
                continue;
            }

            $ageGroup = $this->getAgeGroup(
                $patient->date_of_birth,
                $pregnancyVisit->actual_visit_date
            );

            $results[$ageGroup] = ($results[$ageGroup] ?? 0) + 1;
        }

        return $results;
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

    // Helper: Initialize results structure
    protected function initializeChildResults()
    {
        $results = [];
        foreach (array_keys(self::CHILD_AGE_GROUPS) as $group) {
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

    // 10. viral load test after latest high viral load
    protected function calculatePatientsWithVlAfterHigh($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Step 1: Get patients with high viral load (>=1000) and their latest instance
        $latestHighVl = VisitDetail::join('visits', 'visit_details.visit_id', '=', 'visits.id')
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->where('visit_details.viral_load', '>=', 1000)
            ->select(
                'visits.patient_id',
                DB::raw('MAX(visits.instance) as max_high_instance'),
                DB::raw('MAX(visits.actual_visit_date) as high_vl_date')
            )
            ->groupBy('visits.patient_id');

        // Apply filters to high viral load visits
        if ($cohortId) {
            $latestHighVl->join('sites', 'patients.site_id', '=', 'sites.id')
                ->where('sites.cohort_id', $cohortId);
        }
        if ($siteId) {
            $latestHighVl->where('patients.site_id', $siteId);
        }
        if ($facilityId) {
            $latestHighVl->where('visits.facility_id', $facilityId);
        }
        if ($startDate) {
            $latestHighVl->where('visits.actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $latestHighVl->where('visits.actual_visit_date', '<=', $endDate);
        }

        // Step 2: Find patients with subsequent viral load tests after their latest high VL
        $query = VisitDetail::join('visits', 'visit_details.visit_id', '=', 'visits.id')
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->joinSub($latestHighVl, 'latest_high', function ($join) {
                $join->on('visits.patient_id', '=', 'latest_high.patient_id');
            })
            ->where('visits.instance', '>', DB::raw('latest_high.max_high_instance'))
            ->whereNotNull('visit_details.viral_load')
            ->select(
                'patients.id',
                'patients.date_of_birth',
                'patients.gender',
                'latest_high.high_vl_date'
            )
            ->distinct('patients.id');

        $patients = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($patients as $patient) {
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $patient->high_vl_date);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients with viral load test after latest high viral load',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // 11. ART patients who died

    protected function calculateDeathsAmongART($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        $query = Patient::where('patient_status_id', 5) // Status 5 = Dead
            ->whereHas('initialVisit'); // Ensure they're ART patients

        // Apply filters
        $this->applyFilters($query, $cohortId, $siteId, $facilityId);

        // Get death records
        $deaths = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($deaths as $patient) {
            // Use ART start date for age calculation
            $artStartDate = $patient->initialVisit->art_start_date ?? null;

            $ageGroup = $this->getAgeGroup(
                $patient->date_of_birth,
                $artStartDate ?: $patient->created_at
            );

            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Deaths among ART patients',
            'total' => $total,
            'breakdown' => $results,
            'coverage' => $this->getCoverageDescription($cohortId, $siteId, $facilityId),
        ];
    }

    // 12. Patients transferred out to another facility
    protected function calculateTransferredOut($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Query for patients with transfer_type_id = 2 (transferred out)
        $query = VisitDetail::join('visits', 'visit_details.visit_id', '=', 'visits.id')
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->where('visits.transfer_type_id', 2)
            ->where('visits.next_visit_date', '3000-01-01') // Standard transferred out indicator
            ->select(
                'patients.id',
                'patients.date_of_birth',
                'patients.gender',
                'visits.actual_visit_date as transfer_date'
            )
            ->distinct('patients.id'); // Ensure unique patients

        // Apply cohort filter
        if ($cohortId) {
            $query->join('sites', 'patients.site_id', '=', 'sites.id')
                ->where('sites.cohort_id', $cohortId);
        }

        // Apply site filter
        if ($siteId) {
            $query->where('patients.site_id', $siteId);
        }

        // Apply facility filter
        if ($facilityId) {
            $query->where('visits.facility_id', $facilityId);
        }

        // Apply date filters
        if ($startDate) {
            $query->where('visits.actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('visits.actual_visit_date', '<=', $endDate);
        }

        $transferredPatients = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($transferredPatients as $patient) {
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $patient->transfer_date);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients transferred out to another facility',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // on TB treatment
    protected function calculatePatientsOnTBTreatment($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Get patients with TB status 4 (Preventive) or 5 (Treatment)
        $query = VisitDetail::join('visits', 'visit_details.visit_id', '=', 'visits.id')
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->whereIn('visit_details.tb_status_id', [4, 5])
            ->select(
                'patients.id',
                'patients.date_of_birth',
                'patients.gender',
                'visits.actual_visit_date'
            )
            ->distinct('patients.id'); // Unique patients

        // Apply cohort filter
        if ($cohortId) {
            $query->join('sites', 'patients.site_id', '=', 'sites.id')
                ->where('sites.cohort_id', $cohortId);
        }

        // Apply site filter
        if ($siteId) {
            $query->where('patients.site_id', $siteId);
        }

        // Apply facility filter
        if ($facilityId) {
            $query->where('visits.facility_id', $facilityId);
        }

        // Apply date filters
        if ($startDate) {
            $query->where('visits.actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('visits.actual_visit_date', '<=', $endDate);
        }

        $patients = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($patients as $patient) {
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $patient->actual_visit_date);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients currently on TB treatment',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    // switch reasons
    protected function calculateRegimenSwitches($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Query for patients with regimen switch due to side effects/treatment failure
        $query = VisitDetail::join('visits', 'visit_details.visit_id', '=', 'visits.id')
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->where('visit_details.arv_switch_reason_id', 1) // Reason ID 1 for side effects/treatment failure
            ->select(
                'patients.id',
                'patients.date_of_birth',
                'patients.gender',
                'visits.actual_visit_date as switch_date'
            )
            ->distinct('patients.id'); // Unique patients

        // Apply cohort filter
        if ($cohortId) {
            $query->join('sites', 'patients.site_id', '=', 'sites.id')
                ->where('sites.cohort_id', $cohortId);
        }

        // Apply site filter
        if ($siteId) {
            $query->where('patients.site_id', $siteId);
        }

        // Apply facility filter
        if ($facilityId) {
            $query->where('visits.facility_id', $facilityId);
        }

        // Apply date filters
        if ($startDate) {
            $query->where('visits.actual_visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('visits.actual_visit_date', '<=', $endDate);
        }

        $patients = $query->get();

        $results = $this->initializeResults();
        $total = 0;

        foreach ($patients as $patient) {
            $ageGroup = $this->getAgeGroup($patient->date_of_birth, $patient->switch_date);
            $gender = $this->mapGender($patient->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
            $total++;
        }

        return [
            'indicator' => 'Patients who switched regimens due to side effects or treatment failure',
            'total' => $total,
            'breakdown' => $results,
        ];
    }

    //
    protected function calculateMissedAppointments($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Get all appointments within the period
        $appointmentsQuery = Visit::query()
            ->whereNotNull('app_visit_date')
            ->whereNotNull('actual_visit_date');

        // Apply filters
        $this->applyVisitFilters($appointmentsQuery, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        // Get total appointments
        $totalAppointments = $appointmentsQuery->count();

        // Get missed appointments (1+ days late)
        $missedQuery = clone $appointmentsQuery;
        $missedAppointments = $missedQuery->whereRaw('DATEDIFF(actual_visit_date, app_visit_date) >= 1')
            ->count();

        // Calculate percentage
        $missedPercentage = $totalAppointments > 0
            ? round(($missedAppointments / $totalAppointments) * 100, 2)
            : 0;

        // Get breakdown by age and gender
        $breakdownQuery = Visit::join('patients', 'visits.patient_id', '=', 'patients.id')
            ->whereNotNull('app_visit_date')
            ->whereNotNull('actual_visit_date')
            ->whereRaw('DATEDIFF(actual_visit_date, app_visit_date) >= 90')
            ->select(
                'patients.date_of_birth',
                'patients.gender',
                'visits.actual_visit_date'
            );

        // Apply filters to breakdown query
        $this->applyVisitFilters($breakdownQuery, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        $missedVisits = $breakdownQuery->get();
        $results = $this->initializeResults();

        foreach ($missedVisits as $visit) {
            $ageGroup = $this->getAgeGroup($visit->date_of_birth, $visit->actual_visit_date);
            $gender = $this->mapGender($visit->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
        }

        return [
            'indicator' => 'Missed appointments (1+ days late)',
            'missed_appointments' => $missedAppointments,
            'total_appointments' => $totalAppointments,
            'percentage' => $missedPercentage,
            'breakdown' => $results,
        ];
    }

    protected function calculateMissedVisitRates($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Get all appointments within the period
        $appointmentsQuery = Visit::query()
            ->whereNotNull('app_visit_date')
            ->whereNotNull('actual_visit_date');

        // Apply filters
        $this->applyVisitFilters($appointmentsQuery, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        // Get total appointments
        $totalAppointments = $appointmentsQuery->count();

        // Categorize missed visits by severity
        $severityLevels = [
            '1-7 days' => [1, 7],
            '8-30 days' => [8, 30],
            '31-89 days' => [31, 89],
            '90+ days' => [90, null],
        ];

        $results = $this->initializeResults();
        $severityCounts = [
            '1-7 days' => 0,
            '8-30 days' => 0,
            '31-89 days' => 0,
            '90+ days' => 0,
            'total_missed' => 0,
        ];

        // Get all visits for breakdown
        $visits = $appointmentsQuery->with('patient')->get();

        foreach ($visits as $visit) {
            $daysLate = Carbon::parse($visit->app_visit_date)
                ->diffInDays(Carbon::parse($visit->actual_visit_date));

            $ageGroup = $this->getAgeGroup($visit->patient->date_of_birth, $visit->actual_visit_date);
            $gender = $this->mapGender($visit->patient->gender);

            // Categorize by severity
            $category = 'On-time';
            foreach ($severityLevels as $level => $range) {
                [$min, $max] = $range;
                if ($daysLate >= $min && ($max === null || $daysLate <= $max)) {
                    $category = $level;
                    $severityCounts[$level]++;
                    $severityCounts['total_missed']++;
                    break;
                }
            }

            // Only count missed visits in breakdown
            if ($category !== 'On-time') {
                $results[$ageGroup][$gender]++;
                $results[$ageGroup]['total']++;
            }
        }

        // Calculate rates
        $rates = [];
        foreach ($severityLevels as $level => $range) {
            $rates[$level] = $totalAppointments > 0
                ? round(($severityCounts[$level] / $totalAppointments) * 100, 2)
                : 0;
        }
        $overallMissedRate = $totalAppointments > 0
            ? round(($severityCounts['total_missed'] / $totalAppointments) * 100, 2)
            : 0;

        return [
            'indicator' => 'Missed visit rates by severity',
            'total' => $totalAppointments,
            'overall_missed_rate' => $overallMissedRate,
            'severity_counts' => $severityCounts,
            'severity_rates' => $rates,
            'breakdown' => $results,
        ];
    }

    protected function calculateAppointmentAdherence($cohortId, $siteId, $facilityId, $startDate, $endDate)
    {
        // Get all appointments within the period
        $appointmentsQuery = Visit::query()
            ->whereNotNull('app_visit_date')
            ->whereNotNull('actual_visit_date');

        // Apply filters
        $this->applyVisitFilters($appointmentsQuery, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        // Get total appointments
        $totalAppointments = $appointmentsQuery->count();

        // Get adherent appointments (within 7 days of appointment)
        $adherentQuery = clone $appointmentsQuery;
        $adherentAppointments = $adherentQuery->whereRaw('DATEDIFF(actual_visit_date, app_visit_date) <= 7')
            ->count();

        // Calculate adherence rate
        $adherenceRate = $totalAppointments > 0
            ? round(($adherentAppointments / $totalAppointments) * 100, 2)
            : 0;

        // Get breakdown by age and gender
        $breakdownQuery = Visit::join('patients', 'visits.patient_id', '=', 'patients.id')
            ->whereNotNull('app_visit_date')
            ->whereNotNull('actual_visit_date')
            ->whereRaw('DATEDIFF(actual_visit_date, app_visit_date) <= 7')
            ->select(
                'patients.date_of_birth',
                'patients.gender',
                'visits.actual_visit_date'
            );

        // Apply filters to breakdown query
        $this->applyVisitFilters($breakdownQuery, $cohortId, $siteId, $facilityId, $startDate, $endDate);

        $adherentVisits = $breakdownQuery->get();
        $results = $this->initializeResults();

        foreach ($adherentVisits as $visit) {
            $ageGroup = $this->getAgeGroup($visit->date_of_birth, $visit->actual_visit_date);
            $gender = $this->mapGender($visit->gender);

            $results[$ageGroup][$gender]++;
            $results[$ageGroup]['total']++;
        }

        return [
            'indicator' => 'Appointment adherence (within 7 days)',
            'adherent_appointments' => $adherentAppointments,
            'total' => $totalAppointments,
            'adherence_rate' => $adherenceRate,
            'breakdown' => $results,
        ];
    }

   protected function calculatePatientsInitiatedOnARTWhilstPregnant($cohortId, $siteId, $facilityId, $startDate, $endDate)
{
    // Get ART initiations where pregnancy was recorded at the time of initiation
    $query = Visit::with(['patient', 'visitDetails'])
        ->where('visit_type_id', 1) // Initial ART visit
        ->whereHas('visitDetails', function ($q) {
            $q->where('pregnant', 'Yes');
        });

    // Apply filters
    $this->applyVisitFilters($query, $cohortId, $siteId, $facilityId, $startDate, $endDate);

    $initiations = $query->get();

    $results = $this->initializeResults();
    $total = 0;

    foreach ($initiations as $visit) {
        $patient = $visit->patient;
        $artStartDate = Carbon::parse($visit->actual_visit_date);
        
        $ageGroup = $this->getAgeGroup($patient->date_of_birth, $artStartDate);
        $gender = $this->mapGender($patient->gender);

        $results[$ageGroup][$gender]++;
        $results[$ageGroup]['total']++;
        $total++;
    }

    // Get coverage description
    $coverage = $this->getCoverageDescription($cohortId, $siteId, $facilityId);

    return [
        'indicator' => 'Patients initiated on ART whilst pregnant',
        'total' => $total,
        'breakdown' => $results,
        'coverage' => $coverage, // Add coverage information
    ];
}

protected function calculateMedianDurationOnART($cohortId, $siteId, $facilityId, $startDate, $endDate)
{
    // Get active patients (not dead, not transferred out, not LTFU)
    $query = Patient::query()
        ->where('patient_status_id', '!=', 5) // Not dead
        ->whereDoesntHave('visits', function ($q) {
            $q->where('transfer_type_id', 2) // Not transferred out
                ->where('next_visit_date', '3000-01-01');
        })
        ->whereHas('initialVisit', function ($q) {
            $q->whereNotNull('art_start_date')
              ->where('art_start_date', '!=', '3000-01-01') // Exclude placeholder
              ->where('art_start_date', '!=', '01-01-3000'); // Exclude placeholder
        });

    // Apply filters
    $this->applyFilters($query, $cohortId, $siteId, $facilityId);

    $patients = $query->get();

    $durations = [];
    // Initialize results with a different structure for this method
    $results = [];
    foreach (array_keys(self::AGE_GROUPS) as $group) {
        $results[$group] = [
            'male' => ['durations' => []],
            'female' => ['durations' => []],
            'other' => ['durations' => []],
            'total' => ['durations' => []],
        ];
    }
    $results['Unknown'] = [
        'male' => ['durations' => []],
        'female' => ['durations' => []],
        'other' => ['durations' => []],
        'total' => ['durations' => []],
    ];

    $totalActive = 0;

    foreach ($patients as $patient) {
        // Check if patient is LTFU (last visit > 90 days ago)
        $lastVisit = $patient->visits()
            ->whereNotNull('actual_visit_date')
            ->where('actual_visit_date', '!=', '3000-01-01') // Exclude placeholder
            ->where('actual_visit_date', '!=', '01-01-3000') // Exclude placeholder
            ->orderBy('actual_visit_date', 'desc')
            ->first();

        if ($lastVisit) {
            $daysSinceLastVisit = Carbon::parse($lastVisit->actual_visit_date)->diffInDays(now());
            // if ($daysSinceLastVisit > 90) {
            //     continue; // Skip LTFU patients
            // }
        }

        // Calculate ART duration
        $artStartDate = $patient->initialVisit->art_start_date;

        // Skip if ART start date is a placeholder
        if (!$artStartDate || $artStartDate == '3000-01-01' || $artStartDate == '01-01-3000') {
            continue;
        }

        $durationMonths = Carbon::parse($artStartDate)->diffInMonths(now());
        $durations[] = $durationMonths;

        // Add to breakdown
        $ageGroup = $this->getAgeGroup($patient->date_of_birth, now());
        $gender = $this->mapGender($patient->gender);

        // Ensure the age group exists in results
        if (!isset($results[$ageGroup])) {
            $ageGroup = 'Unknown';
        }

        $results[$ageGroup][$gender]['durations'][] = $durationMonths;
        $results[$ageGroup]['total']['durations'][] = $durationMonths;
        $totalActive++;
    }

    // Calculate overall median
    $medianDuration = $this->calculateMedian($durations);

    // Calculate medians for each group
    $breakdownMedians = [];
    foreach ($results as $ageGroup => $data) {
        $breakdownMedians[$ageGroup] = [
            'male' => 0,
            'female' => 0,
            'other' => 0,
            'total' => 0,
        ];

        foreach (['male', 'female', 'other'] as $gender) {
            if (!empty($data[$gender]['durations'])) {
                $breakdownMedians[$ageGroup][$gender] = $this->calculateMedian($data[$gender]['durations']);
            }
        }

        if (!empty($data['total']['durations'])) {
            $breakdownMedians[$ageGroup]['total'] = $this->calculateMedian($data['total']['durations']);
        }
    }

    // Get coverage description
    $coverage = $this->getCoverageDescription($cohortId, $siteId, $facilityId);

    return [
        'indicator' => 'Median duration on ART among active patients',
        'median_duration' => $medianDuration,
        'total_active_patients' => $totalActive,
        'breakdown' => $breakdownMedians,
        'coverage' => $coverage,
    ];
}


protected function calculateDeathsOverTime($cohortId, $siteId, $facilityId, $startDate, $endDate)
{
    // Default to last 12 months if no date range provided
    if (!$startDate || !$endDate) {
        $startDate = now()->subMonths(12)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
    }

    // Query deaths with proper date filtering
    $query = Visit::join('patients', 'visits.patient_id', '=', 'patients.id')
        ->where('patients.patient_status_id', 5) // Status 5 = Dead
        ->whereNotNull('visits.actual_visit_date')
        ->where('visits.actual_visit_date', '!=', '3000-01-01')
        ->where('visits.actual_visit_date', '!=', '01-01-3000')
        ->whereBetween('visits.actual_visit_date', [$startDate, $endDate])
        ->select(
            'visits.actual_visit_date',
            'patients.gender',
            DB::raw('YEAR(visits.actual_visit_date) as year'),
            DB::raw('MONTH(visits.actual_visit_date) as month')
        );

    // Apply filters
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

    $deaths = $query->get();

    // Initialize data structure for the graph
    $labels = [];
    $maleData = [];
    $femaleData = [];
    $otherData = [];

    // Generate all months in the date range
    $current = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);
    
    while ($current <= $end) {
        $monthYear = $current->format('M Y');
        $labels[] = $monthYear;
        $maleData[$monthYear] = 0;
        $femaleData[$monthYear] = 0;
        $otherData[$monthYear] = 0;
        $current->addMonth();
    }

    // Count deaths by month and gender
    foreach ($deaths as $death) {
        $deathDate = Carbon::parse($death->actual_visit_date);
        $monthYear = $deathDate->format('M Y');
        $gender = $this->mapGender($death->gender);

        if (array_key_exists($monthYear, $maleData)) {
            switch ($gender) {
                case 'male':
                    $maleData[$monthYear]++;
                    break;
                case 'female':
                    $femaleData[$monthYear]++;
                    break;
                default:
                    $otherData[$monthYear]++;
                    break;
            }
        }
    }

    // Convert associative arrays to indexed arrays for the graph
    $maleValues = array_values($maleData);
    $femaleValues = array_values($femaleData);
    $otherValues = array_values($otherData);

    // Get coverage description
    $coverage = $this->getCoverageDescription($cohortId, $siteId, $facilityId);

    return [
        'indicator' => 'Deaths over time',
        'total_deaths' => $deaths->count(),
        'time_period' => [
            'start' => $startDate,
            'end' => $endDate,
        ],
        'coverage' => $coverage,
        'chart_data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Male',
                    'data' => $maleValues,
                    'borderColor' => 'rgb(54, 162, 235)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                ],
                [
                    'label' => 'Female',
                    'data' => $femaleValues,
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                ],
                [
                    'label' => 'Other',
                    'data' => $otherValues,
                    'borderColor' => 'rgb(153, 102, 255)',
                    'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                ],
            ],
        ],
    ];
}

protected function calculateAgeAtDeath($cohortId, $siteId, $facilityId, $startDate, $endDate)
{
    // Query deaths with proper filtering
    $query = Visit::join('patients', 'visits.patient_id', '=', 'patients.id')
        ->where('patients.patient_status_id', 5) // Status 5 = Dead
        ->whereNotNull('visits.actual_visit_date')
        ->where('visits.actual_visit_date', '!=', '3000-01-01')
        ->where('visits.actual_visit_date', '!=', '01-01-3000')
        ->whereNotNull('patients.date_of_birth')
        ->select(
            'patients.date_of_birth',
            'patients.gender',
            'visits.actual_visit_date as death_date'
        );

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

    $deaths = $query->get();

    // Age groups for distribution - FIXED: Use inclusive ranges
    $ageGroups = [
        '0-17 years' => [0, 17],
        '18-24 years' => [18, 24],
        '25-34 years' => [25, 34],
        '35-44 years' => [35, 44],
        '45-54 years' => [45, 54],
        '55-64 years' => [55, 64],
        '65+ years' => [65, null]
    ];

    // Initialize distribution arrays
    $ageDistribution = [];
    $genderDistribution = [
        'male' => [],
        'female' => [],
        'other' => []
    ];
    $agesAtDeath = [];

    foreach ($ageGroups as $group => $range) {
        $ageDistribution[$group] = [
            'male' => 0,
            'female' => 0,
            'other' => 0,
            'total' => 0
        ];
    }

    // Calculate ages at death and populate distributions
    foreach ($deaths as $death) {
        $birthDate = Carbon::parse($death->date_of_birth);
        $deathDate = Carbon::parse($death->death_date);
        //$ageAtDeath = $deathDate->diffInYears($birthDate);
        $ageAtDeath = -1*($deathDate->diffInYears($birthDate));
         Log::info("Death: {$death->death_date}, Birth: {$death->date_of_birth}, Age at Death: {$ageAtDeath}");
        $gender = $this->mapGender($death->gender);

        $agesAtDeath[] = $ageAtDeath;

        // Find age group - FIXED: Use inclusive ranges
        $ageGroup = '65+ years'; // Default for ages 65 and above
        
        foreach ($ageGroups as $group => $range) {
            [$min, $max] = $range;
            
            if ($max === null) {
                // For open-ended range (65+)
                if ($ageAtDeath >= $min) {
                    $ageGroup = $group;
                    break;
                }
            } else {
                // For closed ranges - FIXED: Use inclusive bounds
                if ($ageAtDeath >= $min && $ageAtDeath <= $max) {
                    $ageGroup = $group;
                    break;
                }
            }
        }

        // Update distributions
        $ageDistribution[$ageGroup][$gender]++;
        $ageDistribution[$ageGroup]['total']++;
        $genderDistribution[$gender][] = $ageAtDeath;
    }

    // Calculate statistics
    $totalDeaths = count($deaths);
    $medianAge = $totalDeaths > 0 ? $this->calculateMedian($agesAtDeath) : 0;
    $averageAge = $totalDeaths > 0 ? round(array_sum($agesAtDeath) / $totalDeaths, 1) : 0;

    // Calculate gender-specific medians
    $genderMedians = [];
    foreach ($genderDistribution as $gender => $ages) {
        $genderMedians[$gender] = count($ages) > 0 ? $this->calculateMedian($ages) : 0;
    }

    return [
        'indicator' => 'Age at death distribution',
        'total_deaths' => $totalDeaths,
        'statistics' => [
            'median_age' => $medianAge,
            'average_age' => $averageAge,
            'gender_medians' => $genderMedians,
            'min_age' => $totalDeaths > 0 ? min($agesAtDeath) : 0,
            'max_age' => $totalDeaths > 0 ? max($agesAtDeath) : 0,
        ],
        'age_distribution' => $ageDistribution,
        'coverage' => $this->getCoverageDescription($cohortId, $siteId, $facilityId),
    ];
}


protected function calculateSurvivalAnalysis($cohortId, $siteId, $facilityId, $startDate, $endDate)
{
    // Get patients who died and have ART start dates
    $query = Patient::where('patient_status_id', 5) // Dead
        ->whereHas('initialVisit', function ($q) {
            $q->whereNotNull('art_start_date')
              ->where('art_start_date', '!=', '3000-01-01')
              ->where('art_start_date', '!=', '01-01-3000');
        })
        ->with(['initialVisit']);

    // Apply filters
    $this->applyFilters($query, $cohortId, $siteId, $facilityId);

    $deceasedPatients = $query->get();

    $survivalTimes = [];
    $survivalByAge = [];
    $survivalByGender = [
        'male' => [],
        'female' => [],
        'other' => []
    ];

    // Time intervals for survival analysis (in months)
    $timeIntervals = [6, 12, 24, 36, 60, 120]; // 6m, 1y, 2y, 3y, 5y, 10y

    foreach ($deceasedPatients as $patient) {
        $artStartDate = Carbon::parse($patient->initialVisit->art_start_date);
        $deathDate = $patient->date_of_death 
            ? Carbon::parse($patient->date_of_death)
            : Carbon::parse($patient->updated_at); // Fallback to last update
        
        $survivalMonths = $artStartDate->diffInMonths($deathDate);
        $survivalYears = round($survivalMonths / 12, 1);
        
        $ageAtArtStart = $artStartDate->diffInYears(Carbon::parse($patient->date_of_birth));
        $gender = $this->mapGender($patient->gender);

        $survivalTimes[] = $survivalMonths;
        $survivalByGender[$gender][] = $survivalMonths;
        
        // Group by age at ART start
        $ageGroup = $this->categorizeAge($ageAtArtStart);
        if (!isset($survivalByAge[$ageGroup])) {
            $survivalByAge[$ageGroup] = [];
        }
        $survivalByAge[$ageGroup][] = $survivalMonths;
    }

    // Calculate survival statistics
    $totalPatients = count($deceasedPatients);
    $medianSurvival = $totalPatients > 0 ? $this->calculateMedian($survivalTimes) : 0;

    // Calculate survival rates at different time points
    $survivalRates = [];
    foreach ($timeIntervals as $months) {
        $survivedCount = count(array_filter($survivalTimes, function ($time) use ($months) {
            return $time >= $months;
        }));
        $rate = $totalPatients > 0 ? round(($survivedCount / $totalPatients) * 100, 1) : 0;
        $survivalRates[$months . '_months'] = $rate;
    }

    // Calculate medians by group
    $medianByAge = [];
    foreach ($survivalByAge as $ageGroup => $times) {
        $medianByAge[$ageGroup] = count($times) > 0 ? $this->calculateMedian($times) : 0;
    }

    $medianByGender = [];
    foreach ($survivalByGender as $gender => $times) {
        $medianByGender[$gender] = count($times) > 0 ? $this->calculateMedian($times) : 0;
    }

    return [
        'indicator' => 'Survival analysis after ART initiation',
        'total_patients' => $totalPatients,
        'survival_statistics' => [
            'median_survival_months' => $medianSurvival,
            'median_survival_years' => round($medianSurvival / 12, 1),
            'survival_rates' => $survivalRates,
        ],
        'by_age_group' => $medianByAge,
        'by_gender' => $medianByGender,
        'coverage' => $this->getCoverageDescription($cohortId, $siteId, $facilityId),
    ];
}

// Helper method for age categorization
protected function categorizeAge($age)
{
    if ($age < 18) return '0-17 years';
    if ($age < 25) return '18-24 years';
    if ($age < 35) return '25-34 years';
    if ($age < 45) return '35-44 years';
    if ($age < 55) return '45-54 years';
    if ($age < 65) return '55-64 years';
    return '65+ years';
}

    // coverage
    protected function getCoverageDescription($cohortId, $siteId, $facilityId)
    {
        if ($facilityId) {
            $facility = Facility::find($facilityId);

            return $facility ? $facility->name : 'Specific facility';
        }

        if ($siteId) {
            $site = Site::find($siteId);

            return $site ? "All facilities in {$site->name}" : 'Specific site';
        }

        if ($cohortId) {
            $cohort = Cohort::find($cohortId);

            return $cohort ? "All sites in {$cohort->name}" : 'Specific cohort';
        }

        return 'All facilities in all cohorts';
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
