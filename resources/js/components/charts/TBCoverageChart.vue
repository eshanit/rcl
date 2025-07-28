<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  coverage: {
    type: Number,
    required: true,
    default: 78
  },
  colors: {
    type: Array,
    default: () => ['#38A169', '#E5E7EB']
  }
});

const chartCanvas = ref(null);
let chartInstance = null;

const chartData = computed(() => ({
  covered: props.coverage,
  notCovered: 100 - props.coverage
}));

onMounted(() => {
  renderChart();
});

watch(() => props.coverage, () => {
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
      labels: ['Screened', 'Not Screened'],
      datasets: [{
        data: [chartData.value.covered, chartData.value.notCovered],
        backgroundColor: props.colors,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '85%',
      plugins: {
        legend: {
          display: false
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
        <div class="text-3xl font-bold">{{ coverage }}%</div>
        <div class="text-sm text-gray-500 mt-1">Coverage</div>
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
  height: 200px;
}
</style>