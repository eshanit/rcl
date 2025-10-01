<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
const stats = ref(page.props.stats || []);
const activities = ref(page.props.activities || []);

// Refresh stats
const refreshStats = async () => {
    try {
        const response = await fetch('/dashboard/stats');
        const data = await response.json();
        stats.value = data.stats;
        activities.value = data.activities;
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
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 overflow-x-auto">
            <!-- Welcome header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Rucola (SMART) Dashboard</h1>
                    <p class="text-gray-600 mt-1">Monitor ART program performance and patient care</p>
                </div>
                <div class="flex items-center gap-3 bg-white p-3 rounded-lg shadow-sm">
                    <div class="bg-blue-100 w-10 h-10 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-md text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Welcome back</p>
                        <p class="font-medium">{{ auth.user.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card v-for="(stat, index) in stats" :key="index"
                    class="p-4 transition-all duration-300 hover:shadow-md border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div :class="[stat.bg, 'w-12 h-12 rounded-full flex items-center justify-center']">
                            <i :class="['fas', 'fa-' + stat.icon, stat.color, 'text-lg']"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">{{ stat.title }}</p>
                            <p class="text-xl font-bold text-gray-800">{{ stat.value }}</p>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- ART Program Summary -->
            <div v-if="stats.additional_stats" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="p-4 bg-gradient-to-r from-blue-50 to-white">
                    <div class="text-center">
                        <p class="text-gray-600 text-sm">Total Patients</p>
                        <p class="text-2xl font-bold text-blue-600">
                            {{ stats.additional_stats.total_patients?.toLocaleString() }}
                        </p>
                    </div>
                </Card>
                <Card class="p-4 bg-gradient-to-r from-green-50 to-white">
                    <div class="text-center">
                        <p class="text-gray-600 text-sm">ART Coverage</p>
                        <p class="text-2xl font-bold text-green-600">
                            {{ stats.additional_stats.art_coverage }}%
                        </p>
                    </div>
                </Card>
                <Card class="p-4 bg-gradient-to-r from-purple-50 to-white">
                    <div class="text-center">
                        <p class="text-gray-600 text-sm">Active Sites</p>
                        <p class="text-2xl font-bold text-purple-600">
                            {{ stats.additional_stats.total_sites }}
                        </p>
                    </div>
                </Card>
            </div>

            <!-- Main action cards -->
            <div class="grid auto-rows-min gap-6 md:grid-cols-2">
                <Link :href="route('upload.show')">
                <Card class="flex flex-col items-center justify-center h-64 bg-gradient-to-br from-blue-50 to-white border border-blue-100 
                           transition-all duration-300 hover:shadow-lg hover:border-blue-200 cursor-pointer group">
                    <div class="text-center p-6">
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-4 
                                   transition-transform duration-300 group-hover:scale-110 group-hover:bg-blue-200">
                            <i class="fas fa-cloud-upload-alt text-3xl text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Upload Patient Data</h3>
                        <p class="text-gray-600 max-w-md">
                            Upload clinical data, ART initiations, and patient visits for analysis
                        </p>
                    </div>
                </Card>
                </Link>
                <Link :href="route('reports.index')">
                <Card class="flex flex-col items-center justify-center h-64 bg-gradient-to-br from-green-50 to-white border border-green-100 
                           transition-all duration-300 hover:shadow-lg hover:border-green-200 cursor-pointer group">
                    <div class="text-center p-6">
                        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4 
                                   transition-transform duration-300 group-hover:scale-110 group-hover:bg-green-200">
                            <i class="fas fa-chart-line text-3xl text-green-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Program Analytics</h3>
                        <p class="text-gray-600 max-w-md">
                            Analyze ART retention, viral suppression, and program performance metrics
                        </p>
                    </div>
                </Card>
                </Link>
            </div>

            <!-- Recent activity section -->
            <div class="mt-4">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Program Activity</h2>
                <Card class="p-4 border border-gray-100">
                    <div class="space-y-4">
                        <div v-for="(activity, index) in activities" :key="index"
                            class="flex items-start gap-4 pb-4 border-b border-gray-100 last:border-b-0 last:pb-0">
                            <div :class="[activity.color, 'text-xl mt-1']">
                                <i :class="['fas', 'fa-' + activity.icon]"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-800">{{ activity.title }}</h3>
                                <p class="text-gray-600 text-sm">{{ activity.description }}</p>
                            </div>
                            <div class="text-gray-500 text-sm whitespace-nowrap">
                                {{ activity.time }}
                            </div>
                        </div>
                        <div v-if="activities.length === 0" class="text-center py-4 text-gray-500">
                            No recent program activity to display
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>