<script setup>
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({
      retention12: 85,
      retention24: 92
    })
  },
  colors: {
    type: Array,
    default: () => ['#ED64A6', '#9F7AEA']
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
      labels: ['12 Months', '24 Months'],
      datasets: [{
        label: 'Retention Rate',
        data: [props.data.retention12, props.data.retention24],
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
              return `${context.parsed.y}% retention`;
            }
          }
        }
      },
      scales: {
        y: {
          min: 50,
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
            text: 'Retention Rate'
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
