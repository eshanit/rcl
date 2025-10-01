<script setup lang="ts">
import AgeSurvivalChart from '../charts/AgeSurvivalChart.vue';
import GenderSurvivalChart from '../charts/genderSurvivalChart.vue';

const props = defineProps<{
  reportData: any
}>();
</script>
<template>
  <!-- <h4 class="text-md font-medium mb-3">Age at Death Distribution</h4> -->

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-blue-50 p-4 rounded-lg">
      <p class="text-sm text-blue-800">Patients Analyzed</p>
      <p class="text-2xl font-bold">{{ reportData.total_patients }}</p>
    </div>
    <div class="bg-green-50 p-4 rounded-lg">
      <p class="text-sm text-green-800">Median Survival</p>
      <p class="text-2xl font-bold">{{ reportData.survival_statistics.median_survival_years }} years</p>
    </div>
    <div class="bg-purple-50 p-4 rounded-lg">
      <p class="text-sm text-purple-800">Coverage</p>
      <p class="text-lg">{{ reportData.coverage || 'All facilities' }}</p>
    </div>
  </div>

  <!-- Survival Rates -->
  <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
    <h5 class="text-md font-medium mb-4">Survival Rates at Time Points</h5>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
      <div v-for="(rate, interval) in reportData.survival_statistics.survival_rates" :key="interval"
           class="text-center p-3 bg-gray-50 rounded-lg">
        <p class="text-sm text-gray-600">{{ interval.replace('_', ' ') }}</p>
        <p class="text-xl font-bold text-blue-600">{{ rate }}%</p>
      </div>
    </div>
  </div>

  <!-- chart -->
   <!-- Comparison Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Age Group Chart -->
      <AgeSurvivalChart :survival-data="reportData" />
      
      <!-- Gender Chart -->
      <GenderSurvivalChart :survival-data="reportData" />
    </div>

  <!--table-->

  <!-- <AgeAtDeathTable :report-data="props.reportData" /> -->

  <!-- descriptive text -->

    <!-- Additional Gender Insights -->
    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
      <h5 class="text-md font-medium mb-3">Gender Insights</h5>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
        <div v-for="gender in ['male', 'female', 'other']" :key="gender"
             :class="{
               'bg-blue-50': gender === 'male',
               'bg-pink-50': gender === 'female',
               'bg-purple-50': gender === 'other'
             }" 
             class="p-3 rounded-lg">
          <p class="text-sm capitalize text-gray-600">{{ gender }}</p>
          <p class="text-lg font-bold">
            {{ reportData.by_gender[gender] || 0 }} months
          </p>
          <p class="text-sm text-gray-500">
            {{ ((reportData.by_gender[gender] || 0) / 12).toFixed(1) }} years
          </p>
        </div>
      </div>
    </div>
</template>
