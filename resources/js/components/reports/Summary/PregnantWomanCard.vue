<script setup lang="ts">
const props = defineProps<{
    reportData: {
        retained: number;
        total_pregnant: number;
        percentage: number;
    };
}>();
</script>
<template>
        <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">Retained</p>
                    <p class="text-2xl font-bold">{{ reportData.retained }}</p>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-800">Total Pregnant</p>
                    <p class="text-2xl font-bold">{{ reportData.total_pregnant }}</p>
                  </div>
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-purple-800">Retention Rate</p>
                    <p class="text-2xl font-bold">{{ reportData.percentage }}%</p>
                  </div>
</template>