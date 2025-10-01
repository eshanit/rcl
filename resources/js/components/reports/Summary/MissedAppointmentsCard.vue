<script setup lang="ts">
const props = defineProps<{
    reportData: {
        missed_appointments: number;
        total_appointments: number;
        previous_percentage: number;
        percentage: number;
    };
}>();
</script>
<template>
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