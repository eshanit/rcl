<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ICohort, IFacility, IIndicator, IReportData, ISite } from '@/types/reports';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const props = defineProps<{
  cohorts: ICohort[];
  sites: ISite[];
  facilities: IFacility[];
  initialReport?: IReportData | null;
}>();

// Form state using Inertia's useForm
const form = useForm({
  indicator: 'TotalPatientsEverEnrolled',
  cohort_id: '',
  site_id: '',
  facility_id: '',
  start_date: '',
  end_date: ''
});

//
const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports Index', href: '/reports' },
  { title: 'Indicator Analysis', href: '' }
];
// Report data
const reportData = ref<IReportData | null | any>(props.initialReport || null);

// Indicator options
const indicators: IIndicator[] = [
  { label: 'Total Patients Ever Enrolled', value: 'TotalPatientsEverEnrolled' },
  { label: 'Patients With Unknown Age Group', value: 'PatientsWithUnknownAgeGroup' },
  { label: 'Newly Initiated on ART', value: 'NewlyInitiatedOnART' },
  { label: 'Proportion of Children on ART', value: 'ProportionOfChildrenOnART' },
  { label: 'Patients Retained After 12 Months (%)', value: 'PatientsRetainedAfter12Months' },
  { label: 'Patients Retained After 24 Months (%)', value: 'PatientsRetainedAfter24Months' },
  { label: 'Patients With Suppressed Viral Load', value: 'PatientsWithSuppressedViralLoad' },
  { label: 'Time to First Viral Load', value: 'TimeToFirstViralLoad' },
  { label: 'Patients Screened For TB', value: 'PatientsScreenedForTB' },
  { label: 'Patients on TB Treatment', value: 'PatientsOnTBTreatment' },
  { label: 'Missed Appointment Visits (90+ days)', value: 'MissedAppointmentVisits' },
  { label: 'Missed Appointments (%)', value: 'MissedAppointments' },
  { label: 'Deaths among ART patients', value: 'DeathsAmongART' },
  { label: 'Patients Transferred Out', value: 'TransferredOut' },
  { label: 'Pregnant Women Retained After 12 Months', value: 'PregnantWomenRetainedAfter12Months' },
  { label: 'Pregnant Women Retained After 24 Months', value: 'PregnantWomenRetainedAfter24Months' },
  { label: 'Pregnant Women LTFU After 12 Months', value: 'PregnantWomenLTFUAfter12Months' },
  { label: 'Pregnant Women Died Within 12 Months', value: 'PregnantWomenDiedWithin12Months' },
  { label: 'Regimen Switches (Side Effects/Treatment Failure)', value: 'RegimenSwitches' },
];

const formatDate = (dateString: string | null) => {
  if (!dateString) return null;
  const date = new Date(dateString);
  return date.toLocaleDateString();
};

// Fetch report from backend
const fetchReport = () => {
  form.get(route('reports.indicators', { indicator: form.indicator }), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      reportData.value = page.props.report;
    }
  });
};

// Computed properties for UI logic
const hasBreakdown = () => reportData.value && 'breakdown' in reportData.value;
const hasPercentageMetrics = () => reportData.value && 'percentage' in reportData.value;
const isMissedAppointmentsReport = () => reportData.value?.indicator.includes('Missed Appointment');
const isPregnantWomenReport = () => reportData.value?.indicator.includes('Pregnant women');
const isPregnantWomenLTFUReport = () => reportData.value?.indicator.includes('Pregnant women LTFU');
const isPregnantWomenDiedReport = () => reportData.value?.indicator.includes('Pregnant women Died');

// Computed properties for cascading filters
const filteredSites = computed(() => {
  if (form.cohort_id) {
    return props.sites.filter(site => site.cohort_id == Number(form.cohort_id));
  }
  return props.sites;
});

const filteredFacilities = computed(() => {
  // Defensive: ensure all IDs are numbers for comparison
  const siteIdNum = form.site_id ? Number(form.site_id) : null;
  const cohortIdNum = form.cohort_id ? Number(form.cohort_id) : null;

  let facilities = props.facilities;
  if (siteIdNum) {
    facilities = facilities.filter(facility => Number(facility.site_id) === siteIdNum);
  } else if (cohortIdNum) {
    // If only cohort is selected, show facilities from all sites in that cohort
    const cohortSiteIds = props.sites
      .filter(site => Number(site.cohort_id) === cohortIdNum)
      .map(site => site.id);
    facilities = facilities.filter(facility => cohortSiteIds.includes(Number(facility.site_id)));
  }
  // Debug output
  console.log('All facilities:', props.facilities, 'Filtered:', facilities, 'site_id:', form.site_id, 'cohort_id:', form.cohort_id);
  return facilities;
});

