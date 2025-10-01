<script setup lang="ts">
import LTFUandReengagedTable from '../tables/LTFUandReengagedTable.vue';

const props = defineProps<{
    reportData: any
    startDate: string;
    endDate: string;
}>();
</script>
<template>
    <h4 class="text-md font-medium mb-3">Re-engagement After LTFU</h4>

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

    <!--table-->

    <LTFUandReengagedTable :report-data="props.reportData" />

    <!-- descriptive text -->

    <div class="mt-4 bg-blue-50 p-3 rounded-lg border border-blue-200">
        <p class="text-sm text-blue-700">
            This report shows patients who had at least one visit with a 90+ day lapse in care (LTFU)
            but subsequently returned for another visit. Re-engagement demonstrates patient retention
            despite previous challenges.
        </p>
    </div>

</template>
