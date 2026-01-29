<script setup>
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  distribution: Object
});

const chartCanvas = ref(null);
let chartInstance = null;

const colors = [
  'rgba(59, 130, 246, 0.5)',   // Blue
  'rgba(236, 72, 153, 0.5)',   // Pink
  'rgba(168, 85, 247, 0.5)',   // Purple
  'rgba(249, 115, 22, 0.5)',   // Orange
  'rgba(34, 197, 94, 0.5)'     // Green
];

const borderColors = [
  'rgba(59, 130, 246, 1)',
  'rgba(236, 72, 153, 1)',
  'rgba(168, 85, 247, 1)',
  'rgba(249, 115, 22, 1)',
  'rgba(34, 197, 94, 1)'
];

onMounted(() => {
  renderChart();
});

watch(() => props.distribution, () => {
  if (chartInstance) {
    chartInstance.destroy();
  }
  renderChart();
});

function renderChart() {
  if (!chartCanvas.value || !props.distribution) return;
  
  // Format data for chart
  const labels = Object.keys(props.distribution).sort();
  const values = labels.map(label => props.distribution[label]);

  chartInstance = new Chart(chartCanvas.value, {
    type: 'pie',
    data: {
      labels: labels.map(label => `Age ${label} years`),
      datasets: [{
        data: values,
        backgroundColor: colors.slice(0, labels.length),
        borderColor: borderColors.slice(0, labels.length),
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            padding: 15,
            font: {
              size: 12
            }
          }
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((context.parsed / total) * 100).toFixed(1);
              return `${context.label}: ${context.parsed} (${percentage}%)`;
            }
          }
        }
      }
    }
  });
}
</script>

<template>
  <div v-if="distribution" class="w-full h-full">
    <canvas ref="chartCanvas"></canvas>
  </div>
  <div v-else class="flex items-center justify-center h-full text-gray-500">
    No age distribution data available
  </div>
</template>

<style scoped>
canvas {
  max-height: 100%;
}
</style>
