<script setup lang="ts">
import ViralSuppressionTable from '../tables/ViralSuppressionTable.vue';

const props = defineProps<{
    reportData: {
        suppressed: number;
        with_vl_test: number;
        suppression_rate: number;
        vl_coverage: number;
        eligible_patients: number;
        breakdown: any;
    };
}>();
</script>
<template>
    <h4 class="text-md font-medium mb-3">Viral Suppression at 24 Months</h4>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-sm text-green-800">Suppressed Patients</p>
            <p class="text-2xl font-bold">{{ reportData.suppressed }}</p>
        </div>
        <div class="bg-blue-50 p-4 rounded-lg">
            <p class="text-sm text-blue-800">Patients with VL Test</p>
            <p class="text-2xl font-bold">{{ reportData.with_vl_test }}</p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
            <p class="text-sm text-purple-800">Suppression Rate</p>
            <p class="text-2xl font-bold">{{ reportData.suppression_rate }}%</p>
        </div>
        <div class="bg-teal-50 p-4 rounded-lg">
            <p class="text-sm text-teal-800">VL Test Coverage</p>
            <p class="text-2xl font-bold">{{ reportData.vl_coverage }}%</p>
        </div>
    </div>

    <!-- Key metrics card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg border border-gray-200">
            <h5 class="font-medium mb-2">Measurement Window</h5>
            <p class="text-sm">23-25 months after ART initiation</p>
            <p class="text-sm">Closest test to 24 months used</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200">
            <h5 class="font-medium mb-2">Eligibility Criteria</h5>
            <p class="text-sm">Patients initiated ART at least 13 months ago</p>
            <p class="text-sm">Total eligible: {{ reportData.eligible_patients }}</p>
        </div>
    </div>

    <!-- Viral Load Breakdown Table -->
    <h5 class="text-md font-medium mb-3">Suppressed Patients by Age and Gender</h5>
    <ViralSuppressionTable :reportData="reportData" />

    <div class="mt-4 bg-blue-50 p-3 rounded-lg border border-blue-200">
        <p class="text-sm text-blue-700">
            Viral suppression is defined as &lt;1000 copies/mL. This metric assesses treatment effectiveness
            6 months after ART initiation. VL test coverage shows the percentage of eligible patients
            who received a viral load test within the 23-25 month window.
        </p>
    </div>
</template>