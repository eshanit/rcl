<script setup lang="ts">
import AgeAtDeathChart from '../charts/AgeAtDeathChart.vue';
import AgeAtDeathTable from '../tables/AgeAtDeathTable.vue';

const props = defineProps<{
  reportData: any
}>();
</script>
<template>
  <h4 class="text-md font-medium mb-3">Age at Death Distribution</h4>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-red-50 p-4 rounded-lg">
      <p class="text-sm text-red-800">Total Deaths</p>
      <p class="text-2xl font-bold">{{ reportData.total_deaths }}</p>
    </div>
    <div class="bg-blue-50 p-4 rounded-lg">
      <p class="text-sm text-blue-800">Median Age</p>
      <p class="text-2xl font-bold">{{ Math.round(reportData.statistics.median_age) }} years</p>
    </div>
    <div class="bg-purple-50 p-4 rounded-lg">
      <p class="text-sm text-purple-800">Average Age</p>
      <p class="text-2xl font-bold">{{ reportData.statistics.average_age }} years</p>
    </div>
  </div>

  <!-- chart -->
  <AgeAtDeathChart :report-data="props.reportData" />

  <!--table-->

  <AgeAtDeathTable :report-data="props.reportData" />

  <!-- descriptive text -->


</template>
