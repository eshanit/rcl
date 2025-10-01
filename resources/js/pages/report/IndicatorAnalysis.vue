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
import TimeToFirstVL from '@/components/reports/TimeToFirstVL.vue';
import ARTPatientsDeath from '@/components/reports/ARTPatientsDeath.vue';
import MissedAppointmentsSummaryCard from '@/components/reports/Summary/MissedAppointmentsCard.vue';
import PregnantWomanSummaryCard from '@/components/reports/Summary/PregnantWomanCard.vue';
import PregnantWomanDiedSummaryCard from '@/components/reports/Summary/PregnantWomanDiedCard.vue';
import PregnantWomanLTFUSummaryCard from '@/components/reports/Summary/PregnantWomanLTFUCard.vue';
import RetainedSummaryCard from '@/components/reports/Summary/RetainedCard.vue';
import LTFandReengaged from '@/components/reports/LTFandReengaged.vue';
import MissedVisitRatesSeverity from '@/components/reports/MissedVisitRatesSeverity.vue';
import AppointmentAdherence from '@/components/reports/AppointmentAdherence.vue';
import ViralSuppression6Months from '@/components/reports/ViralSuppression6Months.vue';
import ViralSuppression12Months from '@/components/reports/ViralSuppression12Months.vue';
import ViralSuppression24Months from '@/components/reports/ViralSuppression24Months.vue';
import PregnantARTInitiated from '@/components/reports/PregnantARTInitiated.vue';
import MedianDurationOnArt from '@/components/reports/MedianDurationOnArt.vue';
import DeathOverTime from '@/components/reports/DeathOverTime.vue';
import AgeAtDeath from '@/components/reports/AgeAtDeath.vue';
import SurvivalAnalysis from '@/components/reports/SurvivalAnalysis.vue';

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
  // { label: 'Patients With Unknown Age Group', value: 'PatientsWithUnknownAgeGroup' },
  { label: 'Patients Enrolled on ART', value: 'EnrolledOnART' },
  { label: 'Proportion of Children on ART', value: 'ProportionOfChildrenOnART' },
  { label: 'Patients Retained After 12 Months (%)', value: 'PatientsRetainedAfter12Months' },
  { label: 'Patients Retained After 24 Months (%)', value: 'PatientsRetainedAfter24Months' },
  { label: 'Patients Retained After 60 Months (%)', value: 'PatientsRetainedAfter60Months' },
  { label: 'Patients Retained After 120 Months (%)', value: 'PatientsRetainedAfter120Months' },
  { label: 'Patients Retained After 180 Months (%)', value: 'PatientsRetainedAfter180Months' },
  { label: 'Patients Retained After 240 Months (%)', value: 'PatientsRetainedAfter240Months' },
  { label: 'Patients Retained After 300 Months (%)', value: 'PatientsRetainedAfter300Months' },
  { label: 'Patients With Suppressed Viral Load', value: 'PatientsWithSuppressedViralLoad' },
  { label: 'Patients LTFU and Re-engaged', value: 'PatientsLTFUAndReengaged' },
  { label: 'Time to First Viral Load', value: 'TimeToFirstViralLoad' },
  { label: 'Patients Screened For TB', value: 'PatientsScreenedForTB' },
  { label: 'Patients on TB Treatment', value: 'PatientsOnTBTreatment' },
  { label: 'Missed Appointment Visits (90+ days)', value: 'MissedAppointmentVisits' },
  { label: 'Missed Appointments (%)', value: 'MissedAppointments' },
  { label: 'Missed Visit Rates by Severity', value: 'MissedVisitRates' },
  { label: 'Appointment Adherence', value: 'AppointmentAdherence' },
  // In your indicators array
  {
    label: 'Viral Suppression at 6 Months',
    value: 'ViralSuppressionAt6Months'
  },
  {
    label: 'Viral Suppression at 12 Months',
    value: 'ViralSuppressionAt6Months'
  },
  {
    label: 'Viral Suppression at 24 Months',
    value: 'ViralSuppressionAt24Months'
  },
  // In your indicators array
  {
    label: 'Deaths Over Time',
    value: 'DeathsOverTime'
  },
  // In your indicators array
  {
    label: 'Median Duration on ART Among Active Patients',
    value: 'MedianDurationOnART'
  },
  { label: 'Deaths among ART patients', value: 'DeathsAmongART' },
  { label: 'Patients Transferred Out', value: 'TransferredOut' },
  { label: 'Pregnant Women Retained After 12 Months', value: 'PregnantWomenRetainedAfter12Months' },
  { label: 'Pregnant Women Retained After 24 Months', value: 'PregnantWomenRetainedAfter24Months' },
  { label: 'Pregnant Women LTFU After 12 Months', value: 'PregnantWomenLTFUAfter12Months' },
  { label: 'Pregnant Women Died Within 12 Months', value: 'PregnantWomenDiedWithin12Months' },
  // In your indicators array
  {
    label: 'Patients Initiated on ART Whilst Pregnant',
    value: 'PatientsInitiatedOnARTWhilstPregnant'
  },
  { label: 'Regimen Switches (Side Effects/Treatment Failure)', value: 'RegimenSwitches' },
  // In your indicators array
  {
    label: 'Age at death distribution',
    value: 'AgeAtDeath'
  },
  {
    label: 'Survival Analysis After ART',
    value: 'SurvivalAnalysis'
  }
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


