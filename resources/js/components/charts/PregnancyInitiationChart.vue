
<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  initiated: {
    type: Number,
    required: true,
    default: 75
  },
  colors: {
    type: Array,
    default: () => ['#ED64A6', '#FBCFE8']
  }
});

const chartCanvas = ref(null);
let chartInstance = null;

const chartData = computed(() => ({
  initiated: props.initiated,
  notInitiated: 100 - props.initiated
}));

onMounted(() => {
  renderChart();
});

watch(() => props.initiated, () => {
  if (chartInstance) {
    chartInstance.destroy();
  }
  renderChart();
});

function renderChart() {
  if (!chartCanvas.value) return;
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: {
      labels: ['Initiated ART', 'Not Initiated'],
      datasets: [{
        data: [chartData.value.initiated, chartData.value.notInitiated],
        backgroundColor: props.colors,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '75%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12,
            padding: 20,
            font: {
              size: 12
            }
          }
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `${context.label}: ${context.parsed}%`;
            }
          }
        }
      }
    }
  });
}
</script>
<template>
  <div class="relative">
    <div class="absolute inset-0 flex items-center justify-center">
      <div class="text-center">
        <div class="text-3xl font-bold">{{ initiated }}%</div>
        <div class="text-sm text-gray-500 mt-1">Initiated ART</div>
      </div>
    </div>
    <div class="chart-container">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>

<style scoped>
.chart-container {
  position: relative;
  height: 250px;
}
</style>