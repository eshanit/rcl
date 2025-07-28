<script setup>
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({
      labels: ['0-14', '15-24', '25-34', '35-44', '45+'],
      values: [18, 25, 30, 15, 12]
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
    default: '%'
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
        label: props.title,
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
          ticks: {
            callback: (value) => {
              return props.unit === '%' ? `${value}%` : value;
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