// Add computed property
const tableType = computed(() => {
  if (!reportData.value || !hasBreakdown()) return null;

  if (reportData.value.indicator === 'Total patients ever enrolled on ART' || reportData.value.indicator === 'Patients Enrolled on ART') {
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
              <TimeToFirstVL :report-data="reportData" :start-date="formatDate(form.start_date) || 'Start'"
                :end-date="formatDate(form.end_date) || 'End'" />
            </div>

            <!-- Special display for Deaths report -->
            <div v-if="reportData.indicator === 'Deaths among ART patients'" class="mb-8">
              <ARTPatientsDeath :report-data="reportData" :start-date="formatDate(form.start_date) || 'Start'"
                :end-date="formatDate(form.end_date) || 'End'" />
            </div>

            <div v-if="reportData.indicator === 'Patients lost to follow-up and re-engaged'">
              <LTFandReengaged :report-data="reportData" :start-date="formatDate(form.start_date) || 'Start'"
                :end-date="formatDate(form.end_date) || 'End'" />
            </div>

            <div v-if="reportData.indicator === 'Missed visit rates by severity'">
              <MissedVisitRatesSeverity :report-data="reportData" />
            </div>

            <div v-if="reportData.indicator === 'Appointment adherence (within 7 days)'">
              <AppointmentAdherence :report-data="reportData" />
            </div>

            <div v-if="reportData.indicator === 'Viral suppression at 6 months after ART initiation'">
              <ViralSuppression6Months :report-data="reportData" />
            </div>

            <div v-if="reportData.indicator === 'Viral suppression at 12 months after ART initiation'">
              <ViralSuppression12Months :report-data="reportData" />
            </div>

            <div v-if="reportData.indicator === 'Viral suppression at 24 months after ART initiation'">
              <ViralSuppression24Months :report-data="reportData" />
            </div>

            <div v-if="reportData.indicator === 'Patients initiated on ART whilst pregnant'">
              <PregnantARTInitiated :report-data="reportData" :start-date="formatDate(form.start_date) || 'Start'"
                :end-date="formatDate(form.end_date) || 'End'" />
            </div>

            <div v-if="reportData.indicator === 'Median duration on ART among active patients'">
              <MedianDurationOnArt :report-data="reportData" />
            </div>

            <div v-if="reportData.indicator === 'Deaths over time'">
              <DeathOverTime :report-data="reportData" :start-date="formatDate(form.start_date) || 'Start'"
                :end-date="formatDate(form.end_date) || 'End'" />
            </div>

            <div v-if="reportData.indicator === 'Age at death distribution'">
              <AgeAtDeath :report-data="reportData" />
            </div>

            <div v-if="reportData.indicator === 'Survival analysis after ART initiation'">
              <SurvivalAnalysis :report-data="reportData" />
            </div>

            <template v-if="reportData.indicator === 'Pregnant women died within 12 months'">
              <h4 class="text-md font-medium mb-3">Mortality Among Pregnant Women (12 Months Post-Pregnancy)</h4>

              <!-- Summary Cards -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                  <p class="text-sm text-red-800">Deaths Count</p>
                  <p class="text-2xl font-bold">{{ reportData.died_count }}</p>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                  <p class="text-sm text-blue-800">Total Pregnant Women</p>
                  <p class="text-2xl font-bold">{{ reportData.total_pregnant }}</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                  <p class="text-sm text-purple-800">Mortality Rate</p>
                  <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                </div>
              </div>

              <!-- Mortality Insights -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Age Group Mortality -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                  <h5 class="text-md font-medium mb-4">Mortality Rate by Age Group</h5>
                  <div class="space-y-3">
                    <div v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup"
                      class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                      <span class="text-sm font-medium text-gray-700">{{ ageGroup }}</span>
                      <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600">
                          {{ data.died }}/{{ data.total_pregnant }}
                        </span>
                        <span class="text-sm font-semibold px-2 py-1 rounded" :class="{
                          'bg-red-100 text-red-800': data.proportion > 5,
                          'bg-yellow-100 text-yellow-800': data.proportion > 2 && data.proportion <= 5,
                          'bg-green-100 text-green-800': data.proportion <= 2
                        }">
                          {{ data.proportion }}%
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Mortality Context -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                  <h5 class="text-md font-medium mb-4">Mortality Context</h5>
                  <div class="space-y-4">
                    <div class="flex items-start gap-3">
                      <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-800">High-Risk Groups</p>
                        <p class="text-sm text-gray-600 mt-1">
                          Age groups with mortality rates above 5% may need targeted interventions
                        </p>
                      </div>
                    </div>

                    <div class="flex items-start gap-3">
                      <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-600 text-sm"></i>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-800">Measurement Period</p>
                        <p class="text-sm text-gray-600 mt-1">
                          Tracks deaths occurring within 12 months of recorded pregnancy
                        </p>
                      </div>
                    </div>

                    <div class="flex items-start gap-3">
                      <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-baby text-green-600 text-sm"></i>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-800">Maternal Health Focus</p>
                        <p class="text-sm text-gray-600 mt-1">
                          Critical indicator for maternal healthcare program effectiveness
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Detailed Breakdown Table -->
              <h5 class="text-md font-medium mb-3">Detailed Mortality Breakdown</h5>
              <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Age Group
                    </th>
                    <th
                      class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Deaths
                    </th>
                    <th
                      class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                      Total Pregnant
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Mortality Rate
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup"
                    class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r">
                      {{ ageGroup }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-red-600 font-semibold border-r">
                      {{ data.died }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-600 border-r">
                      {{ data.total_pregnant }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-semibold" :class="{
                      'text-red-600': data.proportion > 5,
                      'text-yellow-600': data.proportion > 2 && data.proportion <= 5,
                      'text-green-600': data.proportion <= 2
                    }">
                      {{ data.proportion }}%
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                  <tr>
                    <td class="px-6 py-4 text-sm border-r">Overall</td>
                    <td class="px-6 py-4 text-sm text-center text-red-600 border-r">
                      {{ reportData.died_count }}
                    </td>
                    <td class="px-6 py-4 text-sm text-center border-r">
                      {{ reportData.total_pregnant }}
                    </td>
                    <td class="px-6 py-4 text-sm text-center text-purple-600">
                      {{ reportData.percentage }}%
                    </td>
                  </tr>
                </tfoot>
              </table>

              <!-- Information and Context -->
              <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                  <h6 class="font-medium text-blue-800 mb-2 flex items-center gap-2">
                    <i class="fas fa-calendar-alt"></i>
                    Measurement Details
                  </h6>
                  <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Tracks deaths within 12 months of pregnancy date</li>
                    <li>• Uses patient status records and status dates</li>
                    <li>• Only includes pregnancies from 12+ months ago</li>
                    <li>• Excludes transferred out and LTFU patients</li>
                  </ul>
                </div>

                <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">
                  <h6 class="font-medium text-amber-800 mb-2 flex items-center gap-2">
                    <i class="fas fa-lightbulb"></i>
                    Program Implications
                  </h6>
                  <ul class="text-sm text-amber-700 space-y-1">
                    <li>• High rates may indicate gaps in postnatal care</li>
                    <li>• Age-specific patterns can guide targeted interventions</li>
                    <li>• Compare with general ART mortality rates for context</li>
                    <li>• Monitor trends over time for program improvement</li>
                  </ul>
                </div>
              </div>
            </template>

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

            <!-- For percentage-based reports -->
            <div v-if="hasPercentageMetrics()" class="mt-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <template v-if="isMissedAppointmentsReport()">
                  <MissedAppointmentsSummaryCard :report-data="reportData" />
                </template>

                <template v-if="isPregnantWomenReport() && !isPregnantWomenLTFUReport()">
                  <PregnantWomanSummaryCard :report-data="reportData" />
                </template>

                <template v-if="isPregnantWomenLTFUReport()">
                  <PregnantWomanLTFUSummaryCard :report-data="reportData" />
                </template>

                <template v-if="isPregnantWomenDiedReport()">
                  <PregnantWomanDiedSummaryCard :report-data="reportData" />
                </template>

                <!-- Retention report -->
                <template v-if="reportData.indicator.includes('retained after') && !isPregnantWomenReport()">
                  <RetainedSummaryCard :report-data="reportData" />
                </template>

                <!-- Retention report -->
                <template v-if="reportData.indicator.includes('Missed appointments')">

                  <MissedAppointmentsSummaryCard :report-data="reportData" />
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