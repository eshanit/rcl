<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

interface Indicator {
  label: string;
  value: string;
}

interface Cohort {
  id: number;
  name: string;
}

interface BreakdownData {
  male: number;
  female: number;
  other: number;
  total: number;
}

interface ReportData {
  indicator: string;
  breakdown?: Record<string, BreakdownData>;
  total?: number;
  percentage?: number;
  missed_visits?: number;
  total_visits?: number;
  retained?: number;
  total_pregnant?: number;
}

const props = defineProps<{
  cohorts: Cohort[];
  initialReport?: ReportData | null;
}>();

// Form state using Inertia's useForm
const form = useForm({
  indicator: 'TotalPatientsEverEnrolled',
  cohort_id: '',
  start_date: '',
  end_date: ''
});

// Report data
const reportData = ref<ReportData | null>(props.initialReport || null);

// Indicator options
const indicators: Indicator[] = [
  { label: 'Total Patients Ever Enrolled', value: 'TotalPatientsEverEnrolled' },
  { label: 'Newly Initiated on ART', value: 'NewlyInitiatedOnART' },
  { label: 'Patients Retained After 12 Months', value: 'PatientsRetainedAfter12Months' },
  { label: 'Patients With Suppressed Viral Load', value: 'PatientsWithSuppressedViralLoad' },
  { label: 'Patients Screened For TB', value: 'PatientsScreenedForTB' },
  { label: 'Missed Appointment Visits', value: 'MissedAppointmentVisits' },
  { label: 'Pregnant Women Retained After 12 Months', value: 'PregnantWomenRetainedAfter12Months' },
];

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
</script>

<template>
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Indicator Analysis Report
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <!-- Filter Form -->
          <form @submit.prevent="fetchReport" class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Indicator</label>
              <select v-model="form.indicator" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option v-for="ind in indicators" :key="ind.value" :value="ind.value">
                  {{ ind.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Cohort</label>
              <select v-model="form.cohort_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">All Cohorts</option>
                <option v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id">
                  {{ cohort.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Start Date</label>
              <input type="date" v-model="form.start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">End Date</label>
              <input type="date" v-model="form.end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>

            <div class="flex items-end">
              <button 
                type="submit" 
                :disabled="form.processing"
                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                <span v-if="!form.processing">Generate Report</span>
                <span v-else>Generating...</span>
              </button>
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
            
            <!-- Table for indicators with breakdown -->
            <table v-if="hasBreakdown()" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age Group</th>
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
                
                <template v-else-if="isPregnantWomenReport()">
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