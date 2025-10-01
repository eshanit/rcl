<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { CalendarIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { Card } from '@/components/ui/card';
import StatCard from '@/components/reports/StatCard.vue';
import ChartCard from '@/components/charts/ChartCard.vue';
import RetentionChart from '@/components/charts/RetentionChart.vue';
import ViralLoadChart from '@/components/charts/ViralLoadChart.vue';
import GenderChart from '@/components/charts/GenderChart.vue';
import AgeChart from '@/components/charts/AgeChart.vue';
import TBCoverageChart from '@/components/charts/TBCoverageChart.vue';
import CohortPerformanceTable from '@/components/tables/CohortPeformanceTable.vue';
import MaternalRetentionChart from '@/components/charts/MaternalRetentionCard.vue';
import PregnancyInitiationChart from '@/components/charts/PregnancyInitiationChart.vue';
import MortalityTrendChart from '@/components/charts/MortalityTrendChart.vue';
import AgeAtDeathChart from '@/components/charts/AgeAtDeathChart.vue';

// Props from backend controller

const props = defineProps<{
  summary: {
    total_patients: number;
    active_percentage: number;
  };
  retention: any; // Define more specific type if available
  viralLoad: any; // Define more specific type if available
  regimen: any; // Define more specific type if available
  tb: any; // Define more specific type if available
  maternal: any; // Define more specific type if available
  mortality: any; // Define more specific type if available
  demographics: any; // Define more specific type if available
  cohorts: Array<any>; // Define more specific type if available
}>();
// UI State
const selectedPeriod = ref('12');
const activeTab = ref('overview');

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'retention', label: 'Retention' },
  { id: 'viral', label: 'Viral Load' },
  { id: 'tb', label: 'TB Program' },
  { id: 'maternal', label: 'Maternal Health' },
  { id: 'mortality', label: 'Mortality' },
];

// Chart data
const retentionData = {
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  datasets: [
    {
      label: 'Current Year',
      data: [72, 74, 76, 78, 80, 82, 84, 83, 82, 84, 85, 86],
      borderColor: '#4F46E5',
      backgroundColor: 'rgba(79, 70, 229, 0.1)',
      tension: 0.3,
      fill: true
    },
    {
      label: 'Previous Year',
      data: [68, 70, 72, 75, 77, 78, 80, 79, 78, 80, 82, 83],
      borderColor: '#9CA3AF',
      borderDash: [5, 5],
      tension: 0.3,
      fill: false
    }
  ]
};

