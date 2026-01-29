<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({
      labels: ['6 Months', '12 Months', '24 Months', '60 Months'],
      values: [82, 85, 86, 88]
    })
  },
  colors: {
    type: Array,
    default: () => ['#9F7AEA', '#9F7AEA', '#9F7AEA', '#9F7AEA']
  }
});

const chartCanvas = ref(null);
let chartInstance = null;

onMounted(() => {
  renderChart();
});

watch(() => props.data, () => {
  if (chartInstance) {
    chartInstance.destroy();
  }
  renderChart();
}, { deep: true });

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
});

function renderChart() {
  if (!chartCanvas.value) return;
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
    data: {
      labels: props.data.labels,
      datasets: [{
        label: 'Viral Suppression Rate',
        data: props.data.values,
        backgroundColor: props.colors,
        borderRadius: 6,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `${context.parsed.y}% suppressed`;
            }
          }
        }
      },
      scales: {
        y: {
          min: 70,
          max: 100,
          ticks: {
            callback: (value) => {
              return value + '%';
            }
          },
          grid: {
            drawBorder: false
          },
          title: {
            display: true,
            text: 'Suppression Rate'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });
}
</script>
<template>
  <div class="chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<style scoped>
.chart-container {
  position: relative;
  height: 400px;
  width: 100%;
}
</style>