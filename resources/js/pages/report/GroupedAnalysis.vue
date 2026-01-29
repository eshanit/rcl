<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import RetentionChart from '@/components/charts/RetentionChart.vue';
import DeathsTrendsChart from '@/components/charts/DeathsTrendsChart.vue';
import DeathsQuarterlyLineChart from '@/components/charts/DeathsQuarterlyLineChart.vue';
import DeathsAgeDistributionChart from '@/components/charts/DeathsAgeDistributionChart.vue';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { ICohort, IFacility, ISite } from '@/types/reports';

const props = defineProps<{
  summary?: any;
  retention?: any;
  deaths?: any;
  cohorts?: ICohort[];
  sites?: ISite[];
  facilities?: IFacility[];
}>();
// Destructure props for easier access in template
const { cohorts, sites, facilities, summary, retention, deaths } = props;
// Form state for filtering
const form = useForm({
  cohort_id: '',
  site_id: '',
  facility_id: '',
  start_date: '',
  end_date: ''
});

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Analytics', href: '/reports' },
  { title: 'Grouped Analysis', href: '' }
];

// Component state
const loading = ref(false);
const activeTab = ref<'retention' | 'deaths'>('retention');

// Store filter selections to prevent loss during re-renders
const lastAppliedFilters = ref({
  cohort_id: '',
  site_id: '',
  facility_id: '',
  start_date: '',
  end_date: ''
});

// Prepare retention chart data
const retentionChartData = computed(() => {
  if (!retention?.rates) return {};

  const labels = Object.keys(retention.rates).map(months => `${months} Months`);
  const values = Object.values(retention.rates).map((rate: any) => rate.percentage);

  return {
    labels,
    datasets: [{
      label: 'Retention Rate (%)',
      data: values,
      borderColor: 'rgb(75, 192, 192)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      tension: 0.1
    }]
  };
});

// Generate data context string for display
const dataContext = computed(() => {
  const parts = [];

  if (form.facility_id) {
    const facilityName = getFacilityName(form.facility_id);
    const siteName = getSiteName(form.site_id);
    const cohortName = getCohortName(form.cohort_id);
    parts.push(`${facilityName} (${siteName}, ${cohortName})`);
  } else if (form.site_id) {
    const siteName = getSiteName(form.site_id);
    const cohortName = getCohortName(form.cohort_id);
    parts.push(`${siteName} (${cohortName})`);
  } else if (form.cohort_id) {
    const cohortName = getCohortName(form.cohort_id);
    parts.push(cohortName);
  } else {
    parts.push('All Data');
  }

  if (form.start_date || form.end_date) {
    const dateRange = [];
    if (form.start_date) dateRange.push(`From: ${form.start_date}`);
    if (form.end_date) dateRange.push(`To: ${form.end_date}`);
    parts.push(dateRange.join(' '));
  }

  return parts.join(' • ');
});

// Computed properties for cascading filters
// Computed properties for cascading filters
const filteredSites = computed(() => {
  if (!sites) return [];
  if (form.cohort_id) {
    return sites.filter(site => site.cohort_id == Number(form.cohort_id));
  }
  return sites;
});

const filteredFacilities = computed(() => {
  if (!facilities || !sites) return [];
  const siteIdNum = form.site_id ? Number(form.site_id) : null;
  const cohortIdNum = form.cohort_id ? Number(form.cohort_id) : null;

  let facilitiesList = facilities;
  if (siteIdNum) {
    facilitiesList = facilitiesList.filter(facility => Number(facility.site_id) === siteIdNum);
  } else if (cohortIdNum) {
    const cohortSiteIds = sites
      .filter(site => Number(site.cohort_id) === cohortIdNum)
      .map(site => site.id);
    facilitiesList = facilitiesList.filter(facility => cohortSiteIds.includes(Number(facility.site_id)));
  }
  return facilitiesList;
});

