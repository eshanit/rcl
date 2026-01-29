<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({
      "0-14": 3003,
      "15-24": 4582,
      "25-34": 10478,
      "35-44": 9969,
      "45+": 7329
    })
  },
  color: {
    type: String,
    default: '#4F46E5'
  },
  title: {
    type: String,
    default: 'Age Distribution'
  },
  unit: {
    type: String,
    default: 'patients'
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
  
  // Extract labels and values from the data object
  const labels = Object.keys(props.data);
  const values = Object.values(props.data);
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: props.title,
        data: values,
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
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const value = context.raw;
              return `${value} ${props.unit === '%' ? '%' : 'patients'}`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            drawBorder: false
          },
          ticks: {
            callback: (value) => {
              return props.unit === '%' ? `${value}%` : value.toLocaleString();
            }
          },
          title: {
            display: true,
            text: props.unit === '%' ? 'Percentage of Patients' : 'Number of Patients'
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
  <!-- <pre>
  {{ props.data }}
  </pre> -->

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