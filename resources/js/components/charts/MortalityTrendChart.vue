<script setup>
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({
      labels: ['2019', '2020', '2021', '2022', '2023'],
      values: [56, 48, 42, 38, 32]
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
    type: 'line',
    data: {
      labels: props.data.labels,
      datasets: [{
        label: 'Deaths',
        data: props.data.values,
        borderColor: props.color,
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        tension: 0.3,
        fill: true,
        pointBackgroundColor: props.color,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            usePointStyle: true,
            padding: 20
          }
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
            text: 'Year'
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
