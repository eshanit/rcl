<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import RetentionChart from '@/components/charts/RetentionChart.vue';
import { Card } from '@/components/ui/card';

const props = defineProps<{
    summary: any;
    retention: any;
    viralLoad: any;
    regimen: any;
    tb: any;
    maternal: any;
    mortality: any;
    demographics: any;
    cohorts: any;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Analytics', href: '/reports' },
    { title: 'Grouped Analysis', href: '' }
];

// Prepare retention chart data
const retentionChartData = computed(() => {
    if (!props.retention?.rates) return {};

    const labels = Object.keys(props.retention.rates).map(months => `${months} Months`);
    const values = Object.values(props.retention.rates);

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
</script>
<template>
  <AppLayout title="ART Program Dashboard" :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Grouped Analysis Dashboard</h1>
        <p class="text-gray-600">Comprehensive analysis of ART program performance metrics</p>
      </div>

      <!-- Retention Rates Analysis -->
       <pre>  
          {{ props.retention }}
       </pre>
      <div class="mb-8">
        <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm">
          <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800">Patient Retention Rates</h2>
                <p class="text-sm text-gray-600">Retention rates at 6, 12, 24, 60, 120, 180, 240, and 300 months</p>
              </div>
            </div>

            <!-- Retention Chart -->
            <div class="mb-6">
              <div class="h-96">
                <RetentionChart :data="retentionChartData" />
              </div>
            </div>

            <!-- Retention Rates Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Time Period
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
                  <tr v-for="(rate, months) in props.retention?.rates || {}" :key="months"
                      class="hover:bg-gray-50/50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ months }} Months
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                      {{ rate }}%
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        rate >= 80 ? 'bg-green-100 text-green-800' :
                        rate >= 60 ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'
                      ]">
                        {{ rate >= 80 ? 'Excellent' : rate >= 60 ? 'Good' : 'Needs Improvement' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Additional Retention Metrics -->
            <div v-if="props.retention" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-blue-50 p-4 rounded-xl">
                <div class="text-sm text-blue-600 font-medium mb-1">LTFU Count</div>
                <div class="text-2xl font-bold text-blue-700">{{ props.retention.ltfu_count || 0 }}</div>
              </div>
              <div class="bg-green-50 p-4 rounded-xl">
                <div class="text-sm text-green-600 font-medium mb-1">Re-engaged Patients</div>
                <div class="text-2xl font-bold text-green-700">{{ props.retention.reengaged_count || 0 }}</div>
              </div>
              <div class="bg-purple-50 p-4 rounded-xl">
                <div class="text-sm text-purple-600 font-medium mb-1">Missed Visit Rate</div>
                <div class="text-2xl font-bold text-purple-700">{{ props.retention.missed_visit_rate || 0 }}%</div>
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