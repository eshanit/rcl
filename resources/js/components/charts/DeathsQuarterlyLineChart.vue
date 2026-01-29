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
  const labels = Object.keys(props.trends);
  const values = labels.map(label => props.trends[label]);

  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Deaths',
          data: values,
          borderColor: '#6d28d9',
          backgroundColor: 'rgba(109, 40, 217, 0.1)',
          borderWidth: 3,
          pointRadius: 6,
          pointBackgroundColor: '#6d28d9',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointHoverRadius: 8,
          tension: 0.4,
          fill: true,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          labels: {
            color: '#4b5563',
            font: {
              size: 12,
              weight: '500'
            }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          titleFont: {
            size: 14,
            weight: 'bold'
          },
          bodyFont: {
            size: 13
          },
          callbacks: {
            label: function(context) {
              return `Deaths: ${context.parsed.y}`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            color: '#6b7280',
            font: {
              size: 12
            }
          }
        },
        x: {
          grid: {
            display: false,
            drawBorder: false
          },
          ticks: {
            color: '#6b7280',
            font: {
              size: 12
            }
          }
        }
      }
    }
  });
}
</script>

<template>
  <canvas ref="chartCanvas"></canvas>
</template>
