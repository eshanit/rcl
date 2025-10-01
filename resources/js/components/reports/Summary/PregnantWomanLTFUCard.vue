<script setup lang="ts">
const props = defineProps<{
    reportData: {
        ltfu_count: number;
        total_pregnant: number;
        percentage: number;
    };
}>();
</script>
<template>
        <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">LTFU Count</p>
                    <p class="text-2xl font-bold">{{ reportData.ltfu_count }}</p>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-800">Total Pregnant</p>
                    <p class="text-2xl font-bold">{{ reportData.total_pregnant }}</p>
                  </div>
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">Mortality Percentage</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>
</template>