<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ChartPieIcon,
    FolderIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    MagnifyingGlassIcon,
    ClockIcon,
    DocumentArrowDownIcon
} from '@heroicons/vue/24/outline';
import { Card } from '@/components/ui/card';

const page = usePage();
const auth = computed(() => page.props.auth);

const props = defineProps<{
    title: string,
    description: string,
    breadcrumbs: any[]
}>();

// Set default values if not provided
const title = ref(props.title || "Analytics Dashboard");
const description = ref(props.description || "Choose your analysis approach to gain insights into the ART program performance");
const breadcrumbs = ref(props.breadcrumbs || [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Analytics', href: null }
]);

// Navigation function
const navigateTo = (type: string) => {

};
</script>
<template>
    <AppLayout :title="title" :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 overflow-x-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">{{ props.title }}</h1>
                    <p class="text-gray-600 mt-1">{{ props.description }}</p>
                </div>
                <div class="flex items-center gap-3 bg-white p-3 rounded-lg shadow-sm">
                    <div class="bg-blue-100 w-10 h-10 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-md text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Logged In</p>
                        <p class="font-medium">{{ auth.user.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Analysis Options -->
            <div class="grid auto-rows-min gap-6 md:grid-cols-2">
                <!-- Indicator Analysis Card -->
                <Link :href="route('reports.indicators.index')">
                <Card class="relative rounded-2xl shadow-lg p-8 border border-gray-100 cursor-pointer bg-gradient-to-br from-blue-50 to-white hover:from-blue-100 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-xl"
                    style="overflow: hidden;">
                    <div class="relative z-10">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-6 bg-blue-100">
                            <ChartPieIcon class="h-10 w-10 text-blue-600" />
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">Indicator Analysis</h2>
                        <p class="text-gray-600 mb-6">
                            Focused metrics tracking specific program performance indicators like retention rates, viral
                            suppression, and TB screening coverage.
                        </p>
                        <div class="features">
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li class="flex items-start">
                                    <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                    <span>Single metric focus</span>
                                </li>
                                <li class="flex items-start">
                                    <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                    <span>Time-based comparisons</span>
                                </li>
                                <li class="flex items-start">
                                    <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                    <span>Facility-level performance</span>
                                </li>
                            </ul>
                        </div>
                        <button
                            class="text-white px-6 py-3 rounded-xl flex items-center justify-center w-full transition-colors duration-200 font-medium bg-blue-600 hover:bg-blue-700 mt-6">
                            Explore Indicators
                            <ArrowRightIcon class="h-5 w-5 ml-2" />
                        </button>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent to-white opacity-30 z-0"></div>
                </Card>
                </Link>
                <!-- Grouped Analysis Card -->
                <Link :href="route('reports.grouped')">
                    <Card class="relative rounded-2xl shadow-lg p-8 border border-gray-100 cursor-pointer bg-gradient-to-br from-purple-50 to-white hover:from-purple-100 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-xl"
                        style="overflow: hidden;">
                        <div class="relative z-10">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-6 bg-purple-100">
                                <FolderIcon class="h-10 w-10 text-purple-600" />
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-3">Grouped Analysis</h2>
                            <p class="text-gray-600 mb-6">
                                Comprehensive analysis combining multiple indicators to understand patterns across
                                demographics, retention, viral load, and long-term outcomes.
                            </p>
                            <div class="features">
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li class="flex items-start">
                                        <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                        <span>Cross-indicator insights</span>
                                    </li>
                                    <li class="flex items-start">
                                        <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                        <span>Demographic patterns</span>
                                    </li>
                                    <li class="flex items-start">
                                        <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                        <span>Longitudinal trends</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                class="text-white px-6 py-3 rounded-xl flex items-center justify-center w-full transition-colors duration-200 font-medium bg-purple-600 hover:bg-purple-700 mt-6">
                                Explore Grouped Analysis
                                <ArrowRightIcon class="h-5 w-5 ml-2" />
                            </button>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent to-white opacity-30 z-0"></div>
                    </Card>
                </Link>
            </div>

            <!-- Divider with OR -->
            <div class="relative my-12 max-w-4xl mx-auto">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-white px-4 text-sm text-gray-500">OR</span>
                </div>
            </div>

            <!-- Quick Access -->
            <div class="max-w-4xl mx-auto">
                <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">Quick Access Tools</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        class="bg-white p-6 rounded-xl border border-gray-200 text-center transition-all hover:shadow-md hover:border-blue-200">
                        <MagnifyingGlassIcon class="h-8 w-8 text-blue-500 mb-3 mx-auto" />
                        <h3 class="font-medium text-gray-800 mb-2">Custom Reports</h3>
                        <p class="text-sm text-gray-600">Create tailored reports with specific parameters</p>
                    </div>
                    <div
                        class="bg-white p-6 rounded-xl border border-gray-200 text-center transition-all hover:shadow-md hover:border-blue-200">
                        <ClockIcon class="h-8 w-8 text-purple-500 mb-3 mx-auto" />
                        <h3 class="font-medium text-gray-800 mb-2">Historical Trends</h3>
                        <p class="text-sm text-gray-600">Analyze program performance over time</p>
                    </div>
                    <div
                        class="bg-white p-6 rounded-xl border border-gray-200 text-center transition-all hover:shadow-md hover:border-blue-200">
                        <DocumentArrowDownIcon class="h-8 w-8 text-green-500 mb-3 mx-auto" />
                        <h3 class="font-medium text-gray-800 mb-2">Export Data</h3>
                        <p class="text-sm text-gray-600">Download datasets for external analysis</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animation for the cards */
.analysis-card {
    transition: all 0.3s ease;
    position: relative;
}

.analysis-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, transparent, white);
    opacity: 0.3;
    z-index: 0;
}

.analysis-card>* {
    position: relative;
    z-index: 10;
}

/* Hover animation */
.analysis-card:hover {
    transform: scale(1.02);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Button transition */
.btn-primary {
    transition: background-color 0.2s ease;
}

/* Tool card hover effect */
.tool-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-color: #bfdbfe;
}
</style>