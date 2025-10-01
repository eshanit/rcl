<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'


Chart.register(...registerables)

// Define props
interface Props {
  reportData: {
    age_distribution: Record<string, {
      male: number
      female: number
      other: number
      total: number
    }>
    statistics: {
      gender_medians: Record<string, number>
    }
  }
}

const props = defineProps<Props>()

// Chart refs
const ageChart = ref<HTMLCanvasElement>()
let chartInstance: Chart | null = null

// Format age group labels for better display
const formatAgeGroup = (ageGroup: string): string => {
  return ageGroup.replace('years', 'yrs').replace(' ', '')
}

// Render the bar chart
const renderAgeChart = () => {
  if (!props.reportData?.age_distribution || !ageChart.value) return

  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }

  const ctx = ageChart.value
  const ageGroups = Object.keys(props.reportData.age_distribution)
  const maleData = ageGroups.map(group => props.reportData.age_distribution[group].male)
  const femaleData = ageGroups.map(group => props.reportData.age_distribution[group].female)
  const otherData = ageGroups.map(group => props.reportData.age_distribution[group].other)

  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ageGroups.map(formatAgeGroup),
      datasets: [
        {
          label: 'Male',
          data: maleData,
          backgroundColor: 'rgba(54, 162, 235, 0.8)',
          borderColor: 'rgb(54, 162, 235)',
          borderWidth: 1
        },
        {
          label: 'Female',
          data: femaleData,
          backgroundColor: 'rgba(255, 99, 132, 0.8)',
          borderColor: 'rgb(255, 99, 132)',
          borderWidth: 1
        },
        {
          label: 'Other',
          data: otherData,
          backgroundColor: 'rgba(153, 102, 255, 0.8)',
          borderColor: 'rgb(153, 102, 255)',
          borderWidth: 1
        }
      ]
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
          },
          ticks: {
            stepSize: 1
          }
        },
        x: {
          title: {
            display: true,
            text: 'Age Groups'
          }
        }
      },
      plugins: {
        legend: {
          position: 'top' as const,
        },
        title: {
          display: true,
          text: 'Age Distribution at Death'
        },
        tooltip: {
          mode: 'index' as const,
          intersect: false
        }
      }
    }
  })
}

// Lifecycle hooks
onMounted(() => {
  nextTick(() => {
    renderAgeChart()
  })
})

// Watch for data changes
watch(() => props.reportData, () => {
  nextTick(() => {
    renderAgeChart()
  })
}, { deep: true })

// Cleanup on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>
<template>
  <div>
    <canvas ref="ageChart"></canvas>
  </div>
</template>
<style scoped>
  /* .chart-container {
    position: relative;
    width: 400px;
    height: 400px;
  } */
</style>