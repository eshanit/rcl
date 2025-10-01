<script setup lang="ts">
import { computed } from 'vue';
import { useGetGenderAverage } from '@/composables/useGetGenderAverage';
import ViralLoadTable from '../tables/ViralLoadTable.vue';

const props = defineProps<{
    reportData: {
        average_days: number;
        total_patients: number;
        median_days: number;
        patients_in_target: number;
        breakdown: any
    };
    startDate: string;
    endDate: string;
}>();

const maxGenderAverage = computed(() => {
    const male = useGetGenderAverage('male', props.reportData);
    const female = useGetGenderAverage('female', props.reportData);
    return Math.max(parseFloat(male), parseFloat(female), 100); // Ensure at least 100 for scaling
});

</script>
<template>
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <p class="text-sm text-blue-800">Average Time to First VL</p>
            <p class="text-3xl font-bold text-blue-600">
                {{ props.reportData.average_days }} days
            </p>
        </div>

        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
            <p class="text-sm text-green-800">Patients with VL Test</p>
            <p class="text-3xl font-bold text-green-600">
                {{ props.reportData.total_patients }}
            </p>
        </div>

        <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
            <p class="text-sm text-purple-800">Reporting Period</p>
            <p class="text-lg font-medium text-purple-600">
                {{ props.startDate }}
                to
                {{ props.endDate }}
            </p>
        </div>

        <div class="bg-purple-50 p-4 rounded-lg">
            <p class="text-sm text-purple-800">Median Time</p>
            <p class="text-2xl font-bold">{{ reportData.median_days }} days</p>
        </div>

        <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-sm text-green-800">Patients in Target</p>
            <p class="text-2xl font-bold">
                {{ reportData.patients_in_target }} / {{ reportData.total_patients }}
            </p>
        </div>

        <div class="bg-amber-50 p-4 rounded-lg">
            <p class="text-sm text-amber-800">Target Compliance</p>
            <p class="text-2xl font-bold">
                {{ reportData.total_patients ?
                    Math.round((reportData.patients_in_target / reportData.total_patients) * 100) : 0 }}%
            </p>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6">
        <p class="text-sm text-yellow-800 flex items-start">
            <svg class="h-5 w-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            This metric shows the average time between ART initiation and first viral load test.
            Lower values indicate patients are receiving timely viral load monitoring.
        </p>
    </div>

    <!-- Breakdown Table -->
    <h4 class="text-md font-medium mb-3">Average Days by Age Group and Gender</h4>

    <!--table -->
    <ViralLoadTable :reportData="props.reportData" />

    <!-- Visualization -->
    <div class="mt-8">
        <h4 class="text-md font-medium mb-3">Time Distribution by Age Group</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Bar Chart -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <div v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup" class="mb-3">
                    <div class="flex items-center">
                        <span class="w-32 text-sm text-gray-600 truncate">{{ ageGroup }}</span>
                        <div class="flex-1 ml-2">
                            <div class="h-6 bg-blue-100 rounded overflow-hidden">
                                <div class="h-full bg-blue-500 rounded"
                                    :style="{ width: Math.min(100, (data.total / reportData.average_days) * 50) + '%' }">
                                </div>
                            </div>
                        </div>
                        <span class="w-16 text-right text-sm font-medium ml-2">
                            {{ data.total }}d
                        </span>
                    </div>
                </div>
            </div>

            <!-- Gender Comparison -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <div class="flex mb-4">
                    <div class="w-1/2 text-center">
                        <div class="text-lg font-bold text-blue-600">{{ useGetGenderAverage('male', props.reportData) }}
                            days</div>
                        <div class="text-sm text-gray-600">Male Average</div>
                    </div>
                    <div class="w-1/2 text-center">
                        <div class="text-lg font-bold text-pink-600">{{ useGetGenderAverage('female', props.reportData)
                            }} days</div>
                        <div class="text-sm text-gray-600">Female Average</div>
                    </div>
                </div>
                <div class="flex items-end justify-center h-32 mt-4">
                    <div class="mx-2 flex flex-col items-center">
                        <div class="w-10 bg-blue-500"
                            :style="{ height: (parseFloat(useGetGenderAverage('male', props.reportData)) / maxGenderAverage * 100) + '%' }">
                        </div>
                        <div class="mt-2 text-sm">Male</div>
                    </div>
                    <div class="mx-2 flex flex-col items-center">
                        <div class="w-10 bg-pink-500"
                            :style="{ height: (parseFloat(useGetGenderAverage('female', props.reportData)) / maxGenderAverage * 100) + '%' }">
                        </div>
                        <div class="mt-2 text-sm">Female</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>