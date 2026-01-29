<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Facility;
use App\Models\InitialVisit;
use App\Models\Patient;
use App\Models\Site;
use App\Models\TbStatus;
use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GroupedAnalysisController extends Controller
{
    // Cache durations
    private const CACHE_DURATIONS = [
        'summary' => 3600, // 1 hour
        'retention' => 1800, // 30 minutes
        'viral_load' => 3600,
        'regimen' => 7200, // 2 hours
        'tb' => 3600,
        'maternal' => 3600,
        'mortality' => 86400, // 24 hours
        'demographics' => 7200,
        'cohorts' => 7200,
    ];

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Log incoming request parameters
        \Log::info('GroupedAnalysisController - Request received', [
            'query_params' => $request->query(),
            'has_cohort_id' => $request->filled('cohort_id'),
            'has_site_id' => $request->filled('site_id'),
            'has_facility_id' => $request->filled('facility_id'),
            'cohort_id' => $request->input('cohort_id'),
            'site_id' => $request->input('site_id'),
            'facility_id' => $request->input('facility_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        // Check if filters are applied - check if ANY filter is filled
        $hasFilters = $request->filled('cohort_id') 
            || $request->filled('site_id') 
            || $request->filled('facility_id') 
            || $request->filled('start_date') 
            || $request->filled('end_date');

        \Log::info('GroupedAnalysisController - Filter detection', [
            'hasFilters' => $hasFilters,
            'filters_used' => $hasFilters ? 'filtered' : 'unfiltered',
        ]);

        if ($hasFilters) {
            // Generate cache key based on filters
            $cacheKey = 'grouped_analysis_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));

            $data = Cache::remember($cacheKey, 900, function () use ($request) { // 15 minutes for filtered data
                return [
                    'summary' => $this->getSummaryStatistics($request),
                    'retention' => $this->getRetentionRates($request),
                    'deaths' => $this->getDeathsAnalysis($request),
                    // Other sections will be loaded lazily via AJAX
                ];
            });
        } else {
            // Use default cache for unfiltered data
            $data = Cache::remember('grouped_analysis_full', 1800, function () {
                return [
                    'summary' => $this->getSummaryStatistics(),
                    'retention' => $this->getRetentionRates(),
                    'deaths' => $this->getDeathsAnalysis(),
                    // Other sections will be loaded lazily via AJAX
                ];
            });
        }

        // Add filter data - ensure these always load fresh (not cached)
        try {
            $data['cohorts'] = Cohort::select('id', 'name')->get()->toArray();
            $data['sites'] = Site::select('id', 'name', 'cohort_id')->get()->toArray();
            $data['facilities'] = Facility::select('id', 'name', 'site_id')->get()->toArray();
            
            \Log::info('GroupedAnalysisController - Filter data loaded', [
                'cohorts_count' => count($data['cohorts']),
                'sites_count' => count($data['sites']),
                'facilities_count' => count($data['facilities']),
                'first_cohort' => $data['cohorts'][0] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load filter data in GroupedAnalysisController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Provide empty arrays as fallback
            $data['cohorts'] = [];
            $data['sites'] = [];
            $data['facilities'] = [];
        }

        // Only load essential data initially
        return inertia('report/GroupedAnalysis', $data);
    }

    /**
     * API endpoint for lazy loading other sections
     */
    public function getSection(Request $request, $section)
    {
        $method = 'get' . ucfirst($section) . 'Metrics';
        
        if (method_exists($this, $method)) {
            $data = Cache::remember(
                "grouped_analysis_{$section}",
                self::CACHE_DURATIONS[$section] ?? 3600,
                fn() => $this->$method()
            );
            
            return response()->json($data);
        }
        
        return response()->json(['error' => 'Section not found'], 404);
    }

    // 1. Summary statistics - Optimized
    protected function getSummaryStatistics(Request $request = null)
    {
        $cacheKey = 'summary_statistics';
        if ($request) {
            $cacheKey .= '_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));
            \Log::info('getSummaryStatistics - with filters', [
                'request_data' => $request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date']),
            ]);
        } else {
            \Log::info('getSummaryStatistics - no filters');
        }

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['summary'], function () use ($request) {
            $query = Patient::query();

            // Apply filters if provided
            if ($request) {
                $this->applyPatientFilters($query, $request);
            }

            $totalPatients = $query->count();
            $sixMonthsAgo = now()->subMonths(6)->format('Y-m-d');

            // Single optimized query for active patients
            $activeQuery = Patient::whereHas('visits', function ($query) use ($sixMonthsAgo) {
                $query->where('actual_visit_date', '>=', $sixMonthsAgo);
            });

            if ($request) {
                $this->applyPatientFilters($activeQuery, $request);
            }

            $activePatients = $activeQuery->count();

            // Get multiple counts in one query
            $visitQuery = DB::table('visits');
            if ($request) {
                $this->applyVisitFiltersToQuery($visitQuery, $request);
            }

            $counts = $visitQuery->select([
                    DB::raw('COUNT(*) as total_visits'),
                    DB::raw('COUNT(DISTINCT patient_id) as patients_with_visits')
                ])
                ->first();

            // New patients query
            $newPatientsQuery = InitialVisit::where('art_start_date', '>=', now()->subMonth());
            if ($request) {
                $this->applyInitialVisitFilters($newPatientsQuery, $request);
            }

            return [
                'total_patients' => $totalPatients,
                'active_patients' => $activePatients,
                'active_percentage' => $totalPatients ? round(($activePatients / $totalPatients) * 100, 1) : 0,
                'avg_visits_per_patient' => $counts->patients_with_visits ?
                    round($counts->total_visits / $counts->patients_with_visits, 1) : 0,
                'avg_art_duration' => $this->calculateAverageArtDuration($request),
                'new_patients_last_month' => $newPatientsQuery->count(),
            ];
        });
    }

    // Calculate average ART duration
    protected function calculateAverageArtDuration(Request $request = null)
    {
        $cacheKey = 'avg_art_duration';
        if ($request) {
            $cacheKey .= '_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));
        }

        return Cache::remember($cacheKey, 3600, function () use ($request) {
            $query = InitialVisit::whereNotNull('art_start_date');

            if ($request) {
                $this->applyInitialVisitFilters($query, $request);
            }

            $averageDuration = $query->selectRaw('AVG(DATEDIFF(NOW(), art_start_date)) as avg_days')
                ->first()
                ->avg_days;

            return $averageDuration ? round($averageDuration / 365, 1) : 0;
        });
    }

    // 2. Retention metrics - Optimized
    protected function getRetentionRates(Request $request = null)
    {
        $cacheKey = 'retention_rates';
        if ($request) {
            $cacheKey .= '_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));
            \Log::info('getRetentionRates - with filters', [
                'request_data' => $request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date']),
            ]);
        } else {
            \Log::info('getRetentionRates - no filters');
        }

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['retention'], function () use ($request) {
            // Load LTFU count once and reuse
            $ltfuCount = $this->getLtfuCount($request);

            return [
                'rates' => $this->calculateAllRetentionRates($request),
                'ltfu_count' => $ltfuCount,
                'reengaged_count' => $this->getReengagedCount($request),
                'missed_visit_rate' => $this->calculateMissedVisitRate($request),
            ];
        });
    }

    // Deaths Analysis - Get deaths data with trends
    protected function getDeathsAnalysis(Request $request = null)
    {
        $cacheKey = 'deaths_analysis';
        if ($request) {
            $cacheKey .= '_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));
            \Log::info('getDeathsAnalysis - with filters', [
                'request_data' => $request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date']),
            ]);
        } else {
            \Log::info('getDeathsAnalysis - no filters');
        }

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['mortality'], function () use ($request) {
            return [
                'total_deaths' => $this->getTotalDeaths($request),
                'deaths_last_year' => $this->getDeathsLastYear($request),
                'death_rate' => $this->calculateMortalityRate($request),
                'avg_age_at_death' => $this->getAverageAgeAtDeath($request),
                'trends' => $this->getDeathTrendsOverTime($request),
                'quarterly_trends' => $this->getDeathTrendsByQuarter($request),
                'age_distribution' => $this->getAgeAtDeathDistribution($request),
            ];
        });
    }

    // Optimized: Calculate all retention rates in one go
    protected function calculateAllRetentionRates(Request $request = null)
    {
        $intervals = [6, 12, 24, 60, 120, 180, 240, 300];
        $retentionRates = [];

        // Get all eligible patients once
        $allEligiblePatients = [];
        foreach ($intervals as $months) {
            $initiationThreshold = now()->subMonths($months);
            $query = Patient::whereHas('initialVisit', function ($query) use ($initiationThreshold) {
                $query->where('art_start_date', '<=', $initiationThreshold);
            });

            if ($request) {
                $this->applyPatientFilters($query, $request);
            }

            $allEligiblePatients[$months] = $query->pluck('id')->toArray();
        }
        
        // Get patient statuses in bulk
        $patientStatuses = Patient::select(['id', 'patient_status_id'])
            ->whereIn('id', array_unique(array_merge(...array_values($allEligiblePatients))))
            ->get()
            ->keyBy('id');
        
        // Get last visits in bulk
        $patientLastVisits = Visit::select([
                'patient_id',
                DB::raw('MAX(actual_visit_date) as last_visit_date'),
                DB::raw('MAX(next_visit_date) as last_next_visit_date')
            ])
            ->whereNotNull('next_visit_date')
            ->where('next_visit_date', '!=', '3000-01-01')
            ->whereIn('patient_id', array_unique(array_merge(...array_values($allEligiblePatients))))
            ->groupBy('patient_id')
            ->get()
            ->keyBy('patient_id');
        
        // Check transfer out status in bulk
        $transferredOutPatients = Visit::select('patient_id')
            ->where('transfer_type_id', 2)
            ->where('next_visit_date', '3000-01-01')
            ->whereIn('patient_id', array_unique(array_merge(...array_values($allEligiblePatients))))
            ->groupBy('patient_id')
            ->pluck('patient_id')
            ->toArray();
        
        // Calculate retention for each interval
        foreach ($intervals as $months) {
            if (empty($allEligiblePatients[$months])) {
                $retentionRates[$months] = 0;
                continue;
            }
            
            $retainedCount = 0;
            $eligibleCount = count($allEligiblePatients[$months]);
            
            foreach ($allEligiblePatients[$months] as $patientId) {
                // Skip dead patients (status = Died and date of death filled)
                $patientStatus = $patientStatuses->get($patientId);
                if ($patientStatus && $patientStatus->patient_status_id == 5 && $patientStatus->status_date) {
                    $eligibleCount--;
                    continue;
                }
                
                // Skip transferred out patients
                if (in_array($patientId, $transferredOutPatients)) {
                    $eligibleCount--;
                    continue;
                }
                
                // Check LTFU status
                $lastVisit = $patientLastVisits->get($patientId);
                $isLTFU = false;
                
                if ($lastVisit && $lastVisit->last_next_visit_date) {
                    $nextVisitDue = \Carbon\Carbon::parse($lastVisit->last_next_visit_date);
                    $daysLate = now()->diffInDays($nextVisitDue, false);

                    if ($daysLate > 90) { // Using 90 days as per definition
                        $isLTFU = true;
                    }
                } else {
                    // No visits with next_visit_date - consider LTFU
                    $isLTFU = true;
                }
                
                if (!$isLTFU) {
                    $retainedCount++;
                }
            }
            
            $retentionRates[$months] = [
                'count' => $retainedCount,
                'total' => $eligibleCount,
                'percentage' => $eligibleCount > 0 ? round(($retainedCount / $eligibleCount) * 100, 1) : 0
            ];
        }
        
        return $retentionRates;
    }

    // Optimized LTFU count
    protected function getLtfuCount(Request $request = null)
    {
        $cacheKey = 'ltfu_count';
        if ($request) {
            $cacheKey .= '_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));
        }

        return Cache::remember($cacheKey, 1800, function () use ($request) {
            // Use subquery for better performance
            $subquery = Visit::select([
                    'patient_id',
                    DB::raw('MAX(next_visit_date) as max_next_visit_date')
                ])
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '!=', '3000-01-01');

            if ($request) {
                $this->applyVisitFiltersToQuery($subquery, $request);
            }

            $subquery->groupBy('patient_id');

            $query = DB::table('patients as p')
                ->leftJoinSub($subquery, 'last_visits', function ($join) {
                    $join->on('p.id', '=', 'last_visits.patient_id');
                })
                ->where(function ($query) {
                    $query->where('p.patient_status_id', '!=', 5)
                          ->orWhereNull('p.status_date'); // Not dead if no date of death
                })
                ->where(function ($query) {
                    $query->whereNull('last_visits.max_next_visit_date')
                        ->orWhereRaw('DATEDIFF(NOW(), last_visits.max_next_visit_date) > 90'); // Using 90 days as per definition
                })
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('visits as v')
                        ->whereColumn('v.patient_id', 'p.id')
                        ->where('v.transfer_type_id', 2)
                        ->where('v.next_visit_date', '3000-01-01');
                });

            if ($request) {
                if ($request->filled('cohort_id')) {
                    $query->join('sites', 'p.site_id', '=', 'sites.id')
                          ->where('sites.cohort_id', $request->cohort_id);
                }

                if ($request->filled('site_id')) {
                    $query->where('p.site_id', $request->site_id);
                }

                if ($request->filled('facility_id')) {
                    $query->whereExists(function ($sub) use ($request) {
                        $sub->select(DB::raw(1))
                            ->from('visits as v2')
                            ->whereColumn('v2.patient_id', 'p.id')
                            ->where('v2.facility_id', $request->facility_id);
                    });
                }

                if ($request->filled('start_date')) {
                    $query->whereExists(function ($sub) use ($request) {
                        $sub->select(DB::raw(1))
                            ->from('initial_visits as iv')
                            ->whereColumn('iv.patient_id', 'p.id')
                            ->where('iv.art_start_date', '>=', $request->start_date);
                    });
                }

                if ($request->filled('end_date')) {
                    $query->whereExists(function ($sub) use ($request) {
                        $sub->select(DB::raw(1))
                            ->from('initial_visits as iv')
                            ->whereColumn('iv.patient_id', 'p.id')
                            ->where('iv.art_start_date', '<=', $request->end_date);
                    });
                }
            }

            return $query->count();
        });
    }

    protected function getReengagedCount(Request $request = null)
    {
        $cacheKey = 'reengaged_count';
        if ($request) {
            $cacheKey .= '_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));
        }

        return Cache::remember($cacheKey, 1800, function () use ($request) {
            $reengagedDate = now()->subMonths(3);
            $ltfuDate = now()->subMonths(9);

            $query = Patient::whereHas('visits', function ($query) use ($reengagedDate) {
                $query->where('actual_visit_date', '>=', $reengagedDate);
            })->whereDoesntHave('visits', function ($query) use ($ltfuDate, $reengagedDate) {
                $query->whereBetween('actual_visit_date', [$ltfuDate, $reengagedDate]);
            });

            if ($request) {
                $this->applyPatientFilters($query, $request);
            }

            return $query->count();
        });
    }

    protected function calculateMissedVisitRate(Request $request = null)
    {
        $cacheKey = 'missed_visit_rate';
        if ($request) {
            $cacheKey .= '_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));
        }

        return Cache::remember($cacheKey, 1800, function () use ($request) {
            $query = Visit::whereNotNull('app_visit_date');
            if ($request) {
                $this->applyVisitFiltersToQuery($query, $request);
            }

            $totalAppointments = $query->count();

            $missedQuery = Visit::whereNotNull('app_visit_date')
                ->whereColumn('app_visit_date', '<>', 'actual_visit_date');
            if ($request) {
                $this->applyVisitFiltersToQuery($missedQuery, $request);
            }

            $missedAppointments = $missedQuery->count();

            return $totalAppointments ? round(($missedAppointments / $totalAppointments) * 100, 1) : 0;
        });
    }

    // 3. Mortality metrics - Deaths and trends over time
    protected function getMortalityMetrics(Request $request = null)
    {
        $cacheKey = 'mortality_metrics';
        if ($request) {
            $cacheKey .= '_' . md5(serialize($request->only(['cohort_id', 'site_id', 'facility_id', 'start_date', 'end_date'])));
        }

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['mortality'], function () use ($request) {
            return [
                'total_deaths' => $this->getTotalDeaths($request),
                'death_trends' => $this->getDeathTrendsOverTime($request),
                'mortality_rate' => $this->calculateMortalityRate($request),
                'death_breakdown' => $this->getDeathBreakdown($request),
            ];
        });
    }

    protected function getTotalDeaths(Request $request = null)
    {
        $query = Patient::where('patient_status_id', 5) // Died status
            ->whereNotNull('status_date'); // Has date of death

        if ($request) {
            $this->applyPatientFilters($query, $request);
        }

        return $query->count();
    }

    protected function getDeathsLastYear(Request $request = null)
    {
        $oneYearAgo = now()->subYear();
        $query = Patient::where('patient_status_id', 5) // Died status
            ->where('status_date', '>=', $oneYearAgo)
            ->whereNotNull('status_date');

        if ($request) {
            $this->applyPatientFilters($query, $request);
        }

        return $query->count();
    }

    protected function getAverageAgeAtDeath(Request $request = null)
    {
        $query = Patient::where('patient_status_id', 5) // Died status
            ->whereNotNull('date_of_birth')
            ->whereNotNull('status_date');

        if ($request) {
            $this->applyPatientFilters($query, $request);
        }

        $avgAge = $query->selectRaw('AVG(YEAR(status_date) - YEAR(date_of_birth)) as avg_age')
            ->first()
            ->avg_age;

        return round($avgAge, 1) ?: 0;
    }

    protected function getDeathTrendsOverTime(Request $request = null)
    {
        // Determine date range based on filters or default to last 5 years
        $startDate = $request && $request->filled('start_date') 
            ? $request->input('start_date')
            : now()->subYears(5)->format('Y-01-01');
        
        $endDate = $request && $request->filled('end_date')
            ? $request->input('end_date')
            : now()->format('Y-12-31');

        // Get deaths by year for the specified date range
        $query = Patient::select([
                DB::raw('YEAR(status_date) as year'),
                DB::raw('COUNT(*) as deaths')
            ])
            ->where('patient_status_id', 5)
            ->whereNotNull('status_date')
            ->whereBetween('status_date', [$startDate, $endDate])
            ->groupBy('year')
            ->orderBy('year');

        if ($request) {
            $this->applyPatientFilters($query, $request);
        }

        $results = $query->get();

        // Format as key-value pairs - include all years in the range
        $trends = [];
        $startYear = (int) date('Y', strtotime($startDate));
        $endYear = (int) date('Y', strtotime($endDate));

        for ($year = $startYear; $year <= $endYear; $year++) {
            $deathData = $results->firstWhere('year', $year);
            $trends[(string) $year] = $deathData ? (int) $deathData->deaths : 0;
        }

        return $trends;
    }

    protected function getDeathTrendsByQuarter(Request $request = null)
    {
        // Determine date range based on filters or default to last 2 years
        $startDate = $request && $request->filled('start_date') 
            ? $request->input('start_date')
            : now()->subYears(2)->format('Y-01-01');
        
        $endDate = $request && $request->filled('end_date')
            ? $request->input('end_date')
            : now()->format('Y-12-31');

        // Get deaths by quarter for the specified date range
        $query = Patient::select([
                DB::raw('YEAR(status_date) as year'),
                DB::raw('QUARTER(status_date) as quarter'),
                DB::raw('COUNT(*) as deaths')
            ])
            ->where('patient_status_id', 5)
            ->whereNotNull('status_date')
            ->whereBetween('status_date', [$startDate, $endDate])
            ->groupBy('year', 'quarter')
            ->orderBy('year')
            ->orderBy('quarter');

        if ($request) {
            $this->applyPatientFilters($query, $request);
        }

        $results = $query->get();

        // Format as key-value pairs with quarter labels
        $trends = [];
        $currentDate = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate);
        $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate);
        
        while ($currentDate <= $endDateTime) {
            $year = (int) $currentDate->format('Y');
            $quarter = (int) ceil($currentDate->format('m') / 3);
            $label = 'Q' . $quarter . ' ' . $year;
            
            $deathData = $results->filter(function ($item) use ($year, $quarter) {
                return $item->year == $year && $item->quarter == $quarter;
            })->first();
            
            $trends[$label] = $deathData ? (int) $deathData->deaths : 0;
            
            // Move to next quarter
            $currentDate->addMonths(3);
        }

        return $trends;
    }


    protected function calculateMortalityRate(Request $request = null)
    {
        $totalPatientsQuery = Patient::query();
        if ($request) {
            $this->applyPatientFilters($totalPatientsQuery, $request);
        }
        $totalPatients = $totalPatientsQuery->count();

        $deaths = $this->getTotalDeaths($request);

        return $totalPatients > 0 ? round(($deaths / $totalPatients) * 100, 2) : 0;
    }

    protected function getDeathBreakdown(Request $request = null)
    {
        // Get deaths by age groups and gender
        $query = Patient::select([
                'patients.gender',
                DB::raw('CASE
                    WHEN TIMESTAMPDIFF(YEAR, patients.dob, patients.status_date) < 15 THEN "0-14"
                    WHEN TIMESTAMPDIFF(YEAR, patients.dob, patients.status_date) BETWEEN 15 AND 24 THEN "15-24"
                    WHEN TIMESTAMPDIFF(YEAR, patients.dob, patients.status_date) BETWEEN 25 AND 34 THEN "25-34"
                    WHEN TIMESTAMPDIFF(YEAR, patients.dob, patients.status_date) BETWEEN 35 AND 49 THEN "35-49"
                    ELSE "50+"
                END as age_group'),
                DB::raw('COUNT(*) as deaths')
            ])
            ->where('patient_status_id', 5)
            ->whereNotNull('status_date')
            ->whereNotNull('dob')
            ->groupBy('gender', 'age_group')
            ->orderBy('age_group')
            ->orderBy('gender');

        if ($request) {
            $this->applyPatientFilters($query, $request);
        }

        return $query->get()->toArray();
    }

    // 3. Viral load metrics
    protected function getViralLoadMetrics()
    {
        return Cache::remember('viral_load_metrics', self::CACHE_DURATIONS['viral_load'], function () {
            return [
                'suppression_rates' => [
                    '6' => 82.5,
                    '12' => 85.2,
                    '24' => 87.8,
                ],
                'time_to_first_test' => $this->calculateTimeToFirstViralLoad(),
                'repeat_test_rate' => 72.4,
                'longitudinal_trends' => $this->getViralLoadTrends(),
            ];
        });
    }

    protected function calculateTimeToFirstViralLoad()
    {
        return [
            'median_days' => 45,
            'average_days' => 58.2,
        ];
    }

    protected function getViralLoadTrends()
    {
        return [
            '2020' => 78.4,
            '2021' => 81.2,
            '2022' => 84.7,
            '2023' => 86.9,
        ];
    }

    // 4. Regimen metrics
    protected function getRegimenMetrics()
    {
        return Cache::remember('regimen_metrics', self::CACHE_DURATIONS['regimen'], function () {
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
        });
    }

    protected function getCurrentRegimenDistribution()
    {
        return [
            'Regimen A' => 42,
            'Regimen B' => 28,
            'Regimen C' => 18,
            'Regimen D' => 12,
        ];
    }

    // 5. TB metrics
    protected function getTbMetrics()
    {
        return Cache::remember('tb_metrics', self::CACHE_DURATIONS['tb'], function () {
            $currentYear = now()->year;
            
            $screenedThisYear = Visit::whereYear('actual_visit_date', $currentYear)
                ->whereHas('visitDetails', function ($query) {
                    $query->whereNotNull('tb_status_id');
                })
                ->count();

            $totalVisitsThisYear = Visit::whereYear('actual_visit_date', $currentYear)->count();

            return [
                'screening_coverage' => $totalVisitsThisYear ? round(($screenedThisYear / $totalVisitsThisYear) * 100, 1) : 0,
                'current_tb_treatment' => TbStatus::whereIn('id', [4, 5])->count(),
                'tb_status_distribution' => $this->getTbStatusDistribution(),
            ];
        });
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

    // 6. Maternal health metrics
    protected function getMaternalMetrics()
    {
        return Cache::remember('maternal_metrics', self::CACHE_DURATIONS['maternal'], function () {
            $pregnantPatients = VisitDetail::where('pregnant', 'yes')->distinct('visit_id')->count();

            return [
                'pregnant_patients' => $pregnantPatients,
                'retention_12_months' => $this->calculateMaternalRetention(12),
                'retention_24_months' => $this->calculateMaternalRetention(24),
                'art_initiation_during_pregnancy' => $this->getArtInitiationDuringPregnancy(),
                'postpartum_loss' => $this->calculatePostpartumLoss(),
            ];
        });
    }

    protected function calculateMaternalRetention($months)
    {
        $pregnantPatients = Visit::whereHas('visitDetails', function ($query) {
                $query->where('pregnant', 'Yes');
            })
            ->where('actual_visit_date', '<=', now()->subMonths($months))
            ->distinct()
            ->count();

        $activePregnantPatients = Visit::whereHas('visitDetails', function ($query) {
                $query->where('pregnant', 'Yes');
            })
            ->where('actual_visit_date', '<=', now()->subMonths(3))
            ->distinct()
            ->count();

        return $pregnantPatients ? round(($activePregnantPatients / $pregnantPatients) * 100, 1) : 0;
    }

    protected function getArtInitiationDuringPregnancy()
    {
        return InitialVisit::whereNotNull('art_start_date')
            ->whereHas('patient.visits.visitDetails', function ($query) {
                $query->where('pregnant', 'Yes')
                    ->whereDate('actual_visit_date', '>=', DB::raw('art_start_date'))
                    ->whereDate('actual_visit_date', '<=', DB::raw('DATE_ADD(art_start_date, INTERVAL 9 MONTH)'));
            })
            ->count();
    }

    protected function calculatePostpartumLoss()
    {
        $postpartumPatients = Visit::whereHas('visitDetails', function ($query) {
                $query->where('pregnant', 'Yes');
            })
            ->where('actual_visit_date', '<=', now()->subMonths(12))
            ->distinct()
            ->count();

        $lostPatients = Visit::whereHas('visitDetails', function ($query) {
                $query->where('pregnant', 'Yes');
            })
            ->where('actual_visit_date', '>=', now()->subMonths(12))
            ->distinct()
            ->count();

        return $postpartumPatients ? round(($lostPatients / $postpartumPatients) * 100, 1) : 0;
    }

    // Filter helper methods
    protected function applyPatientFilters($query, Request $request)
    {
        if ($request->filled('cohort_id')) {
            $query->whereHas('site', function ($q) use ($request) {
                $q->where('cohort_id', $request->cohort_id);
            });
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('facility_id')) {
            $query->whereHas('visits', function ($q) use ($request) {
                $q->where('facility_id', $request->facility_id);
            });
        }

        if ($request->filled('start_date')) {
            $query->whereHas('initialVisit', function ($q) use ($request) {
                $q->where('art_start_date', '>=', $request->start_date);
            });
        }

        if ($request->filled('end_date')) {
            $query->whereHas('initialVisit', function ($q) use ($request) {
                $q->where('art_start_date', '<=', $request->end_date);
            });
        }
    }

    protected function applyInitialVisitFilters($query, Request $request)
    {
        if ($request->filled('cohort_id')) {
            $query->whereHas('patient.site', function ($q) use ($request) {
                $q->where('cohort_id', $request->cohort_id);
            });
        }

        if ($request->filled('site_id')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('site_id', $request->site_id);
            });
        }

        if ($request->filled('facility_id')) {
            $query->whereHas('patient.visits', function ($q) use ($request) {
                $q->where('facility_id', $request->facility_id);
            });
        }

        if ($request->filled('start_date')) {
            $query->where('art_start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('art_start_date', '<=', $request->end_date);
        }
    }

    protected function applyVisitFiltersToQuery($query, Request $request)
    {
        // Use whereHas and whereIn for visit filtering to avoid duplicate joins
        if ($request->filled('cohort_id')) {
            // Get patient IDs from the cohort first
            $patientIds = Patient::whereHas('site', function ($q) use ($request) {
                $q->where('cohort_id', $request->cohort_id);
            })->pluck('id');
            
            $query->whereIn('visits.patient_id', $patientIds);
        }

        if ($request->filled('site_id')) {
            // Get patient IDs from the site
            $patientIds = Patient::where('site_id', $request->site_id)->pluck('id');
            $query->whereIn('visits.patient_id', $patientIds);
        }

        if ($request->filled('facility_id')) {
            $query->where('visits.facility_id', $request->facility_id);
        }

        if ($request->filled('start_date')) {
            $query->where('visits.actual_visit_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('visits.actual_visit_date', '<=', $request->end_date);
        }
    }

    // 7. Mortality metrics
    
    protected function getDeathCount($since)
    {
        return Patient::where('patient_status_id', 5)
            ->whereHas('visits', function ($query) use ($since) {
                $query->where('actual_visit_date', '>=', $since);
            })
            ->count();
    }

    protected function getDeathTrends()
    {
        return [
            '2019' => 56,
            '2020' => 48,
            '2021' => 42,
            '2022' => 38,
            '2023' => 32,
        ];
    }

    protected function getAgeAtDeathDistribution(Request $request = null)
    {
        $query = Patient::selectRaw('
            CASE
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 15 THEN "0-14"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 15 AND 24 THEN "15-24"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN "25-34"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN "35-44"
                ELSE "45+"
            END as age_group,
            COUNT(*) as count
        ')
            ->where('patient_status_id', 5)
            ->whereNotNull('date_of_birth');

        if ($request) {
            $this->applyPatientFilters($query, $request);
        }

        return $query->groupBy('age_group')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->age_group => $item->count];
            });
    }

    protected function getSurvivalAnalysis()
    {
        return [
            '1_year' => 92.4,
            '5_year' => 78.6,
            '10_year' => 65.3,
        ];
    }

    // 8. Patient demographics
    protected function getDemographics()
    {
        return Cache::remember('demographics', self::CACHE_DURATIONS['demographics'], function () {
            $genderDistribution = Patient::select('gender', DB::raw('count(*) as count'))
                ->groupBy('gender')
                ->get();

            return [
                'gender_distribution' => $genderDistribution,
                'age_distribution' => $this->getAgeDistribution(),
                'art_initiation_age' => $this->getArtInitiationAge(),
                'current_age' => $this->getCurrentAgeDistribution(),
            ];
        });
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
        return $this->getAgeDistribution(); // Same as current age distribution
    }

    // 9. Cohort performance
    protected function getCohortMetrics()
    {
        return Cache::remember('cohort_metrics', self::CACHE_DURATIONS['cohorts'], function () {
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
        });
    }

    protected function getCohortRetention(Cohort $cohort)
    {
        return [
            '6' => rand(70, 90),
            '12' => rand(65, 85),
            '24' => rand(60, 80),
            '60' => rand(55, 75),
        ];
    }

    protected function getCohortViralSuppression(Cohort $cohort)
    {
        return [
            '6' => rand(75, 90),
            '12' => rand(80, 92),
            '24' => rand(82, 95),
        ];
    }

    protected function getCohortLtfuRate(Cohort $cohort)
    {
        return rand(5, 20);
    }

    /**
     * Clear all cached data (call this when data changes)
     */
    public static function clearCache()
    {
        Cache::forget('grouped_analysis_full');
        Cache::forget('summary_statistics');
        Cache::forget('retention_rates');
        Cache::forget('ltfu_count');
        Cache::forget('reengaged_count');
        Cache::forget('missed_visit_rate');
        Cache::forget('avg_art_duration');
        Cache::forget('viral_load_metrics');
        Cache::forget('regimen_metrics');
        Cache::forget('tb_metrics');
        Cache::forget('maternal_metrics');
        Cache::forget('mortality_metrics');
        Cache::forget('demographics');
        Cache::forget('cohort_metrics');
    }
}