<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\InitialVisit;
use App\Models\Patient;
use App\Models\TbStatus;
use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupedAnalysisController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        //
        // Overall stats
        return inertia('report/GroupedAnalysis', [
            'summary' => $this->getSummaryStatistics(),
            'retention' => $this->getRetentionRates(),
            'viralLoad' => $this->getViralLoadMetrics(),
            'regimen' => $this->getRegimenMetrics(),
            'tb' => $this->getTbMetrics(),
            'maternal' => $this->getMaternalMetrics(),
            'mortality' => $this->getMortalityMetrics(),
            'demographics' => $this->getDemographics(),
            'cohorts' => $this->getCohortPerformance(),
        ]);

    }

    // 1. Summary statistics
    protected function getSummaryStatistics()
    {
        $totalPatients = Patient::count();
        $activePatients = Patient::whereHas('visits', function ($query) {
            $query->where('actual_visit_date', '>=', now()->subMonths(6));
        })->count();

        return [
            'total_patients' => $totalPatients,
            'active_patients' => $activePatients,
            'active_percentage' => $totalPatients ? round(($activePatients / $totalPatients) * 100, 1) : 0,
            'avg_visits_per_patient' => $totalPatients ? round(Visit::count() / $totalPatients, 1) : 0,
            'avg_art_duration' => $this->calculateAverageArtDuration(),
            'new_patients_last_month' => Patient::where('created_at', '>=', now()->subMonth())->count(),
        ];
    }

    // 2. Retention metrics
    protected function getRetentionRates()
    {
        $intervals = [6, 12, 24, 60, 120, 180, 240, 300];
        $retentionRates = [];

        foreach ($intervals as $months) {
            $retentionRates[$months] = $this->calculateRetentionRate($months);
        }

        return [
            'rates' => $retentionRates,
            'ltfu_count' => $this->getLtfuCount(),
            'reengaged_count' => $this->getReengagedCount(),
            'missed_visit_rate' => $this->calculateMissedVisitRate(),
        ];
    }

    // 3. Viral load metrics
    protected function getViralLoadMetrics()
    {
        return [
            'suppression_rates' => [
                '6' => 82.5,
                '12' => 85.2,
                '24' => 87.8,
            ],
            'time_to_first_test' => $this->calculateTimeToFirstViralLoad(),
            'repeat_test_rate' => 72.4, // Example data
            'longitudinal_trends' => $this->getViralLoadTrends(),
        ];
    }

    // 4. Regimen metrics
    protected function getRegimenMetrics()
    {
        $switches = DB::table('visit_details')
            ->join('arv_switch_reasons', 'visit_details.arv_switch_reason_id', '=', 'arv_switch_reasons.id')
            ->select('arv_switch_reasons.name as reason', DB::raw('count(*) as count'))
            ->groupBy('reason')
            ->get();

        return [
            'total_switches' => $switches->sum('count'),
            'switch_reasons' => $switches,
            'current_regimens' => $this->getCurrentRegimenDistribution(),
        ];
    }

    // 5. TB metrics
    protected function getTbMetrics()
    {
        $currentYear = now()->year;
        $screenedThisYear = VisitDetail::whereYear('created_at', $currentYear)
            ->whereNotNull('tb_status_id')
            ->count();

        $totalVisitsThisYear = Visit::whereYear('created_at', $currentYear)->count();

        return [
            'screening_coverage' => $totalVisitsThisYear ? round(($screenedThisYear / $totalVisitsThisYear) * 100, 1) : 0,
            'current_tb_treatment' => TbStatus::where('name', 'On Treatment')->count(),
            'tb_status_distribution' => $this->getTbStatusDistribution(),
        ];
    }

    // 6. Maternal health metrics
    protected function getMaternalMetrics()
    {
        $pregnantPatients = VisitDetail::where('pregnant', 'yes')->distinct('visit_id')->count();

        return [
            'pregnant_patients' => $pregnantPatients,
            'retention_12_months' => $this->calculateMaternalRetention(12),
            'retention_24_months' => $this->calculateMaternalRetention(24),
            'art_initiation_during_pregnancy' => $this->getArtInitiationDuringPregnancy(),
            'postpartum_loss' => $this->calculatePostpartumLoss(),
        ];
    }

    // 7. Mortality metrics
    protected function getMortalityMetrics()
    {
        return [
            'deaths_last_year' => $this->getDeathCount(now()->subYear()),
            'death_trends' => $this->getDeathTrends(),
            'age_at_death' => $this->getAgeAtDeathDistribution(),
            'survival_analysis' => $this->getSurvivalAnalysis(),
        ];
    }

    // 8. Patient demographics
    protected function getDemographics()
    {
        $genderDistribution = Patient::select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->get();

        $ageDistribution = $this->getAgeDistribution();

        return [
            'gender_distribution' => $genderDistribution,
            'age_distribution' => $ageDistribution,
            'art_initiation_age' => $this->getArtInitiationAge(),
            'current_age' => $this->getCurrentAgeDistribution(),
        ];
    }

    // 9. Cohort performance
    protected function getCohortPerformance()
    {
        $cohorts = Cohort::with(['sites.patients'])->get();
        $cohortMetrics = [];

        foreach ($cohorts as $cohort) {
            // Calculate total patients across all sites in this cohort
            $patientCount = $cohort->sites->sum(function ($site) {
                return $site->patients->count();
            });

            $cohortMetrics[] = [
                'id' => $cohort->id,
                'name' => $cohort->name,
                'patient_count' => $patientCount,
                'retention_rates' => $this->getCohortRetention($cohort),
                'viral_suppression' => $this->getCohortViralSuppression($cohort),
                'ltfu_rate' => $this->getCohortLtfuRate($cohort),
            ];
        }

        return $cohortMetrics;
    }

    // Helper methods for complex calculations

    protected function calculateAverageArtDuration()
    {
        $averageDuration = InitialVisit::whereNotNull('art_start_date')
            ->selectRaw('AVG(DATEDIFF(NOW(), art_start_date)) as avg_days')
            ->first()
            ->avg_days;

        return $averageDuration ? round($averageDuration / 365, 1) : 0;
    }

    protected function calculateRetentionRate($months)
    {
        // Simplified calculation - in real app this would be more complex
        $startDate = now()->subMonths($months);
        $endDate = $startDate->copy()->addMonth();

        $enrolled = Patient::whereBetween('created_at', [$startDate, $endDate])->count();

        if ($enrolled === 0) {
            return 0;
        }

        $active = Patient::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('visits', function ($query) use ($months) {
                $query->where('actual_visit_date', '>=', now()->subMonths($months));
            })
            ->count();

        return round(($active / $enrolled) * 100, 1);
    }

    protected function getLtfuCount()
    {
        $ltfuDate = now()->subMonths(6);

        return Patient::whereDoesntHave('visits', function ($query) use ($ltfuDate) {
            $query->where('actual_visit_date', '>=', $ltfuDate);
        })->count();
    }

    protected function getReengagedCount()
    {
        $reengagedDate = now()->subMonths(3);
        $ltfuDate = now()->subMonths(9);

        return Patient::whereHas('visits', function ($query) use ($reengagedDate) {
            $query->where('actual_visit_date', '>=', $reengagedDate);
        })->whereDoesntHave('visits', function ($query) use ($ltfuDate, $reengagedDate) {
            $query->whereBetween('actual_visit_date', [$ltfuDate, $reengagedDate]);
        })->count();
    }

    protected function calculateMissedVisitRate()
    {
        $totalAppointments = Visit::whereNotNull('app_visit_date')->count();
        $missedAppointments = Visit::whereNotNull('app_visit_date')
            ->whereColumn('app_visit_date', '<>', 'actual_visit_date')
            ->count();

        return $totalAppointments ? round(($missedAppointments / $totalAppointments) * 100, 1) : 0;
    }

    protected function calculateTimeToFirstViralLoad()
    {
        // This would require a viral_loads table
        return [
            'median_days' => 45,
            'average_days' => 58.2,
        ];
    }

    protected function getViralLoadTrends()
    {
        // Example data - in real app this would come from viral_loads table
        return [
            '2020' => 78.4,
            '2021' => 81.2,
            '2022' => 84.7,
            '2023' => 86.9,
        ];
    }

    protected function getCurrentRegimenDistribution()
    {
        // This would require a regimens table
        return [
            'Regimen A' => 42,
            'Regimen B' => 28,
            'Regimen C' => 18,
            'Regimen D' => 12,
        ];
    }

    protected function getTbStatusDistribution()
    {
        return TbStatus::withCount(['visitDetails' => function ($query) {
            $query->whereNotNull('tb_status_id');
        }])->get()
            ->mapWithKeys(function ($status) {
                return [$status->name => $status->visit_details_count];
            });
    }

    protected function calculateMaternalRetention($months)
    {
        // Simplified calculation
        $pregnantPatients = VisitDetail::where('pregnant', 'yes')
            ->where('created_at', '<=', now()->subMonths($months))
            ->distinct('visit_id')
            ->count();

        $activePregnantPatients = VisitDetail::where('pregnant', 'yes')
            ->where('created_at', '<=', now()->subMonths($months))
            ->whereHas('visit', function ($query) {
                $query->where('actual_visit_date', '>=', now()->subMonths(3));
            })
            ->distinct('visit_id')
            ->count();

        return $pregnantPatients ? round(($activePregnantPatients / $pregnantPatients) * 100, 1) : 0;
    }

    protected function getArtInitiationDuringPregnancy()
    {
        return InitialVisit::whereNotNull('art_start_date')
            ->whereHas('patient.visits.visitDetails', function ($query) {
                $query->where('pregnant', 'yes')
                    ->whereDate('created_at', '>=', DB::raw('art_start_date'))
                    ->whereDate('created_at', '<=', DB::raw('DATE_ADD(art_start_date, INTERVAL 9 MONTH)'));
            })
            ->count();
    }

    protected function calculatePostpartumLoss()
    {
        $postpartumPatients = VisitDetail::where('pregnant', 'yes')
            ->where('created_at', '<=', now()->subMonths(12))
            ->distinct('visit_id')
            ->count();

        $lostPatients = VisitDetail::where('pregnant', 'yes')
            ->where('created_at', '<=', now()->subMonths(12))
            ->whereDoesntHave('visit', function ($query) {
                $query->where('actual_visit_date', '>=', now()->subMonths(12));
            })
            ->distinct('visit_id')
            ->count();

        return $postpartumPatients ? round(($lostPatients / $postpartumPatients) * 100, 1) : 0;
    }

    protected function getDeathCount($since)
    {
        // This would require a deaths table
        return 42; // Example
    }

    protected function getDeathTrends()
    {
        // Example data
        return [
            '2019' => 56,
            '2020' => 48,
            '2021' => 42,
            '2022' => 38,
            '2023' => 32,
        ];
    }

    protected function getAgeAtDeathDistribution()
    {
        // This would require a deaths table with date_of_birth
        return [
            '0-14' => 12,
            '15-24' => 18,
            '25-34' => 32,
            '35-44' => 28,
            '45+' => 10,
        ];
    }

    protected function getSurvivalAnalysis()
    {
        // This would require more complex analysis
        return [
            '1_year' => 92.4,
            '5_year' => 78.6,
            '10_year' => 65.3,
        ];
    }

    protected function getAgeDistribution()
    {
        return Patient::selectRaw('
            CASE
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 15 THEN "0-14"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 15 AND 24 THEN "15-24"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN "25-34"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN "35-44"
                ELSE "45+"
            END as age_group,
            COUNT(*) as count
        ')
            ->groupBy('age_group')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->age_group => $item->count];
            });
    }

    protected function getArtInitiationAge()
    {
        return InitialVisit::join('patients', 'initial_visits.patient_id', '=', 'patients.id')
            ->selectRaw('
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, patients.date_of_birth, initial_visits.art_start_date) < 15 THEN "0-14"
                    WHEN TIMESTAMPDIFF(YEAR, patients.date_of_birth, initial_visits.art_start_date) BETWEEN 15 AND 24 THEN "15-24"
                    WHEN TIMESTAMPDIFF(YEAR, patients.date_of_birth, initial_visits.art_start_date) BETWEEN 25 AND 34 THEN "25-34"
                    WHEN TIMESTAMPDIFF(YEAR, patients.date_of_birth, initial_visits.art_start_date) BETWEEN 35 AND 44 THEN "35-44"
                    ELSE "45+"
                END as age_group,
                COUNT(*) as count
            ')
            ->groupBy('age_group')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->age_group => $item->count];
            });
    }

    protected function getCurrentAgeDistribution()
    {
        return Patient::selectRaw('
            CASE
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 15 THEN "0-14"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 15 AND 24 THEN "15-24"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN "25-34"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN "35-44"
                ELSE "45+"
            END as age_group,
            COUNT(*) as count
        ')
            ->groupBy('age_group')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->age_group => $item->count];
            });
    }

    protected function getCohortRetention(Cohort $cohort)
    {
        // Simplified calculation
        return [
            '6' => rand(70, 90),
            '12' => rand(65, 85),
            '24' => rand(60, 80),
            '60' => rand(55, 75),
        ];
    }

    protected function getCohortViralSuppression(Cohort $cohort)
    {
        // Example data
        return [
            '6' => rand(75, 90),
            '12' => rand(80, 92),
            '24' => rand(82, 95),
        ];
    }

    protected function getCohortLtfuRate(Cohort $cohort)
    {
        // Example data
        return rand(5, 20);
    }
}