// Watchers for auto-selection logic
watch(() => form.site_id, (newSiteId) => {
  if (newSiteId) {
    // Auto-select the cohort when a site is selected
    const selectedSite = props.sites.find(site => site.id == Number(newSiteId));
    if (selectedSite && form.cohort_id != selectedSite.cohort_id.toString()) {
      form.cohort_id = selectedSite.cohort_id.toString();
    }
  }
});

watch(() => form.facility_id, (newFacilityId) => {
  if (newFacilityId) {
    // Auto-select the site when a facility is selected
    const selectedFacility = props.facilities.find(facility => facility.id == Number(newFacilityId));
    if (selectedFacility && form.site_id != selectedFacility.site_id.toString()) {
      form.site_id = selectedFacility.site_id.toString();

      // Also auto-select the cohort
      const selectedSite = props.sites.find(site => site.id == selectedFacility.site_id);
      if (selectedSite && form.cohort_id != selectedSite.cohort_id.toString()) {
        form.cohort_id = selectedSite.cohort_id.toString();
      }
    }
  }
});

///for time to first viral load test
// Add these computed properties and methods
const genderAverage = (gender: string) => {
  if (!reportData.value?.breakdown) return 0;

  let total = 0;
  let count = 0;

  for (const ageGroup in reportData.value.breakdown) {
    const value = reportData.value.breakdown[ageGroup][gender];
    if (value > 0) {
      total += value;
      count++;
    }
  }

  return count ? (total / count).toFixed(1) : 0;
};

const maxGenderAverage = computed(() => {
  const male = parseFloat(genderAverage('male')) || 0;
  const female = parseFloat(genderAverage('female')) || 0;
  return Math.max(male, female, 100); // Ensure at least 100 for scaling
});

const getDaysClass = (days: number) => {
  if (days > 180) return 'text-red-600 font-semibold';
  if (days > 90) return 'text-orange-500';
  if (days > 60) return 'text-yellow-500';
  return 'text-green-600';
};

// Add computed property
const tableType = computed(() => {
  if (!reportData.value || !hasBreakdown()) return null;

  if (reportData.value.indicator === 'Total patients ever enrolled on ART') {
    return 'proportions';
  }

  if (reportData.value.indicator === 'Time to first Viral Load test') {
    return 'vl-time'; // Handle elsewhere
  }

  if (reportData.value.indicator === 'Proportion of children on ART') {
    return 'art-children';
  }

  if (reportData.value.indicator === 'Pregnant women retained after 12 months' || reportData.value.indicator === 'Pregnant women retained after 24 months') {
    return 'pregnant-women';
  }

  if (reportData.value.indicator === 'Pregnant women LTFU after 12 months') {
    return 'pregnant-women-ltfu';
  }

  if (reportData.value.indicator === 'Pregnant women died within 12 months') {
    return 'pregnant-women-died';
  }

  return 'standard';
});

const isChildAgeGroup = (ageGroup: string) => {
  const childGroups = [
    '≤2 months', '3-12 months', '13-24 months', '25-59 months',
    '5-9 years', '10-14 years', '15-18 years'
  ];
  return childGroups.includes(ageGroup);
};