// Watchers for auto-selection logic
watch(() => form.site_id, (newSiteId) => {
  if (newSiteId && sites) {
    const selectedSite = sites.find(site => site.id == Number(newSiteId));
    if (selectedSite && form.cohort_id != selectedSite.cohort_id.toString()) {
      form.cohort_id = selectedSite.cohort_id.toString();
    }
  }
});

watch(() => form.facility_id, (newFacilityId) => {
  if (newFacilityId && facilities && sites) {
    const selectedFacility = facilities.find(facility => facility.id == Number(newFacilityId));
    if (selectedFacility && form.site_id != selectedFacility.site_id.toString()) {
      form.site_id = selectedFacility.site_id.toString();
      const selectedSite = sites.find(site => site.id == selectedFacility.site_id);
      if (selectedSite && form.cohort_id != selectedSite.cohort_id.toString()) {
        form.cohort_id = selectedSite.cohort_id.toString();
      }
    }
  }
});

// Fetch filtered data
const fetchFilteredData = async () => {
  // Save current filter values before the request
  lastAppliedFilters.value = {
    cohort_id: form.cohort_id,
    site_id: form.site_id,
    facility_id: form.facility_id,
    start_date: form.start_date,
    end_date: form.end_date
  };

  console.log('Fetching filtered data with filters:', lastAppliedFilters.value);

  loading.value = true;
  await form.get(route('reports.grouped'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Restore the filter values after successful response
      form.cohort_id = lastAppliedFilters.value.cohort_id;
      form.site_id = lastAppliedFilters.value.site_id;
      form.facility_id = lastAppliedFilters.value.facility_id;
      form.start_date = lastAppliedFilters.value.start_date;
      form.end_date = lastAppliedFilters.value.end_date;
      console.log('Filters restored after data load');
    },
    onFinish: () => {
      loading.value = false;
    }
  });
};

// Reset filters
const resetFilters = () => {
  form.cohort_id = '';
  form.site_id = '';
  form.facility_id = '';
  form.start_date = '';
  form.end_date = '';
  fetchFilteredData();
};

// Load initial data on component mount
onMounted(() => {
  // If no retention data is present, fetch the default/unfiltered data
  if (!props.retention) {
    fetchFilteredData();
  }
});

// Helper functions to get names from IDs
const getCohortName = (cohortId: string | number): string => {
  if (!cohorts) return 'Unknown Cohort';
  const cohort = cohorts.find(c => c.id == Number(cohortId));
  return cohort?.name || 'Unknown Cohort';
};

const getSiteName = (siteId: string | number): string => {
  if (!sites) return 'Unknown Site';
  const site = sites.find(s => s.id == Number(siteId));
  return site?.name || 'Unknown Site';
};

const getFacilityName = (facilityId: string | number): string => {
  if (!facilities) return 'Unknown Facility';
  const facility = facilities.find(f => f.id == Number(facilityId));
  return facility?.name || 'Unknown Facility';
};
</script>

