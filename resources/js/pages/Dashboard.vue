<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type StatItem, type AdditionalStats, type ActivityItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { Card } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const page = usePage();
const auth = computed(() => page.props.auth);
const rawStats = Array.isArray((page.props as any).stats) ? (page.props as any).stats : [];
const stats = ref<StatItem[]>(rawStats.slice(0, 4));
const additionalStats = ref<AdditionalStats>(rawStats.additional_stats || {});
const activities = ref<ActivityItem[]>(Array.isArray((page.props as any).activities) ? (page.props as any).activities : []);

// Refresh stats
const refreshStats = async () => {
    try {
        const response = await fetch('/dashboard/stats');
        const data = await response.json();
        const rawStats = Array.isArray(data.stats) ? data.stats : [];
        stats.value = rawStats.slice(0, 4);
        additionalStats.value = rawStats.additional_stats || {};
        activities.value = Array.isArray(data.activities) ? data.activities : [];
    } catch (error) {
        console.error('Failed to refresh stats:', error);
    }
};

onMounted(() => {
    // Optional: Auto-refresh every 5 minutes
    // setInterval(refreshStats, 300000);
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-white p-4 md:p-6 lg:p-8">
            <!-- Header Section -->
            <div class="mb-8 md:mb-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Rucola SMART Dashboard
                        </h1>
                        <p class="text-gray-600 mt-2 md:mt-3 text-sm md:text-base">
                            Monitor ART program performance and patient care in real-time
                        </p>
                    </div>
                    
                    <!-- User Profile Card -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 md:p-5 shadow-sm border border-gray-200/50 
                              hover:shadow-md transition-shadow duration-300 w-full lg:w-auto">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 
                                         flex items-center justify-center shadow-md">
                                    <i class="fas fa-user-md text-white text-lg md:text-xl"></i>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 rounded-full border-2 border-white"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Welcome back</p>
                                <p class="font-semibold text-gray-800 truncate text-sm md:text-base">{{ auth.user.name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">Last active: Today</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid - Modern Cards -->
            <div class="mb-8 md:mb-12">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <Card v-for="(stat, index) in stats" :key="index"
                        class="group relative bg-white/90 backdrop-blur-sm rounded-2xl p-5 md:p-6 
                               border border-gray-200/50 hover:border-blue-200/50
                               transition-all duration-300 hover:shadow-xl hover:-translate-y-1 
                               overflow-hidden">
                        <!-- Background accent -->
                        <div class="absolute top-0 right-0 w-20 h-20 -mr-6 -mt-6 rounded-full opacity-10 group-hover:opacity-20 
                                  transition-opacity duration-300"
                            :class="stat.bg.replace('bg-', 'bg-')"></div>
                        
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-gray-600 text-sm font-medium mb-1">{{ stat.title }}</p>
                                <p class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">{{ stat.value }}</p>
                                <div class="flex items-center text-xs text-gray-500">
                                    <i class="fas fa-trend-up text-green-500 mr-1"></i>
                                    <span>Updated just now</span>
                                </div>
                            </div>
                            <div :class="[stat.bg, 'w-12 h-12 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow duration-300']">
                                <i :class="['fas', 'fa-' + stat.icon, stat.color, 'text-lg']"></i>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- ART Program Summary - Enhanced -->
            <div v-if="additionalStats" class="mb-8 md:mb-12">
                <h2 class="text-lg md:text-xl font-semibold text-gray-800 mb-4 md:mb-6">Program Overview</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                    <Card class="bg-gradient-to-br from-blue-50/80 to-white rounded-2xl p-5 md:p-6 border border-blue-100/50">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
                                <i class="fas fa-user-injured text-2xl text-blue-600"></i>
                            </div>
                            <p class="text-gray-600 text-sm font-medium mb-2">Total Patients</p>
                            <p class="text-3xl md:text-4xl font-bold text-blue-700">
                                {{ additionalStats.total_patients?.toLocaleString() }}
                            </p>
                            <div class="mt-3 pt-3 border-t border-blue-100">
                                <span class="inline-flex items-center text-sm text-blue-600">
                                    <i class="fas fa-plus-circle mr-1 text-xs"></i>
                                    +12% this month
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card class="bg-gradient-to-br from-emerald-50/80 to-white rounded-2xl p-5 md:p-6 border border-emerald-100/50">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4">
                                <i class="fas fa-shield-alt text-2xl text-emerald-600"></i>
                            </div>
                            <p class="text-gray-600 text-sm font-medium mb-2">ART Coverage</p>
                            <p class="text-3xl md:text-4xl font-bold text-emerald-700">
                                {{ additionalStats.art_coverage }}%
                            </p>
                            <div class="mt-3 pt-3 border-t border-emerald-100">
                                <span class="inline-flex items-center text-sm text-emerald-600">
                                    <i class="fas fa-trend-up mr-1 text-xs"></i>
                                    --% from last quarter
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card class="bg-gradient-to-br from-violet-50/80 to-white rounded-2xl p-5 md:p-6 border border-violet-100/50">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-100 mb-4">
                                <i class="fas fa-clinic-medical text-2xl text-violet-600"></i>
                            </div>
                            <p class="text-gray-600 text-sm font-medium mb-2">Active Sites</p>
                            <p class="text-3xl md:text-4xl font-bold text-violet-700">
                                {{ additionalStats.total_sites }}
                            </p>
                            <div class="mt-3 pt-3 border-t border-violet-100">
                                <span class="inline-flex items-center text-sm text-violet-600">
                                    <i class="fas fa-map-marker-alt mr-1 text-xs"></i>
                                    8 regions covered
                                </span>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Main Action Cards - Modern Design -->
            <div class="mb-8 md:mb-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
                    <Link :href="route('upload.show')" class="block">
                        <Card class="group relative h-full min-h-[280px] bg-gradient-to-br from-blue-500 to-blue-600 
                                   rounded-2xl overflow-hidden hover:shadow-2xl transition-all duration-500 
                                   hover:-translate-y-1 cursor-pointer">
                            <div class="absolute inset-0 bg-grid-white/10"></div>
                            <div class="relative h-full flex flex-col justify-center items-center p-8 text-center">
                                <div class="mb-6 transform transition-transform duration-500 group-hover:scale-110">
                                    <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center 
                                               border border-white/30 shadow-lg">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-white"></i>
                                    </div>
                                </div>
                                <h3 class="text-2xl font-bold text-white mb-3">Upload Patient Data</h3>
                                <p class="text-blue-100/90 max-w-md">
                                    Upload clinical data, ART initiations, and patient visits for analysis
                                </p>
                                <div class="mt-6 flex items-center text-white/80">
                                    <span class="text-sm font-medium">Get Started</span>
                                    <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-2 transition-transform duration-300"></i>
                                </div>
                            </div>
                        </Card>
                    </Link>

                    <Link :href="route('reports.index')" class="block">
                        <Card class="group relative h-full min-h-[280px] bg-gradient-to-br from-emerald-500 to-emerald-600 
                                   rounded-2xl overflow-hidden hover:shadow-2xl transition-all duration-500 
                                   hover:-translate-y-1 cursor-pointer">
                            <div class="absolute inset-0 bg-grid-white/10"></div>
                            <div class="relative h-full flex flex-col justify-center items-center p-8 text-center">
                                <div class="mb-6 transform transition-transform duration-500 group-hover:scale-110">
                                    <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center 
                                               border border-white/30 shadow-lg">
                                        <i class="fas fa-chart-line text-3xl text-white"></i>
                                    </div>
                                </div>
                                <h3 class="text-2xl font-bold text-white mb-3">Program Analytics</h3>
                                <p class="text-emerald-100/90 max-w-md">
                                    Analyze ART retention, viral suppression, and program performance metrics
                                </p>
                                <div class="mt-6 flex items-center text-white/80">
                                    <span class="text-sm font-medium">View Reports</span>
                                    <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-2 transition-transform duration-300"></i>
                                </div>
                            </div>
                        </Card>
                    </Link>
                </div>
            </div>

            <!-- Recent Activity Section - Modern Timeline -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800">Recent Program Activity</h2>
                    <button @click="refreshStats" 
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-2 
                                   px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors duration-200">
                        <i class="fas fa-sync-alt text-xs"></i>
                        Refresh
                    </button>
                </div>
                
                <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                    <div class="p-1">
                        <div v-if="activities.length > 0" class="divide-y divide-gray-100">
                            <div v-for="(activity, index) in activities" :key="index"
                                class="group p-4 md:p-5 hover:bg-gray-50/50 transition-colors duration-200">
                                <div class="flex items-start gap-4">
                                    <!-- Timeline indicator -->
                                    <div class="relative flex-shrink-0">
                                        <div :class="[activity.color, 'w-10 h-10 rounded-xl flex items-center justify-center']">
                                            <i :class="['fas', 'fa-' + activity.icon, 'text-white']"></i>
                                        </div>
                                        <div v-if="index !== activities.length - 1" 
                                             class="absolute top-full left-1/2 w-0.5 h-4 bg-gray-200 transform -translate-x-1/2"></div>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-2">
                                            <h3 class="font-semibold text-gray-800">{{ activity.title }}</h3>
                                            <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                                {{ activity.time }}
                                            </span>
                                        </div>
                                        <p class="text-gray-600 text-sm md:text-base">{{ activity.description }}</p>
                                    </div>
                                    
                                    <div class="hidden md:block opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <i class="fas fa-chevron-right text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-gray-500 font-medium">No recent program activity</p>
                            <p class="text-gray-400 text-sm mt-1">Activity will appear here as it happens</p>
                        </div>
                    </div>
                    
                    <!-- View all button -->
                    <!-- <div v-if="activities.length > 0" 
                         class="border-t border-gray-100 p-4 bg-gray-50/50">
                        <Link :href="route('activities.index')" 
                              class="flex items-center justify-center gap-2 text-sm font-medium text-gray-600 
                                     hover:text-gray-800 hover:bg-white px-4 py-2 rounded-lg transition-colors duration-200">
                            View all activity
                            <i class="fas fa-external-link-alt text-xs"></i>
                        </Link>
                    </div> -->
                </Card>
            </div>

            <!-- Quick Stats Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200/50">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Data Updated</p>
                        <p class="text-sm font-medium text-gray-700">Just now</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">System Status</p>
                        <p class="text-sm font-medium text-emerald-600 flex items-center justify-center gap-1">
                            <i class="fas fa-circle text-xs"></i>
                            Operational
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Latency</p>
                        <p class="text-sm font-medium text-gray-700">~50ms</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">API Version</p>
                        <p class="text-sm font-medium text-gray-700">v2.4.1</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.bg-grid-white\/10 {
    background-image: linear-gradient(to right, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                      linear-gradient(to bottom, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.group:hover .shimmer-effect {
    animation: shimmer 2s infinite;
}
</style>