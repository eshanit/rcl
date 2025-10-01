<script setup lang="ts">
import StandardTable from '../tables/StandardTable.vue';

type SeverityLevel = '1-7 days' | '8-30 days' | '31-89 days' | '90+ days';

const props = defineProps<{
    reportData: {
        breakdown: any;
        total: number;
        overall_missed_rate: number;
        severity_counts: Record<SeverityLevel, number> & { total_missed: number };
        severity_rates: Record<SeverityLevel, number>;
    };
}>();

function getSeverityRate(level: string): number | undefined {
    // Type guard to ensure level is a valid SeverityLevel
    if (['1-7 days', '8-30 days', '31-89 days', '90+ days'].includes(level)) {
        return props.reportData.severity_rates[level as SeverityLevel];
    }
    return undefined;
}
</script>
<template>
    <h4 class="text-md font-medium mb-3">Missed Visit Rates by Severity</h4>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
            <p class="text-sm text-blue-800">Total Appointments</p>
            <p class="text-2xl font-bold">{{ reportData.total}}</p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
            <p class="text-sm text-purple-800">Overall Missed Rate</p>
            <p class="text-2xl font-bold">{{ reportData.overall_missed_rate }}%</p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-sm text-green-800">Adherent Appointments</p>
            <p class="text-2xl font-bold">
                {{ reportData.total - reportData.severity_counts.total_missed }}
            </p>
        </div>
    </div>

    <!-- Severity Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div v-for="([level, count], idx) in Object.entries(reportData.severity_counts)" :key="level">
            <div v-if="level !== 'total_missed'" :class="{
                'bg-yellow-50': level === '1-7 days',
                'bg-orange-50': level === '8-30 days',
                'bg-red-50': level === '31-89 days',
                'bg-red-100': level === '90+ days'
            }" class="p-4 rounded-lg">
                <p class="text-sm">{{ level }} late</p>
                <p class="text-xl font-bold">{{ count }}</p>
                <p class="text-sm">{{ getSeverityRate(level) }}%</p>
            </div>
        </div>
    </div>

    <h5 class="text-md font-medium mb-3">Missed Visits by Age and Gender</h5>

    <StandardTable :reportData="reportData" />
</template>