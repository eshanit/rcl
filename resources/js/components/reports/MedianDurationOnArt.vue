<script setup lang="ts">
import MedianDurationOnArtTable from '../tables/MedianDurationOnArtTable.vue';


const props = defineProps<{
    reportData: {
        indicator: number;
        median_duration: number;
        total_active_patients: number;
        breakdown: any;
        coverage: any;
    };
}>();
</script>
<template>
    <!-- <h4 class="text-md font-medium mb-3">{{ reportData.indicator }}</h4> -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-blue-50 p-4 rounded-lg">
      <p class="text-sm text-blue-800">Median Duration</p>
      <p class="text-2xl font-bold">{{ Math.round(reportData.median_duration) }} months</p>
    </div>
    <div class="bg-green-50 p-4 rounded-lg">
      <p class="text-sm text-green-800">Active Patients</p>
      <p class="text-2xl font-bold">{{ reportData.total_active_patients }}</p>
    </div>
    <div class="bg-purple-50 p-4 rounded-lg">
      <p class="text-sm text-purple-800">Coverage</p>
      <p class="text-lg">{{ reportData.coverage || 'All facilities' }}</p>
    </div>
  </div>

    <!-- Key metrics card -->


    <!-- Viral Load Breakdown Table -->
    <h5 class="text-md font-medium mb-3">Patient ART initiation (Pregant Women)</h5>
    <MedianDurationOnArtTable :reportData="reportData" />

    <div class="mt-4 bg-blue-50 p-3 rounded-lg border border-blue-200">
        <p class="text-sm text-blue-700">
              This report shows the median duration (in months) that active patients have been on ART.
      Active patients are defined as those who are not deceased, not transferred out, and have had
      a visit within the last 90 days. The median provides a measure of central tendency that is
      less affected by outliers than the mean.
        </p>
    </div>
</template>