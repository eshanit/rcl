<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: {
    type: Array,
    required: true,
    default: () => []
  },
  colors: {
    type: Array,
    default: () => ['#4F46E5', '#EC4899', '#10B981']
  }
});

const chartCanvas = ref(null);
let chartInstance = null;

// Transform the data to the format expected by the chart
const chartData = computed(() => {
  if (!props.data || !Array.isArray(props.data)) {
    return {
      labels: [],
      values: [],
      total: 0
    };
  }
  
  const labels = props.data.map(item => item.gender);
  const values = props.data.map(item => item.count);
  const total = values.reduce((sum, value) => sum + value, 0);
  
  return {
    labels,
    values,
    total
  };
});

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
  
  const data = chartData.value;
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: {
      labels: data.labels,
      datasets: [{
        data: data.values,
        backgroundColor: props.colors.slice(0, data.labels.length),
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
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
              const value = context.parsed;
              const percentage = Math.round((value / data.total) * 100);
              return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
            }
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
