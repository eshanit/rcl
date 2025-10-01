<script setup lang="ts">
import StandardTable from '../tables/StandardTable.vue';
import ViralSuppressionTable from '../tables/ViralSuppressionTable.vue';

const props = defineProps<{
    reportData: {
        indicator: number;
        total: number;
        breakdown: any;
        coverage: any;
    };
     startDate: string;
    endDate: string;
}>();
</script>
<template>
    <!-- <h4 class="text-md font-medium mb-3">{{ reportData.indicator }}</h4> -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-sm text-green-800">Re-engaged Patients</p>
            <p class="text-2xl font-bold">{{ reportData.total }}</p>
        </div>
        <div class="bg-blue-50 p-4 rounded-lg">
            <p class="text-sm text-blue-800">Reporting Period</p>
            <p class="text-lg">
                {{ props.startDate }} to {{ props.endDate }}
            </p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
            <p class="text-sm text-purple-800">Coverage</p>
            <p class="text-lg">{{ reportData.coverage || 'All facilities' }}</p>
        </div>
    </div>

    <!-- Key metrics card -->


    <!-- Viral Load Breakdown Table -->
    <h5 class="text-md font-medium mb-3">Patient ART initiation (Pregant Women)</h5>
    <StandardTable :reportData="reportData" />

    <div class="mt-4 bg-blue-50 p-3 rounded-lg border border-blue-200">
        <p class="text-sm text-blue-700">
                This report shows patients who were pregnant at the time of ART initiation. 
      Early ART initiation during pregnancy is crucial for preventing mother-to-child 
      transmission of HIV and ensuring maternal health.
        </p>
    </div>
</template>