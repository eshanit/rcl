<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  cohorts: Array
});

function getStatusText(status) {
  switch (status) {
    case 'excellent': return 'Excellent';
    case 'good': return 'Good';
    case 'needs_attention': return 'Needs Attention';
    default: return status;
  }
}
</script>
<template>
  <div class="overflow-x-auto">
    <table class="min-w-full">
      <thead>
        <tr class="text-left text-gray-500 text-sm border-b">
          <th class="pb-3">Cohort</th>
          <th class="pb-3">Patients</th>
          <th class="pb-3">Retention (24 mo)</th>
          <th class="pb-3">Viral Suppression</th>
          <th class="pb-3">LTFU Rate</th>
          <th class="pb-3">Avg. Visits</th>
          <th class="pb-3">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr v-for="cohort in cohorts" :key="cohort.id">
          <td class="py-4 font-medium">{{ cohort.name }}</td>
          <td class="py-4">{{ cohort.patient_count.toLocaleString() }}</td>
          <td class="py-4">
            <div class="flex items-center">
              <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                <div 
                  class="h-2 rounded-full" 
                  :class="{
                    'bg-green-600': cohort.retention_rates['24'] > 75,
                    'bg-yellow-400': cohort.retention_rates['24'] > 65 && cohort.retention_rates['24'] <= 75,
                    'bg-red-500': cohort.retention_rates['24'] <= 65
                  }" 
                  :style="{ width: `${cohort.retention_rates['24']}%` }"
                ></div>
              </div>
              <span>{{ cohort.retention_rates['24'] }}%</span>
            </div>
          </td>
          <td class="py-4">{{ cohort.viral_suppression['24'] }}%</td>
          <td class="py-4">{{ cohort.ltfu_rate }}%</td>
          <td class="py-4">{{ cohort.avg_visits }}</td>
          <td class="py-4">
            <span 
              class="px-3 py-1 rounded-full text-xs"
              :class="{
                'bg-green-100 text-green-800': cohort.status === 'excellent',
                'bg-yellow-100 text-yellow-800': cohort.status === 'good',
                'bg-red-100 text-red-800': cohort.status === 'needs_attention'
              }"
            >
              {{ getStatusText(cohort.status) }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