// Add to computed properties
// Add to methods
const getProportionClass = (proportion: number) => {
  if (proportion > 75) return 'text-green-600 font-semibold';
  if (proportion > 50) return 'text-yellow-500';
  return 'text-red-600';
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Indicator Analysis Report
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <!-- Filter Form -->
          <form @submit.prevent="fetchReport" class="mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
              <Label class="block text-sm font-medium text-gray-700 mb-1">Indicator</Label>
              <Select v-model="form.indicator"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Select Indicator" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="ind in indicators" :key="ind.value" :value="ind.value">
                    {{ ind.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label class="block text-sm font-medium text-gray-700 mb-1">Cohort</Label>
              <Select v-model="form.cohort_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Select Cohort" />
                </SelectTrigger>
                <SelectContent>
                  <!-- <SelectItem value="">All Cohorts</SelectItem> -->
                  <SelectItem v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id">
                    {{ cohort.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label class="block text-sm font-medium text-gray-700 mb-1">Site</Label>
              <Select v-model="form.site_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Select Site" />
                </SelectTrigger>
                <!-- <option value="">All Sites</option> -->
                <SelectContent>
                  <SelectItem v-for="site in filteredSites" :key="site.id" :value="site.id">
                    {{ site.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label class="block text-sm font-medium text-gray-700 mb-1">Facility</Label>
              <Select v-model="form.facility_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Select Facility" />
                </SelectTrigger>
                <!-- <option value="">All Facilities</option> -->
                <SelectContent>
                  <SelectItem v-for="facility in filteredFacilities" :key="facility.id" :value="facility.id">
                    {{ facility.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label class="block text-sm font-medium text-gray-700 mb-1">Start Date</Label>
              <Input type="date" v-model="form.start_date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
            </div>

            <div>
              <Label class="block text-sm font-medium text-gray-700">End Date</Label>
              <Input type="date" v-model="form.end_date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
            </div>

            <div class="flex items-end">
              <Button type="submit" :disabled="form.processing"
                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                <span v-if="!form.processing">Generate Report</span>
                <span v-else>Generating...</span>
              </Button>
            </div>
          </form>

          <!-- Loading State -->
          <div v-if="form.processing" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Generating report...</p>
          </div>

          <!-- Results Section -->
          <div v-else-if="reportData" class="overflow-x-auto">
            <h3 class="text-lg font-medium mb-4">{{ reportData.indicator }}</h3>

            <div v-if="reportData.indicator === 'Time to first Viral Load test'" class="mb-8">
              <!-- Summary Cards -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                  <p class="text-sm text-blue-800">Average Time to First VL</p>
                  <p class="text-3xl font-bold text-blue-600">
                    {{ reportData.average_days }} days
                  </p>
                </div>

                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                  <p class="text-sm text-green-800">Patients with VL Test</p>
                  <p class="text-3xl font-bold text-green-600">
                    {{ reportData.total_patients }}
                  </p>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                  <p class="text-sm text-purple-800">Reporting Period</p>
                  <p class="text-lg font-medium text-purple-600">
                    {{ formatDate(form.start_date) || 'Start' }}
                    to
                    {{ formatDate(form.end_date) || 'End' }}
                  </p>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg">
                  <p class="text-sm text-purple-800">Median Time</p>
                  <p class="text-2xl font-bold">{{ reportData.median_days }} days</p>
                </div>

                <div class="bg-green-50 p-4 rounded-lg">
                  <p class="text-sm text-green-800">Patients in Target</p>
                  <p class="text-2xl font-bold">
                    {{ reportData.patients_in_target }} / {{ reportData.total_patients }}
                  </p>
                </div>

                <div class="bg-amber-50 p-4 rounded-lg">
                  <p class="text-sm text-amber-800">Target Compliance</p>
                  <p class="text-2xl font-bold">
                    {{ reportData.total_patients ?
                      Math.round((reportData.patients_in_target / reportData.total_patients) * 100) : 0 }}%
                  </p>
                </div>
              </div>

              <!-- Info Box -->
              <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6">
                <p class="text-sm text-yellow-800 flex items-start">
                  <svg class="h-5 w-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  This metric shows the average time between ART initiation and first viral load test.
                  Lower values indicate patients are receiving timely viral load monitoring.
                </p>
              </div>

              <!-- Breakdown Table -->
              <h4 class="text-md font-medium mb-3">Average Days by Age Group and Gender</h4>
              <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Age
                      Group</th>
                    <th
                      class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Male</th>
                    <th
                      class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Female</th>
                    <th
                      class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Other</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">{{ ageGroup }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center" :class="getDaysClass(data.male)">
                      {{ data.male }} <span class="text-xs text-gray-500">days</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center" :class="getDaysClass(data.female)">
                      {{ data.female }} <span class="text-xs text-gray-500">days</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center" :class="getDaysClass(data.other)">
                      {{ data.other }} <span class="text-xs text-gray-500">days</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-semibold"
                      :class="getDaysClass(data.total)">
                      {{ data.total }} <span class="text-xs text-gray-500">days</span>
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                  <tr>
                    <td class="px-4 py-3 text-sm border-r">Overall Average</td>
                    <td colspan="4" class="px-4 py-3 text-sm text-center text-blue-600 font-bold">
                      {{ reportData.average_days }} days
                    </td>
                  </tr>
                </tfoot>
              </table>

              <!-- Visualization -->
              <div class="mt-8">
                <h4 class="text-md font-medium mb-3">Time Distribution by Age Group</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Bar Chart -->
                  <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <div v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup" class="mb-3">
                      <div class="flex items-center">
                        <span class="w-32 text-sm text-gray-600 truncate">{{ ageGroup }}</span>
                        <div class="flex-1 ml-2">
                          <div class="h-6 bg-blue-100 rounded overflow-hidden">
                            <div class="h-full bg-blue-500 rounded"
                              :style="{ width: Math.min(100, (data.total / reportData.average_days) * 50) + '%' }">
                            </div>
                          </div>
                        </div>
                        <span class="w-16 text-right text-sm font-medium ml-2">
                          {{ data.total }}d
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Gender Comparison -->
                  <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <div class="flex mb-4">
                      <div class="w-1/2 text-center">
                        <div class="text-lg font-bold text-blue-600">{{ genderAverage('male') }} days</div>
                        <div class="text-sm text-gray-600">Male Average</div>
                      </div>
                      <div class="w-1/2 text-center">
                        <div class="text-lg font-bold text-pink-600">{{ genderAverage('female') }} days</div>
                        <div class="text-sm text-gray-600">Female Average</div>
                      </div>
                    </div>
                    <div class="flex items-end justify-center h-32 mt-4">
                      <div class="mx-2 flex flex-col items-center">
                        <div class="w-10 bg-blue-500"
                          :style="{ height: (genderAverage('male') / maxGenderAverage * 100) + '%' }"></div>
                        <div class="mt-2 text-sm">Male</div>
                      </div>
                      <div class="mx-2 flex flex-col items-center">
                        <div class="w-10 bg-pink-500"
                          :style="{ height: (genderAverage('female') / maxGenderAverage * 100) + '%' }"></div>
                        <div class="mt-2 text-sm">Female</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Special display for Deaths report -->
            <div v-if="reportData.indicator === 'Deaths among ART patients'" class="mb-8">
              <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                <div class="flex items-center mb-4">
                  <svg class="h-8 w-8 text-red-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <h4 class="text-xl font-semibold text-red-800">Mortality Report</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="bg-white p-4 rounded-lg shadow">
                    <p class="text-sm text-gray-600">Total Deaths</p>
                    <p class="text-3xl font-bold text-red-700">{{ reportData.total }}</p>
                  </div>

                  <div class="bg-white p-4 rounded-lg shadow">
                    <p class="text-sm text-gray-600">Reporting Period</p>
                    <p class="text-lg font-medium">
                      {{ formatDate(form.start_date) || 'Start' }}
                      to
                      {{ formatDate(form.end_date) || 'End' }}
                    </p>
                  </div>

                  <div class="bg-white p-4 rounded-lg shadow">
                    <p class="text-sm text-gray-600">Coverage</p>
                    <p class="text-lg font-medium">
                      {{ reportData.coverage || 'All facilities' }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="mt-6 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <p class="text-sm text-yellow-800 flex items-start">
                  <svg class="h-5 w-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Note: This report shows patients who were active in ART care and whose status was recorded as
                  deceased.
                </p>
              </div>
            </div>
            <!-- {{ reportData.indicator }} -->
            <!-- Table for indicators with breakdown -->
            <table v-if="tableType === 'standard'" class=" min-w-full
              divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age Group
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Male</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Female</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Other</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ageGroup }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ data.male }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ data.female }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ data.other }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ data.total }}</td>
                </tr>
              </tbody>
              <tfoot v-if="reportData.total !== undefined" class="bg-gray-50 font-semibold">
                <tr>
                  <td class="px-6 py-4 text-sm">Grand Total</td>
                  <td colspan="4" class="px-6 py-4 text-sm text-right">{{ reportData.total }}</td>
                </tr>
              </tfoot>
            </table>

            <table v-else-if="tableType === 'proportions'" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age Group
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Male</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Female</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Other</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ageGroup }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ data.male }} <span class="text-xs text-gray-400">({{ data.male_proportion }}%)</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ data.female }} <span class="text-xs text-gray-400">({{ data.female_proportion }}%)</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ data.other }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ data.total }}</td>
                </tr>
              </tbody>
              <tfoot v-if="reportData.total !== undefined" class="bg-gray-50 font-semibold">
                <tr>
                  <td class="px-6 py-4 text-sm">Grand Total</td>
                  <td class="px-6 py-4 text-sm">
                    {{ reportData.male_count }} <span class="text-xs text-gray-400">({{ reportData.male_proportion
                    }}%)</span>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    {{ reportData.total - reportData.male_count }} <span class="text-xs text-gray-400">({{ (100 -
                      reportData.male_proportion).toFixed(2) }}%)</span>
                  </td>
                  <td class="px-6 py-4 text-sm">-</td>
                  <td class="px-6 py-4 text-sm">{{ reportData.total }}</td>
                </tr>
              </tfoot>
            </table>


            <table v-if="tableType === 'art-children'" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age Group
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Male</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Female</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Other</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% Children
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ageGroup }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ data.male }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ data.female }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ data.other }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ data.total }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-medium">
                    {{ data.children_proportion }}%
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50 font-semibold">
                <tr>
                  <td class="px-6 py-4 text-sm">Grand Total</td>
                  <td colspan="3"></td>
                  <td class="px-6 py-4 text-sm">{{ reportData.total_patients }}</td>
                  <td class="px-6 py-4 text-sm text-blue-600 font-medium">
                    {{ reportData.proportion }}%
                    <span class="text-xs text-gray-500">({{ reportData.total_children }} children)</span>
                  </td>
                </tr>
              </tfoot>
            </table>

            <!-- Breakdown Table -->
            <table v-if="tableType === 'pregnant-women-ltfu'"
              class="min-w-full divide-y divide-gray-200 border border-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Age Group
                  </th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    LTFU
                  </th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Total
                  </th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Proportion
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                  <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">
                    {{ ageGroup }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-center border-r">
                    {{ data.ltfu }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-center border-r">
                    {{ data.total_pregnant }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-semibold text-red-600">
                    {{ data.proportion }}%
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="tableType === 'pregnant-women'" class="mt-6">
              <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Age Group
                    </th>
                    <th
                      class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Retained
                    </th>
                    <th
                      class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Total Pregnant*
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Proportion
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">
                      {{ ageGroup }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center border-r">
                      {{ data.retained }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center border-r">
                      {{ data.total_pregnant }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-semibold"
                      :class="{ 'text-green-600': data.proportion > 75, 'text-yellow-500': data.proportion > 50 && data.proportion <= 75, 'text-red-600': data.proportion <= 50 }">
                      {{ data.proportion }}%
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- Info note about proportions -->
              <div class="mt-4 bg-blue-50 p-3 rounded-lg border border-blue-200">
                <p class="text-sm text-blue-700 flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 mt-0.5 flex-shrink-0" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                      clip-rule="evenodd" />
                  </svg>
                  Proportion represents the percentage of retained pregnant women relative to the total pregnant
                  population
                  in each age group
                </p>
              </div>
            </div>

            <div v-if="tableType === 'pregnant-women-died'" class="mt-6">
              <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Age Group
                    </th>
                    <th
                      class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Died
                    </th>
                    <th
                      class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Total
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Proportion
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">
                      {{ ageGroup }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center border-r">
                      {{ data.died }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center border-r">
                      {{ data.total_pregnant }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-semibold text-red-600">
                      {{ data.proportion }}%
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Additional context note -->
              <div class="mt-4 bg-yellow-50 p-3 rounded-lg border border-yellow-200">
                <p class="text-sm text-yellow-700">
                  Note: This metric shows pregnant women who died within 12 months of their recorded pregnancy visit.
                  Deaths are identified through patient status records or visits marked as deceased.
                </p>
              </div>
            </div>

            <!-- Summary Card -->
            <div v-if="reportData.indicator === 'Proportion of children on ART'" class="mb-6">
              <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div class="flex items-center">
                  <div class="flex-1">
                    <h4 class="font-medium text-blue-800">Child Patients Overview</h4>
                    <p class="text-sm text-blue-600 mt-1">
                      {{ reportData.total_children }} of {{ reportData.total_patients }}
                      patients are children (&lt;18 years)
                    </p>
                  </div>
                  <div class="text-3xl font-bold text-blue-700">
                    {{ reportData.proportion }}%
                  </div>
                </div>

                <div class="mt-4 bg-white p-3 rounded-lg">
                  <div class="flex items-center">
                    <span class="text-sm text-gray-600 mr-3">Age Distribution:</span>
                    <div class="flex-1 flex overflow-x-auto pb-2">
                      <div v-for="(data, ageGroup) in reportData.breakdown">

                        <div v-if="isChildAgeGroup(ageGroup)" class="flex flex-col items-center mx-2">
                          <span class="text-xs text-gray-500 mb-1">{{ ageGroup }}</span>
                          <span class="text-sm font-medium">{{ data.total }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- For percentage-based reports -->
            <div v-if="hasPercentageMetrics()" class="mt-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <template v-if="isMissedAppointmentsReport()">
                  <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">Missed Visits</p>
                    <p class="text-2xl font-bold">{{ reportData.missed_visits }}</p>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-800">Total Visits</p>
                    <p class="text-2xl font-bold">{{ reportData.total_visits }}</p>
                  </div>
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">Missed Percentage</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>
                </template>

                <template v-if="isPregnantWomenReport() && !isPregnantWomenLTFUReport()">
                  <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">Retained</p>
                    <p class="text-2xl font-bold">{{ reportData.retained }}</p>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-800">Total Pregnant</p>
                    <p class="text-2xl font-bold">{{ reportData.total_pregnant }}</p>
                  </div>
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">Retention Rate</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>
                </template>

                <template v-if="isPregnantWomenLTFUReport()">
                  <div class="bg-red-50 p-4 rounded-lg">
                    <p class="text-sm text-red-800">LTFU Count</p>
                    <p class="text-2xl font-bold">{{ reportData.ltfu_count }}</p>
                  </div>
                  <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">Total Pregnant</p>
                    <p class="text-2xl font-bold">{{ reportData.total_pregnant }}</p>
                  </div>
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">LTFU Percentage</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>
                </template>

                <template v-if="isPregnantWomenDiedReport()">
                  <div class="bg-red-50 p-4 rounded-lg">
                    <p class="text-sm text-red-800">Died Count</p>
                    <p class="text-2xl font-bold">{{ reportData.died_count }}</p>
                  </div>
                  <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">Total Pregnant</p>
                    <p class="text-2xl font-bold">{{ reportData.total_pregnant }}</p>
                  </div>
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">Mortality Percentage</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>
                </template>

                <!-- Retention report -->
                <template v-if="reportData.indicator.includes('retained after 12 months') && !isPregnantWomenReport()">
                  <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-800">Retained</p>
                    <p class="text-2xl font-bold">{{ reportData.retained }}</p>
                  </div>
                  <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">Total Eligible</p>
                    <p class="text-2xl font-bold">{{ reportData.total_eligible }}</p>
                  </div>
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">Retention Rate</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>
                </template>

                <!-- Retention report -->
                <template v-if="reportData.indicator.includes('retained after 24 months') && !isPregnantWomenReport()">
                  <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-800">Retained</p>
                    <p class="text-2xl font-bold">{{ reportData.retained }}</p>
                  </div>
                  <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">Total Eligible</p>
                    <p class="text-2xl font-bold">{{ reportData.total_eligible }}</p>
                  </div>
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">Retention Rate</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>
                </template>

                <!-- Retention report -->
                <template v-if="reportData.indicator.includes('Missed appointments')">

                  <div class="bg-red-50 p-4 rounded-lg">
                    <p class="text-sm text-red-800">Missed Appointments</p>
                    <p class="text-2xl font-bold">{{ reportData.missed_appointments }}</p>
                  </div>

                  <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">Total Appointments</p>
                    <p class="text-2xl font-bold">{{ reportData.total_appointments }}</p>
                  </div>

                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">Missed Percentage</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>

                  <div class="mt-2 flex items-center text-sm" v-if="reportData.previous_percentage !== undefined">
                    <span :class="{
                      'text-green-600': reportData.percentage < reportData.previous_percentage,
                      'text-red-600': reportData.percentage > reportData.previous_percentage
                    }">
                      <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path v-if="reportData.percentage < reportData.previous_percentage"
                          d="M13 10V3h-2v7H8l4 4 4-4h-3zm-9 8h14v-2H4v2z" />
                        <path v-else d="M13 17v-7h-2v7H8l4 4 4-4h-3zM4 4h14v2H4V4z" />
                      </svg>
                      {{ Math.abs(reportData.percentage - reportData.previous_percentage) }}%
                      {{ reportData.percentage < reportData.previous_percentage ? 'decrease' : 'increase' }} from
                        previous period </span>
                  </div>

                </template>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12 text-gray-500">
            Select parameters and generate report to view data
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>