// Use real data from backend instead of hardcoded values
const viralLoadData = computed(() => {
  if (!props.viralLoad?.suppression_rates) {
    return {
      labels: ['6 Months', '12 Months', '24 Months'],
      values: [0, 0, 0]
    };
  }

  return {
    labels: Object.keys(props.viralLoad.suppression_rates).map(key => `${key} Months`),
    values: Object.values(props.viralLoad.suppression_rates)
  };
});
</script>
<template>
  <AppLayout title="ART Program Dashboard">
    <div class="px-6 py-8">
      <!-- Dashboard Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">ART Program Dashboard</h1>
          <p class="text-gray-600 mt-2">Comprehensive overview of HIV treatment program metrics</p>
        </div>
        <div class="flex items-center space-x-4 mt-4 md:mt-0">
          <div class="relative">
            <select v-model="selectedPeriod"
              class="pl-10 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="12">Last 12 Months</option>
              <option value="6">Last 6 Months</option>
              <option value="3">Last 3 Months</option>
              <option value="custom">Custom Range</option>
            </select>
            <CalendarIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
          </div>
          <button class="bg-primary text-white px-4 py-2 rounded-lg flex items-center hover:bg-primary-dark transition">
            <ArrowDownTrayIcon class="h-5 w-5 mr-2" />
            Export Report
          </button>
        </div>
      </div>

      <!-- Dashboard Tabs -->
      <div class="bg-white rounded-xl shadow p-4 mb-6">
        <div class="flex space-x-6 overflow-x-auto">
          <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
            'pb-3 px-2 whitespace-nowrap',
            activeTab === tab.id
              ? 'text-primary font-semibold border-b-2 border-primary'
              : 'text-gray-600 hover:text-primary'
          ]">
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <StatCard title="Total Patients" :value="summary.total_patients.toLocaleString()" icon="users" color="art"
          :trend="{
            value: `${summary.active_percentage}% active`,
            direction: 'up'
          }" />

        <StatCard title="Viral Suppression" value="86%" icon="vial" color="viral" :trend="{
          value: '3.2% improvement',
          direction: 'up'
        }" />

        <StatCard title="TB Screening" value="78%" icon="lungs" color="tb" :trend="{
          value: 'Needs improvement',
          direction: 'down'
        }" />

        <StatCard title="Maternal Retention" value="92%" icon="baby" color="maternal" :trend="{
          value: '4.5% improvement',
          direction: 'up'
        }" />

        <StatCard title="Retention (24 mo)" value="65%" icon="clock" color="retention" :trend="{
          value: 'Target: 75%',
          direction: 'neutral'
        }" />
      </div>

      <!-- Main Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Retention Rates -->
        <ChartCard title="Retention Rates Over Time">
          <RetentionChart :data="retentionData" />
        </ChartCard>

        <!-- Viral Suppression -->
        <ChartCard title="Viral Suppression Rates">
          <ViralLoadChart :data="viralLoadData" />
        </ChartCard>

        <!-- Patient Demographics -->
        <ChartCard title="Patient Demographics" class="mb-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h3 class="text-md font-medium text-gray-700 mb-2">Gender Distribution</h3>
              <GenderChart :data="demographics.gender_distribution" />

            </div>
            <div>
              <h3 class="text-md font-medium text-gray-700 mb-2">Count Patients by Gender</h3>
              <div v-for="(demog, index) in demographics.gender_distribution" :key="index"
                class="text-sm text-gray-600 flex justify-between items-center mb-2">
                <span class="font-semibold">
                  {{ demog.gender }}
                </span>
                <span class="font-semibold">
                  {{ demog.count }}
                </span>
              </div>

            </div>
          </div>
        </ChartCard>

        <ChartCard title="Patient Age Distribution">
              <AgeChart :data="demographics.art_initiation_age" />
        </ChartCard>


        <!-- TB Program Metrics -->
        <ChartCard title="TB Program Metrics">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h3 class="text-md font-medium text-gray-700 mb-2">Screening Coverage</h3>
              <TBCoverageChart :coverage="tb.screening_coverage" />
            </div>
            <div class="flex flex-col justify-center">
              <h3 class="text-md font-medium text-gray-700 mb-2">Current on TB Treatment</h3>
              <div class="text-center py-4">
                <div class="text-4xl font-bold text-tb">{{ tb.current_tb_treatment.toLocaleString() }}</div>
                <p class="text-sm text-gray-600 mt-2">Patients receiving TB treatment</p>
              </div>
            </div>
          </div>
        </ChartCard>
      </div>

      <!-- Cohort Performance -->
      <ChartCard title="Cohort Performance" class="mb-8">
        <CohortPerformanceTable :cohorts="cohorts" />
      </ChartCard>

      <!-- Additional Metrics -->
      <div v-if="activeTab === 'maternal'" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <ChartCard title="Maternal Retention Rates">
          <MaternalRetentionChart :data="maternal" />
        </ChartCard>
        <ChartCard title="ART Initiation During Pregnancy">
          <PregnancyInitiationChart :initiated="maternal.art_initiation_during_pregnancy" />
        </ChartCard>
      </div>

      <div v-if="activeTab === 'mortality'" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <ChartCard title="Mortality Trends">
          <MortalityTrendChart :data="mortality" />
        </ChartCard>
        <ChartCard title="Age at Death Distribution">
          <AgeAtDeathChart :data="mortality" />
        </ChartCard>
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