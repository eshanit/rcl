<script setup lang="ts">
import StandardTable from '../tables/StandardTable.vue';

const props = defineProps<{
    reportData: {
        adherent_appointments: number;
        total: number;
        adherence_rate: number;
        breakdown: any;
    };
}>();

</script>
<template>
      <h4 class="text-md font-medium mb-3">Appointment Adherence</h4>
  
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-green-50 p-4 rounded-lg">
      <p class="text-sm text-green-800">Adherent Appointments</p>
      <p class="text-2xl font-bold">{{ reportData.adherent_appointments }}</p>
    </div>
    <div class="bg-blue-50 p-4 rounded-lg">
      <p class="text-sm text-blue-800">Total Appointments</p>
      <p class="text-2xl font-bold">{{ reportData.total }}</p>
    </div>
    <div class="bg-purple-50 p-4 rounded-lg">
      <p class="text-sm text-purple-800">Adherence Rate</p>
      <p class="text-2xl font-bold">{{ reportData.adherence_rate }}%</p>
    </div>
  </div>
    <!-- Age/Gender Breakdown -->
  <h5 class="text-md font-medium mb-3">Adherent Visits by Age and Gender</h5>
    <StandardTable :reportData="reportData" />
  
  <div class="mt-4 bg-blue-50 p-3 rounded-lg border border-blue-200">
    <p class="text-sm text-blue-700">
      Adherence is defined as visits occurring within 7 days of the scheduled appointment date.
      Higher adherence rates indicate better patient engagement and retention in care.
    </p>
  </div>
</template>