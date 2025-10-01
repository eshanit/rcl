<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

// Define props
interface Props {
  survivalData: {
    by_gender: Record<string, number>
  }
}

const props = defineProps<Props>()

// Chart refs
const genderSurvivalChart = ref<HTMLCanvasElement>()
let chartInstance: Chart | null = null

// Gender colors and labels
const genderConfig = {
  male: {
    label: 'Male',
    color: 'rgba(54, 162, 235, 0.8)',
    borderColor: 'rgb(54, 162, 235)'
  },
  female: {
    label: 'Female', 
    color: 'rgba(255, 99, 132, 0.8)',
    borderColor: 'rgb(255, 99, 132)'
  },
  other: {
    label: 'Other',
    color: 'rgba(153, 102, 255, 0.8)',
    borderColor: 'rgb(153, 102, 255)'
  }
}

// Render the bar chart for survival by gender
const renderGenderSurvivalChart = () => {
  if (!props.survivalData?.by_gender || !genderSurvivalChart.value) return

  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }

  const ctx = genderSurvivalChart.value
  const genderData = props.survivalData.by_gender

  // Prepare data in consistent order
  const genders = ['male', 'female', 'other'] as const
  const labels = genders.map(gender => genderConfig[gender].label)
  const data = genders.map(gender => genderData[gender] || 0)
  const backgroundColors = genders.map(gender => genderConfig[gender].color)
  const borderColors = genders.map(gender => genderConfig[gender].borderColor)

  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Median Survival (months)',
          data: data,
          backgroundColor: backgroundColors,
          borderColor: borderColors,
          borderWidth: 2,
          borderRadius: 6,
          barPercentage: 0.6,
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
            text: 'Survival (months)'
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.1)'
          },
          ticks: {
            stepSize: 12 // Show ticks every 12 months (1 year)
          }
        },
        x: {
          title: {
            display: true,
            text: 'Gender'
          },
          grid: {
            display: false
          }
        }
      },
      plugins: {
        legend: {
          display: false
        },
        title: {
          display: true,
          text: 'Median Survival by Gender'
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const months = context.parsed.y
              const years = (months / 12).toFixed(1)
              return `Median survival: ${months} months (${years} years)`
            },
            afterLabel: function(context) {
              const gender = context.label.toLowerCase()
              const months = context.parsed.y
              
              if (months >= 60) return '✓ Excellent long-term survival'
              if (months >= 36) return '✓ Good survival duration'
              if (months >= 24) return '○ Moderate survival'
              return '● Needs attention'
            }
          }
        }
      },
      animation: {
        duration: 1000,
        easing: 'easeInOutQuart'
      }
    }
  })
}

// Alternative pie chart version for gender distribution
const renderGenderPieChart = () => {
  if (!props.survivalData?.by_gender || !genderSurvivalChart.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const ctx = genderSurvivalChart.value
  const genderData = props.survivalData.by_gender

  const genders = ['male', 'female', 'other'] as const
  const labels = genders.map(gender => genderConfig[gender].label)
  const data = genders.map(gender => genderData[gender] || 0)
  const backgroundColors = genders.map(gender => genderConfig[gender].color)
  const borderColors = genders.map(gender => genderConfig[gender].borderColor)

  chartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Median Survival (months)',
          data: data,
          backgroundColor: backgroundColors,
          borderColor: borderColors,
          borderWidth: 2,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const months = context.parsed
              const years = (months / 12).toFixed(1)
              return `${context.label}: ${months} months (${years} years)`
            }
          }
        }
      }
    }
  })
}

// Calculate gender distribution percentages
const calculateGenderDistribution = () => {
  if (!props.survivalData?.by_gender) return null

  const genderData = props.survivalData.by_gender
  const total = Object.values(genderData).reduce((sum, value) => sum + value, 0)
  
  if (total === 0) return null

  return {
    male: ((genderData.male || 0) / total * 100).toFixed(1),
    female: ((genderData.female || 0) / total * 100).toFixed(1),
    other: ((genderData.other || 0) / total * 100).toFixed(1)
  }
}

// Lifecycle hooks
onMounted(() => {
  nextTick(() => {
    renderGenderSurvivalChart() // Use renderGenderPieChart() for pie chart version
  })
})

// Watch for data changes
watch(() => props.survivalData, () => {
  nextTick(() => {
    renderGenderSurvivalChart() // Use renderGenderPieChart() for pie chart version
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
  <div class="bg-white p-4 rounded-lg border border-gray-200">
    <h5 class="text-md font-medium mb-4">Median Survival by Gender (months)</h5>
    <div class="h-64">
      <canvas ref="genderSurvivalChart"></canvas>
    </div>
  </div>
</template>