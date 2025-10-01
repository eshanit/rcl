<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\VisitDetail;
use App\Models\Cohort;
use App\Models\Site;
use App\Models\InitialVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getDashboardStats();
        $activities = $this->getRecentActivities();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'activities' => $activities,
        ]);
    }

    protected function getDashboardStats()
    {
        // 1. Medical Facilities - count of facilities
        $facilitiesCount = Facility::count();

        // 2. Patients on ART - count of patients with ART start dates
        $artPatientsCount = Patient::whereHas('initialVisit', function ($query) {
            $query->whereNotNull('art_start_date')
                  ->where('art_start_date', '!=', '3000-01-01')
                  ->where('art_start_date', '!=', '01-01-3000');
        })->count();

        // 3. Data Records - total number of patient visits
        $dataRecordsCount = Visit::count();

        // 4. Recent ART Initiations - patients starting ART in last 30 days
        $recentArtInitiations = Patient::whereHas('initialVisit', function ($query) {
            $query->whereNotNull('art_start_date')
                  ->where('art_start_date', '!=', '3000-01-01')
                  ->where('art_start_date', '!=', '01-01-3000')
                  ->where('art_start_date', '>=', Carbon::now()->subDays(30));
        })->count();

        // 5. Additional ART-focused stats
        $totalPatients = Patient::count();
        $activeCohorts = Cohort::count();
        $totalSites = Site::count();

        // Format large numbers with commas
        $formattedRecords = number_format($dataRecordsCount);

        return [
            [
                'title' => 'Medical Facilities',
                'value' => $facilitiesCount,
                'icon' => 'clinic-medical',
                'color' => 'text-blue-500',
                'bg' => 'bg-blue-100'
            ],
            [
                'title' => 'Patients on ART',
                'value' => $artPatientsCount,
                'icon' => 'pills',
                'color' => 'text-green-500',
                'bg' => 'bg-green-100'
            ],
            [
                'title' => 'Clinical Visits',
                'value' => $formattedRecords,
                'icon' => 'stethoscope',
                'color' => 'text-purple-500',
                'bg' => 'bg-purple-100'
            ],
            [
                'title' => 'Recent ART Starts',
                'value' => $recentArtInitiations > 0 ? $recentArtInitiations . ' in 30d' : 'No recent',
                'icon' => 'user-plus',
                'color' => 'text-amber-500',
                'bg' => 'bg-amber-100'
            ],
            // Additional ART-focused stats
            'additional_stats' => [
                'total_patients' => $totalPatients,
                'art_coverage' => $totalPatients > 0 ? round(($artPatientsCount / $totalPatients) * 100, 1) : 0,
                'active_cohorts' => $activeCohorts,
                'total_sites' => $totalSites,
            ]
        ];
    }

    protected function getRecentActivities()
    {
        $activities = [];

        // Get latest ART initiations
        $recentArtStarts = InitialVisit::with(['patient', 'patient.site'])
            ->whereNotNull('art_start_date')
            ->where('art_start_date', '!=', '3000-01-01')
            ->where('art_start_date', '!=', '01-01-3000')
            ->orderBy('art_start_date', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentArtStarts as $artStart) {
            $activities[] = [
                'title' => 'ART Initiation',
                'description' => 'Patient started ART at ' . ($artStart->patient->site->name ?? 'Unknown Site'),
                'time' => Carbon::parse($artStart->art_start_date)->diffForHumans(),
                'icon' => 'pills',
                'color' => 'text-green-500'
            ];
        }

        // Get recent clinical visits
        $recentVisits = Visit::with(['patient', 'facility'])
            ->whereNotNull('actual_visit_date')
            ->where('actual_visit_date', '!=', '3000-01-01')
            ->where('actual_visit_date', '!=', '01-01-3000')
            ->orderBy('actual_visit_date', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentVisits as $visit) {
            $activities[] = [
                'title' => 'Clinical Visit',
                'description' => 'Patient visit at ' . ($visit->facility->name ?? 'Unknown Facility'),
                'time' => Carbon::parse($visit->actual_visit_date)->diffForHumans(),
                'icon' => 'stethoscope',
                'color' => 'text-blue-500'
            ];
        }

        // Sort activities by time (most recent first) and take top 5
        usort($activities, function ($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return array_slice($activities, 0, 5);
    }

    // ART-focused advanced statistics
    public function getAdvancedStats()
    {
        // Patient growth by ART start date (last 6 months)
        $patientGrowth = Patient::selectRaw('
            YEAR(initial_visits.art_start_date) as year,
            MONTH(initial_visits.art_start_date) as month,
            COUNT(*) as count
        ')
        ->join('initial_visits', 'patients.id', '=', 'initial_visits.patient_id')
        ->whereNotNull('initial_visits.art_start_date')
        ->where('initial_visits.art_start_date', '!=', '3000-01-01')
        ->where('initial_visits.art_start_date', '!=', '01-01-3000')
        ->where('initial_visits.art_start_date', '>=', Carbon::now()->subMonths(6))
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->get();

        // ART patient demographics
        $artDemographics = Patient::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN gender = "male" OR gender = "M" THEN 1 ELSE 0 END) as male,
            SUM(CASE WHEN gender = "female" OR gender = "F" THEN 1 ELSE 0 END) as female,
            SUM(CASE WHEN (gender != "male" AND gender != "M" AND gender != "female" AND gender != "F") THEN 1 ELSE 0 END) as other
        ')
        ->whereHas('initialVisit', function ($query) {
            $query->whereNotNull('art_start_date')
                  ->where('art_start_date', '!=', '3000-01-01')
                  ->where('art_start_date', '!=', '01-01-3000');
        })
        ->first();

        // Current patients on ART by age group
        $ageGroups = [
            '0-17 years' => [0, 17],
            '18-24 years' => [18, 24],
            '25-34 years' => [25, 34],
            '35-44 years' => [35, 44],
            '45-54 years' => [45, 54],
            '55-64 years' => [55, 64],
            '65+ years' => [65, null]
        ];

        $artByAgeGroup = [];
        foreach ($ageGroups as $group => $range) {
            [$min, $max] = $range;
            
            $query = Patient::whereHas('initialVisit', function ($q) {
                $q->whereNotNull('art_start_date')
                  ->where('art_start_date', '!=', '3000-01-01')
                  ->where('art_start_date', '!=', '01-01-3000');
            });

            if ($max === null) {
                $count = $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$min])->count();
            } else {
                $count = $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?', [$min, $max])->count();
            }

            $artByAgeGroup[$group] = $count;
        }

        return response()->json([
            'patient_growth' => $patientGrowth,
            'art_demographics' => $artDemographics,
            'art_by_age_group' => $artByAgeGroup,
        ]);
    }

    // API endpoint for real-time stats
    public function getStats()
    {
        $stats = $this->getDashboardStats();
        $activities = $this->getRecentActivities();

        return response()->json([
            'stats' => $stats,
            'activities' => $activities,
        ]);
    }
}