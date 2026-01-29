<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ChartPieIcon,
    FolderIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    MagnifyingGlassIcon,
    ClockIcon,
    DocumentArrowDownIcon,
    CpuChipIcon,
    ChartBarIcon,
    UsersIcon
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

// Recent reports data
const recentReports = ref([
    { id: 1, title: 'Q4 Retention Report', type: 'Indicator', date: '2 hours ago', status: 'completed' },
    { id: 2, title: 'Viral Load Analysis', type: 'Grouped', date: 'Yesterday', status: 'completed' },
    { id: 3, title: 'Site Performance Review', type: 'Indicator', date: 'Dec 15', status: 'pending' }
]);
</script>

<template>
    <AppLayout :title="title" :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50/30 p-4 md:p-6 lg:p-8">
            <!-- Header Section -->
            <div class="mb-8 md:mb-12">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="max-w-3xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                <ChartBarIcon class="h-6 w-6 text-white" />
                            </div>
                            <div class="text-sm font-medium text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
                                ANALYTICS CENTER
                            </div>
                        </div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                            Advanced <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                Program Analytics
                            </span>
                        </h1>
                        <p class="text-lg text-gray-600 mb-6">
                            Choose your analysis approach to gain deep insights into ART program performance, retention rates, and patient outcomes.
                        </p>
                        
                        <!-- Quick Stats -->
                        <div class="flex flex-wrap gap-4 md:gap-6">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                <span class="text-sm text-gray-600">Live data streaming</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                <span class="text-sm text-gray-600">Updated 5 min ago</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                                <span class="text-sm text-gray-600">Data-powered insights</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile Card -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-5 shadow-sm border border-gray-200/50 
                              hover:shadow-md transition-shadow duration-300 w-full lg:w-80">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 
                                         flex items-center justify-center shadow-md">
                                    <UsersIcon class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Analyst</p>
                                <p class="font-semibold text-gray-800 truncate">{{ auth.user.name }}</p>
                                <p class="text-xs text-gray-500 mt-1">Last analysis: Today</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Reports Generated</span>
                                <span class="font-semibold text-blue-600">42</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Analysis Options - Modern Cards -->
            <div class="mb-10 md:mb-14">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
                    <!-- Indicator Analysis -->
                    <Link :href="route('reports.indicators.index')" class="block group">
                        <Card class="relative h-full min-h-[480px] bg-gradient-to-br from-white to-blue-50/50 
                                   rounded-3xl border border-blue-100/50 overflow-hidden
                                   transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 cursor-pointer">
                            <!-- Animated background elements -->
                            <div class="absolute inset-0 bg-grid-blue-100/30"></div>
                            <div class="absolute top-0 right-0 w-64 h-64 -mr-32 -mt-32 rounded-full bg-blue-200/10"></div>
                            
                            <div class="relative h-full p-8 flex flex-col">
                                <!-- Header -->
                                <div class="flex items-start justify-between mb-8">
                                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 
                                              flex items-center justify-center shadow-lg group-hover:scale-110 
                                              transition-transform duration-500">
                                        <ChartPieIcon class="h-8 w-8 text-white" />
                                    </div>
                                    <div class="text-xs font-medium text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full">
                                        RECOMMENDED
                                    </div>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-1">
                                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                                        Indicator Analysis
                                    </h2>
                                    <p class="text-gray-600 mb-8 text-lg">
                                        Focused metrics tracking specific program performance indicators like retention rates, 
                                        viral suppression, and TB screening coverage with precision.
                                    </p>
                                    
                                    <!-- Features -->
                                    <div class="space-y-4 mb-10">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                                                <CheckCircleIcon class="h-5 w-5 text-green-600" />
                                            </div>
                                            <span class="text-gray-700 font-medium">Single metric focus</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                                <CheckCircleIcon class="h-5 w-5 text-blue-600" />
                                            </div>
                                            <span class="text-gray-700 font-medium">Time-based comparisons</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                                                <CheckCircleIcon class="h-5 w-5 text-purple-600" />
                                            </div>
                                            <span class="text-gray-700 font-medium">Facility-level performance</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Button -->
                                <div class="mt-auto">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-500">
                                            <span class="font-medium text-gray-700">12K+</span> analyses this month
                                        </div>
                                        <div class="flex items-center gap-2 text-blue-600 font-semibold group-hover:gap-3 
                                                 transition-all duration-300">
                                            <span>Explore Indicators</span>
                                            <ArrowRightIcon class="h-5 w-5" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hover effect -->
                            <div class="absolute inset-0 border-2 border-transparent group-hover:border-blue-200/50 
                                      rounded-3xl transition-all duration-500"></div>
                        </Card>
                    </Link>

                    <!-- Grouped Analysis -->
                    <Link :href="route('reports.grouped')" class="block group">
                        <Card class="relative h-full min-h-[480px] bg-gradient-to-br from-white to-purple-50/50 
                                   rounded-3xl border border-purple-100/50 overflow-hidden
                                   transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 cursor-pointer">
                            <!-- Animated background elements -->
                            <div class="absolute inset-0 bg-grid-purple-100/30"></div>
                            <div class="absolute top-0 right-0 w-64 h-64 -mr-32 -mt-32 rounded-full bg-purple-200/10"></div>
                            
                            <div class="relative h-full p-8 flex flex-col">
                                <!-- Header -->
                                <div class="flex items-start justify-between mb-8">
                                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 
                                              flex items-center justify-center shadow-lg group-hover:scale-110 
                                              transition-transform duration-500">
                                        <FolderIcon class="h-8 w-8 text-white" />
                                    </div>
                                    <div class="text-xs font-medium text-purple-600 bg-purple-50 px-3 py-1.5 rounded-full">
                                        ADVANCED
                                    </div>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-1">
                                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                                        Grouped Analysis
                                    </h2>
                                    <p class="text-gray-600 mb-8 text-lg">
                                        Comprehensive analysis combining multiple indicators to understand patterns 
                                        across demographics, retention, viral load, and long-term outcomes.
                                    </p>
                                    
                                    <!-- Features -->
                                    <div class="space-y-4 mb-10">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                                                <CheckCircleIcon class="h-5 w-5 text-green-600" />
                                            </div>
                                            <span class="text-gray-700 font-medium">Cross-indicator insights</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                                <CheckCircleIcon class="h-5 w-5 text-blue-600" />
                                            </div>
                                            <span class="text-gray-700 font-medium">Demographic patterns</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                                                <CheckCircleIcon class="h-5 w-5 text-purple-600" />
                                            </div>
                                            <span class="text-gray-700 font-medium">Longitudinal trends</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Button -->
                                <div class="mt-auto">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-500">
                                            <span class="font-medium text-gray-700">8K+</span> analyses this month
                                        </div>
                                        <div class="flex items-center gap-2 text-purple-600 font-semibold group-hover:gap-3 
                                                 transition-all duration-300">
                                            <span>Explore Grouped Analysis</span>
                                            <ArrowRightIcon class="h-5 w-5" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hover effect -->
                            <div class="absolute inset-0 border-2 border-transparent group-hover:border-purple-200/50 
                                      rounded-3xl transition-all duration-500"></div>
                        </Card>
                    </Link>
                </div>
            </div>

            <!-- Divider with AI Assistant -->
            <div class="relative my-12 max-w-6xl mx-auto">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <div class="bg-white/80 backdrop-blur-sm px-6 py-3 rounded-2xl border border-gray-200/50 
                              shadow-sm flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-purple-500 
                                  flex items-center justify-center">
                            <CpuChipIcon class="h-4 w-4 text-white" />
                        </div>
                        <span class="text-sm font-medium text-gray-700">Support Available</span>
                        <button class="text-xs text-blue-600 font-medium hover:text-blue-700">
                            Ask for analysis recommendations →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Access & Recent Reports -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
                <!-- Quick Access Tools -->
                <div class="lg:col-span-2">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">Quick Access Tools</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                        <Link href="#" class="block group">
                            <Card class="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 
                                       hover:border-blue-200/70 hover:shadow-lg transition-all duration-300">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 
                                               flex items-center justify-center mb-4 group-hover:scale-110 
                                               transition-transform duration-300">
                                        <MagnifyingGlassIcon class="h-7 w-7 text-white" />
                                    </div>
                                    <h3 class="font-semibold text-gray-800 mb-2">Custom Reports</h3>
                                    <p class="text-sm text-gray-600">Create tailored reports with specific parameters</p>
                                    <div class="mt-4 text-xs text-blue-600 opacity-0 group-hover:opacity-100 
                                              transition-opacity duration-300">
                                        Configure custom metrics →
                                    </div>
                                </div>
                            </Card>
                        </Link>
                        
                        <Link href="#" class="block group">
                            <Card class="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 
                                       hover:border-purple-200/70 hover:shadow-lg transition-all duration-300">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 
                                               flex items-center justify-center mb-4 group-hover:scale-110 
                                               transition-transform duration-300">
                                        <ClockIcon class="h-7 w-7 text-white" />
                                    </div>
                                    <h3 class="font-semibold text-gray-800 mb-2">Historical Trends</h3>
                                    <p class="text-sm text-gray-600">Analyze program performance over time</p>
                                    <div class="mt-4 text-xs text-purple-600 opacity-0 group-hover:opacity-100 
                                              transition-opacity duration-300">
                                        View timeline analysis →
                                    </div>
                                </div>
                            </Card>
                        </Link>
                        
                        <Link href="#" class="block group">
                            <Card class="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 
                                       hover:border-green-200/70 hover:shadow-lg transition-all duration-300">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-green-600 
                                               flex items-center justify-center mb-4 group-hover:scale-110 
                                               transition-transform duration-300">
                                        <DocumentArrowDownIcon class="h-7 w-7 text-white" />
                                    </div>
                                    <h3 class="font-semibold text-gray-800 mb-2">Export Data</h3>
                                    <p class="text-sm text-gray-600">Download datasets for external analysis</p>
                                    <div class="mt-4 text-xs text-green-600 opacity-0 group-hover:opacity-100 
                                              transition-opacity duration-300">
                                        Multiple formats available →
                                    </div>
                                </div>
                            </Card>
                        </Link>
                    </div>
                </div>
                
                <!-- Recent Reports -->
                <div>
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">Recent Reports</h2>
                    <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                        <div class="p-1">
                            <div v-if="recentReports.length > 0" class="space-y-1">
                                <div v-for="report in recentReports" :key="report.id"
                                     class="group p-4 hover:bg-gray-50/50 transition-colors duration-200 cursor-pointer">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span :class="[
                                                    'text-xs font-medium px-2 py-1 rounded-full',
                                                    report.type === 'Indicator' 
                                                        ? 'bg-blue-50 text-blue-600' 
                                                        : 'bg-purple-50 text-purple-600'
                                                ]">
                                                    {{ report.type }}
                                                </span>
                                                <span :class="[
                                                    'text-xs px-2 py-1 rounded-full',
                                                    report.status === 'completed' 
                                                        ? 'bg-green-50 text-green-600' 
                                                        : 'bg-yellow-50 text-yellow-600'
                                                ]">
                                                    {{ report.status }}
                                                </span>
                                            </div>
                                            <h4 class="font-medium text-gray-800 truncate mb-1">{{ report.title }}</h4>
                                            <p class="text-xs text-gray-500">{{ report.date }}</p>
                                        </div>
                                        <ArrowRightIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-500 
                                                             transition-colors duration-200 ml-2 flex-shrink-0" />
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="text-center py-8">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <DocumentArrowDownIcon class="h-6 w-6 text-gray-400" />
                                </div>
                                <p class="text-gray-500">No recent reports</p>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-100 p-4 bg-gray-50/50">
                            <Link :href="route('reports.index')" 
                                  class="flex items-center justify-center gap-2 text-sm font-medium text-gray-600 
                                         hover:text-gray-800 hover:bg-white px-4 py-2 rounded-lg transition-colors duration-200">
                                View all reports
                                <ArrowRightIcon class="h-4 w-4" />
                            </Link>
                        </div>
                    </Card>
                </div>
            </div>
            
            <!-- Footer CTA -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <div class="max-w-3xl mx-auto text-center">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Need help choosing the right analysis?</h3>
                    <p class="text-gray-600 mb-6">
                        Our analytics guide can help you select the best approach for your specific needs.
                    </p>
                    <Link href="#" 
                          class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium">
                        <span>View analytics guide</span>
                        <ArrowRightIcon class="h-5 w-5" />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.bg-grid-blue-100\/30 {
    background-image: linear-gradient(to right, rgba(219, 234, 254, 0.3) 1px, transparent 1px),
                      linear-gradient(to bottom, rgba(219, 234, 254, 0.3) 1px, transparent 1px);
    background-size: 24px 24px;
}

.bg-grid-purple-100\/30 {
    background-image: linear-gradient(to right, rgba(237, 233, 254, 0.3) 1px, transparent 1px),
                      linear-gradient(to bottom, rgba(237, 233, 254, 0.3) 1px, transparent 1px);
    background-size: 24px 24px;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.group:hover .floating {
    animation: float 3s ease-in-out infinite;
}
</style>