<template>
  <AppLayout title="ART Program Dashboard" :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Grouped Analysis Dashboard</h1>
        <p class="text-gray-600">Comprehensive analysis of ART program performance metrics</p>
      </div>

      <!-- Active Filters Indicator -->
      <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg shadow-sm">
        <div class="flex items-center justify-between mb-3">
          <div class="text-sm font-bold text-blue-900">📊 Currently Viewing Data:</div>
          <div v-if="loading" class="text-xs text-blue-700 animate-pulse">Loading...</div>
        </div>
        <div class="flex flex-wrap gap-2">
          <div class="px-3 py-2 bg-white border border-blue-200 text-blue-900 text-sm font-medium rounded-lg shadow-xs">
            <span class="text-blue-700">Cohort:</span>
            <span class="ml-1 font-semibold">{{ form.cohort_id ? getCohortName(form.cohort_id) : 'All Cohorts' }}</span>
          </div>
          <div class="px-3 py-2 bg-white border border-blue-200 text-blue-900 text-sm font-medium rounded-lg shadow-xs">
            <span class="text-blue-700">Site:</span>
            <span class="ml-1 font-semibold">{{ form.site_id ? getSiteName(form.site_id) : 'All Sites' }}</span>
          </div>
          <div class="px-3 py-2 bg-white border border-blue-200 text-blue-900 text-sm font-medium rounded-lg shadow-xs">
            <span class="text-blue-700">Facility:</span>
            <span class="ml-1 font-semibold">{{ form.facility_id ? getFacilityName(form.facility_id) : 'All Facilities'
              }}</span>
          </div>
          <div v-if="form.start_date"
            class="px-3 py-2 bg-white border border-green-200 text-green-900 text-sm font-medium rounded-lg shadow-xs">
            <span class="text-green-700">From:</span>
            <span class="ml-1 font-semibold">{{ form.start_date }}</span>
          </div>
          <div v-if="form.end_date"
            class="px-3 py-2 bg-white border border-green-200 text-green-900 text-sm font-medium rounded-lg shadow-xs">
            <span class="text-green-700">To:</span>
            <span class="ml-1 font-semibold">{{ form.end_date }}</span>
          </div>
        </div>
      </div>

      <!-- Summary Statistics (Quick Load) -->
      <div v-if="summary" class="mb-8">
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-gray-800 mb-2">Summary Statistics</h2>
          <p class="text-sm text-gray-600">📊 {{ dataContext }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="bg-white p-4 rounded-xl shadow-sm border">
            <div class="text-sm text-gray-600 mb-1">Total Patients</div>
            <div class="text-2xl font-bold text-gray-800">{{ summary.total_patients }}</div>
          </div>
          <div class="bg-white p-4 rounded-xl shadow-sm border">
            <div class="text-sm text-gray-600 mb-1">Active Patients</div>
            <div class="text-2xl font-bold text-green-600">{{ summary.active_patients }}</div>
          </div>
          <div class="bg-white p-4 rounded-xl shadow-sm border">
            <div class="text-sm text-gray-600 mb-1">Active Percentage</div>
            <div class="text-2xl font-bold text-blue-600">{{ summary.active_percentage }}%</div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm mb-6">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-800">Filter Analysis</h2>
              <p class="text-sm text-gray-600 mt-1">Select criteria to filter the retention analysis</p>
            </div>
            <Button @click="resetFilters" variant="outline" size="sm">
              Reset Filters
            </Button>
          </div>

          <form @submit.prevent="fetchFilteredData" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Cohort -->
            <div>
              <Label class="text-sm font-medium text-gray-700 mb-2">Cohort</Label>
              <Select v-model="form.cohort_id">
                <SelectTrigger>
                  <SelectValue placeholder="All Cohorts" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id.toString()">
                    {{ cohort.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Site -->
            <div>
              <Label class="text-sm font-medium text-gray-700 mb-2">Site</Label>
              <Select v-model="form.site_id">
                <SelectTrigger>
                  <SelectValue placeholder="All Sites" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="site in filteredSites" :key="site.id" :value="site.id.toString()">
                    {{ site.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Facility -->
            <div>
              <Label class="text-sm font-medium text-gray-700 mb-2">Facility</Label>
              <Select v-model="form.facility_id">
                <SelectTrigger>
                  <SelectValue placeholder="All Facilities" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="facility in filteredFacilities" :key="facility.id" :value="facility.id.toString()">
                    {{ facility.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Start Date -->
            <div>
              <Label class="text-sm font-medium text-gray-700 mb-2">Start Date</Label>
              <Input type="date" v-model="form.start_date" />
            </div>

            <!-- End Date -->
            <div>
              <Label class="text-sm font-medium text-gray-700 mb-2">End Date</Label>
              <Input type="date" v-model="form.end_date" />
            </div>

            <div class="flex items-end">
              <Button type="submit" :disabled="form.processing" class="w-full">
                <span v-if="form.processing">Applying...</span>
                <span v-else>Apply Filters</span>
              </Button>
            </div>
          </form>
        </div>
      </Card>

      <!-- Analysis Tabs -->
      <div class="mb-6 border-b border-gray-200">
        <div class="flex gap-0">
          <button
            @click="activeTab = 'retention'"
            :class="[
              'px-6 py-3 font-medium text-sm border-b-2 transition-colors',
              activeTab === 'retention'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-gray-600 hover:text-gray-900'
            ]">
            📊 Retention Analysis
          </button>
          <button
            @click="activeTab = 'deaths'"
            :class="[
              'px-6 py-3 font-medium text-sm border-b-2 transition-colors',
              activeTab === 'deaths'
                ? 'border-red-600 text-red-600'
                : 'border-transparent text-gray-600 hover:text-gray-900'
            ]">
            💔 Deaths Analysis
          </button>
        </div>
      </div>

      <!-- Retention Rates Analysis -->
      <div v-if="activeTab === 'retention'" class="mb-8">
        <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm">
          <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
              <div
                class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800">Patient Retention Rates</h2>
                <p class="text-sm text-gray-600 mb-1">Retention rates at 6, 12, 24, 60, 120, 180, 240, and 300 months</p>
                <p class="text-sm font-medium text-blue-700">📊 {{ dataContext }}</p>
              </div>
            </div>

            <!-- Loading state -->
            <div v-if="!retention" class="flex justify-center items-center h-64">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            </div>

            <!-- Retention Chart -->
            <div v-else class="mb-6">
              <div class="h-96">
                <RetentionChart :data="retentionChartData" />
              </div>
            </div>

            <!-- Additional sections would load lazily... -->
            <!-- Retention Rates Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Time Period
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Retained Patients
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Total Eligible
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Retention Rate (%)
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(rate, months) in retention?.rates || {}" :key="months"
                    class="hover:bg-gray-50/50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ months }} Months
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                      {{ rate.count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                      {{ rate.total }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                      {{ rate.percentage }}%
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        rate.percentage >= 80 ? 'bg-green-100 text-green-800' :
                          rate.percentage >= 60 ? 'bg-yellow-100 text-yellow-800' :
                            'bg-red-100 text-red-800'
                      ]">
                        {{ rate.percentage >= 80 ? 'Excellent' : rate.percentage >= 60 ? 'Good' : 'Needs Improvement' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Additional Retention Metrics -->
            <div v-if="retention" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-blue-50 p-4 rounded-xl">
                <div class="text-sm text-blue-600 font-medium mb-1">LTFU Count</div>
                <div class="text-2xl font-bold text-blue-700">{{ retention.ltfu_count || 0 }}</div>
              </div>
              <div class="bg-green-50 p-4 rounded-xl">
                <div class="text-sm text-green-600 font-medium mb-1">Re-engaged Patients</div>
                <div class="text-2xl font-bold text-green-700">{{ retention.reengaged_count || 0 }}</div>
              </div>
              <div class="bg-purple-50 p-4 rounded-xl">
                <div class="text-sm text-purple-600 font-medium mb-1">Missed Visit Rate</div>
                <div class="text-2xl font-bold text-purple-700">{{ retention.missed_visit_rate || 0 }}%</div>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Deaths Analysis -->
      <div v-if="activeTab === 'deaths'" class="mb-8">
        <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm">
          <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
              <div
                class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-md">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800">Patient Deaths Analysis</h2>
                <p class="text-sm text-gray-600 mb-1">Deaths trends over time and demographic analysis</p>
                <p class="text-sm font-medium text-red-700">📊 {{ dataContext }}</p>
              </div>
            </div>

            <!-- Loading state -->
            <div v-if="!deaths" class="flex justify-center items-center h-64">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-500"></div>
            </div>

            <!-- Deaths Summary Cards -->
            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-red-50 p-4 rounded-xl border border-red-200">
                <div class="text-sm text-red-600 font-medium mb-1">Total Deaths</div>
                <div class="text-3xl font-bold text-red-700">{{ deaths.total_deaths || 0 }}</div>
                <p class="text-xs text-red-600 mt-1">Last year: {{ deaths.deaths_last_year || 0 }}</p>
              </div>
              <div class="bg-orange-50 p-4 rounded-xl border border-orange-200">
                <div class="text-sm text-orange-600 font-medium mb-1">Average Age at Death</div>
                <div class="text-3xl font-bold text-orange-700">{{ deaths.avg_age_at_death || 0 }}</div>
                <p class="text-xs text-orange-600 mt-1">years</p>
              </div>
              <div class="bg-amber-50 p-4 rounded-xl border border-amber-200">
                <div class="text-sm text-amber-600 font-medium mb-1">Death Rate</div>
                <div class="text-3xl font-bold text-amber-700">{{ deaths.death_rate || 0 }}%</div>
                <p class="text-xs text-amber-600 mt-1">of total patients</p>
              </div>
            </div>

            <!-- Deaths Trends Chart (Annual) -->
            <div v-if="deaths?.trends" class="mt-8 bg-gray-50 p-6 rounded-xl border border-gray-200">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Annual Deaths Trend</h3>
              <div class="h-64">
                <DeathsTrendsChart :trends="deaths.trends" />
              </div>
            </div>

            <!-- Deaths Quarterly Chart -->
            <div v-if="deaths?.quarterly_trends" class="mt-6 bg-gray-50 p-6 rounded-xl border border-gray-200">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Quarterly Deaths Trend</h3>
              <div class="h-64">
                <DeathsQuarterlyLineChart :trends="deaths.quarterly_trends" />
              </div>
            </div>

            <!-- Age Distribution Chart -->
            <div v-if="deaths?.age_distribution" class="mt-6 bg-gray-50 p-6 rounded-xl border border-gray-200">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Age Distribution at Death</h3>
              <div class="h-64">
                <DeathsAgeDistributionChart :distribution="deaths.age_distribution" />
              </div>
            </div>

            <!-- Deaths Trends Table -->
            <div v-if="deaths?.trends" class="mt-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Deaths Trends Over Time</h3>
              <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Period
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Deaths Count
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Trend
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(count, period) in deaths.trends || {}" :key="period"
                      class="hover:bg-gray-50/50 transition-colors duration-150">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ period }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        {{ count }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="count > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
                          {{ count > 0 ? '↑ ' + count : 'No deaths' }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Age at Death Distribution -->
            <div v-if="deaths?.age_distribution" class="mt-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Age at Death Distribution</h3>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div v-for="(count, ageRange) in deaths.age_distribution || {}" :key="ageRange"
                  class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <div class="text-xs text-gray-600 font-medium">{{ ageRange }}</div>
                  <div class="text-lg font-bold text-gray-800">{{ count }}</div>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
<style scoped>
/* Custom color definitions for the dashboard */
:root {
  --color-art: #4C51BF;
  --color-viral: #9F7AEA;
  --color-tb: #38A169;
  --color-maternal: #ED64A6;
  --color-retention: #0EA5E9;
}

.bg-art {
  background-color: var(--color-art);
}

.text-art {
  color: var(--color-art);
}

.bg-viral {
  background-color: var(--color-viral);
}

.text-viral {
  color: var(--color-viral);
}

.bg-tb {
  background-color: var(--color-tb);
}

.text-tb {
  color: var(--color-tb);
}

.bg-maternal {
  background-color: var(--color-maternal);
}

.text-maternal {
  color: var(--color-maternal);
}

.bg-retention {
  background-color: var(--color-retention);
}

.text-retention {
  color: var(--color-retention);
}
</style>