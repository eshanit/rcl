<script setup lang="ts">
import StandardTable from '../tables/StandardTable.vue';

const props = defineProps<{
    reportData: {
        total: number;
        coverage: string;
        breakdown: any
    };
    startDate: string;
    endDate: string;
}>();

</script>
<template>
    <div class="bg-red-50 p-6 rounded-lg border border-red-200">
        <div class="flex items-center mb-4">
            <svg class="h-8 w-8 text-red-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h4 class="text-xl font-semibold text-red-800">Mortality Report</h4>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-lg shadow">
                <p class="text-sm text-gray-600">Total Deaths</p>
                <p class="text-3xl font-bold text-red-700">{{ props.reportData.total }}</p>
            </div>

            <div class="bg-white p-4 rounded-lg shadow">
                <p class="text-sm text-gray-600">Reporting Period</p>
                <p class="text-lg font-medium">
                    {{ props.startDate }}
                    to
                    {{ props.endDate }}
                </p>
            </div>

            <div class="bg-white p-4 rounded-lg shadow">
                <p class="text-sm text-gray-600">Coverage</p>
                <p class="text-lg font-medium">
                    {{ props.reportData.coverage || 'All facilities' }}
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
        <p class="text-sm text-yellow-800 flex items-start">
            <svg class="h-5 w-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Note: This report shows patients who were active in ART care and whose status was recorded as
            deceased.
        </p>
    </div>
<!-- Breakdown Table -->
    <StandardTable :reportData="props.reportData" />
</template>