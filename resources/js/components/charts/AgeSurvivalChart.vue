<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

// Define props
interface Props {
    survivalData: {
        by_age_group: Record<string, number>
    }
}

const props = defineProps<Props>()

// Chart refs
const ageSurvivalChart = ref<HTMLCanvasElement>()
let chartInstance: Chart | null = null

// Format age group labels for better display
const formatAgeGroup = (ageGroup: string): string => {
    return ageGroup.replace('years', 'yrs').replace(' ', '')
}

// Render the bar chart for survival by age group
const renderAgeSurvivalChart = () => {
    if (!props.survivalData?.by_age_group || !ageSurvivalChart.value) return

    // Destroy existing chart
    if (chartInstance) {
        chartInstance.destroy()
    }

    const ctx = ageSurvivalChart.value
    const ageGroups = Object.keys(props.survivalData.by_age_group)
    const survivalData = ageGroups.map(group => props.survivalData.by_age_group[group])

    // Sort age groups in logical order
    const ageGroupOrder = [
        '0-17 years', '18-24 years', '25-34 years',
        '35-44 years', '45-54 years', '55-64 years', '65+ years'
    ]

    const sortedAgeGroups = ageGroups.sort((a, b) =>
        ageGroupOrder.indexOf(a) - ageGroupOrder.indexOf(b)
    )
    const sortedSurvivalData = sortedAgeGroups.map(group => props.survivalData.by_age_group[group])

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sortedAgeGroups.map(formatAgeGroup),
            datasets: [
                {
                    label: 'Median Survival (months)',
                    data: sortedSurvivalData,
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 2,
                    borderRadius: 4,
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
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Age at ART Initiation'
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
                    text: 'Median Survival by Age Group'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return `Median survival: ${context.parsed.y} months`
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

// Color coding based on survival duration
const getBarColor = (survivalMonths: number): string => {
    if (survivalMonths >= 60) return 'rgba(34, 197, 94, 0.8)' // Green for 5+ years
    if (survivalMonths >= 36) return 'rgba(234, 179, 8, 0.8)' // Yellow for 3-5 years
    if (survivalMonths >= 24) return 'rgba(249, 115, 22, 0.8)' // Orange for 2-3 years
    return 'rgba(239, 68, 68, 0.8)' // Red for less than 2 years
}

// Alternative version with color coding (uncomment to use)
const renderAgeSurvivalChartWithColors = () => {
    if (!props.survivalData?.by_age_group || !ageSurvivalChart.value) return

    if (chartInstance) {
        chartInstance.destroy()
    }

    const ctx = ageSurvivalChart.value
    const ageGroups = Object.keys(props.survivalData.by_age_group)

    // Sort age groups
    const ageGroupOrder = [
        '0-17 years', '18-24 years', '25-34 years',
        '35-44 years', '45-54 years', '55-64 years', '65+ years'
    ]

    const sortedAgeGroups = ageGroups.sort((a, b) =>
        ageGroupOrder.indexOf(a) - ageGroupOrder.indexOf(b)
    )
    const sortedSurvivalData = sortedAgeGroups.map(group => props.survivalData.by_age_group[group])

    // Create different colors based on survival duration
    const backgroundColors = sortedSurvivalData.map(months => getBarColor(months))

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sortedAgeGroups.map(formatAgeGroup),
            datasets: [
                {
                    label: 'Median Survival (months)',
                    data: sortedSurvivalData,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(color => color.replace('0.8', '1')),
                    borderWidth: 2,
                    borderRadius: 4,
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
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Age at ART Initiation'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const months = context.parsed.y
                            const years = (months / 12).toFixed(1)
                            return `Median survival: ${months} months (${years} years)`
                        }
                    }
                }
            }
        }
    })
}

// Lifecycle hooks
onMounted(() => {
    nextTick(() => {
        renderAgeSurvivalChart() // Use renderAgeSurvivalChartWithColors for colored version
    })
})

// Watch for data changes
watch(() => props.survivalData, () => {
    nextTick(() => {
        renderAgeSurvivalChart() // Use renderAgeSurvivalChartWithColors for colored version
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
        <h5 class="text-md font-medium mb-4">Median Survival by Age Group (months)</h5>
        <div class="h-64">
            <canvas ref="ageSurvivalChart"></canvas>
        </div>
    </div>
</template>