<script setup>
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({
      labels: ['0-14', '15-24', '25-34', '35-44', '45+'],
      values: [12, 18, 32, 28, 10]
    })
  },
  color: {
    type: String,
    default: '#EF4444'
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

function renderChart() {
  if (!chartCanvas.value) return;
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
    data: {
      labels: props.data.labels,
      datasets: [{
        label: 'Deaths by Age Group',
        data: props.data.values,
        backgroundColor: props.color,
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
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            drawBorder: false
          },
          title: {
            display: true,
            text: 'Number of Deaths'
          }
        },
        x: {
          grid: {
            display: false
          },
          title: {
            display: true,
            text: 'Age Group'
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
