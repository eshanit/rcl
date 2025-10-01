<script setup lang="ts">
import StandardTable from '../tables/StandardTable.vue';

const props = defineProps<{
    reportData: any
}>();

</script>
<template>
<!-- Breakdown Table -->
    <StandardTable :reportData="props.reportData" />
</template>