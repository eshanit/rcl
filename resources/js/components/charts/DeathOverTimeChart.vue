<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'
import type { IReportData } from '@/types/reports'

Chart.register(...registerables)

// Define props
interface Props {
  reportData?: IReportData | null
}

const props = withDefaults(defineProps<Props>(), {
  reportData: null
})

// Refs
const deathsChart = ref<HTMLCanvasElement>()
const chartInstance = ref<Chart>()

// Format date function
const formatDate = (dateString: string | null): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString()
}

// Render chart function
const renderChart = () => {
  if (!props.reportData || !props.reportData.chart_data) return
  
  const ctx = deathsChart.value
  if (!ctx) return
  
  // Destroy existing chart if it exists
  if (chartInstance.value) {
    chartInstance.value.destroy()
  }
  
  chartInstance.value = new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.reportData.chart_data.labels,
      datasets: props.reportData.chart_data.datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Number of Deaths'
          }
        },
        x: {
          title: {
            display: true,
            text: 'Month'
          }
        }
      },
      plugins: {
        title: {
          display: true,
          text: 'Deaths Trend Over Time'
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      }
    }
  })
}

// Lifecycle hooks
onMounted(() => {
  renderChart()
})

// Watch for reportData changes
watch(() => props.reportData, () => {
  nextTick(() => {
    renderChart()
  })
}, { deep: true })
</script>
<template>
   <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
      <h5 class="text-md font-medium mb-4">Deaths Trend by Gender</h5>
      <div class="h-96">
        <canvas ref="deathsChart"></canvas>
      </div>
    </div>
</template>