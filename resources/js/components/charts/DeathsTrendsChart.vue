<script setup>
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  trends: Object
});

const chartCanvas = ref(null);
let chartInstance = null;

onMounted(() => {
  renderChart();
});

watch(() => props.trends, () => {
  if (chartInstance) {
    chartInstance.destroy();
  }
  renderChart();
});

function renderChart() {
  if (!chartCanvas.value || !props.trends) return;
  
  // Format data for chart
  const labels = Object.keys(props.trends).sort();
  const values = labels.map(label => props.trends[label]);

  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Deaths',
        data: values,
        backgroundColor: 'rgba(239, 68, 68, 0.5)',
        borderColor: 'rgba(239, 68, 68, 1)',
        borderWidth: 2,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `Deaths: ${context.parsed.y}`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => {
              return Math.floor(value);
            }
          }
        }
      }
    }
  });
}
</script>

<template>
  <div v-if="trends" class="w-full h-full">
    <canvas ref="chartCanvas"></canvas>
  </div>
  <div v-else class="flex items-center justify-center h-full text-gray-500">
    No deaths data available
  </div>
</template>

<style scoped>
canvas {
  max-height: 100%;
}
</style>
