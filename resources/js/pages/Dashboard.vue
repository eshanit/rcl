<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const goToUpload = () => {
    //router.get('/upload');
};

const goToReports = () => {
    // router.get('/reports');
};

// Mock data for dashboard stats
const stats = ref([
    { title: 'Medical Facilities', value: 34, icon: 'clinic-medical', color: 'text-blue-500', bg: 'bg-blue-100' },
    { title: 'Diagnosis Codes', value: 43, icon: 'diagnoses', color: 'text-green-500', bg: 'bg-green-100' },
    { title: 'Data Records', value: '12,458', icon: 'database', color: 'text-purple-500', bg: 'bg-purple-100' },
    { title: 'Recent Uploads', value: '24h', icon: 'history', color: 'text-amber-500', bg: 'bg-amber-100' },
]);

// Recent activity mock data
const activities = ref([
    { title: 'Data Upload Completed', description: 'Facility and diagnosis data processed', time: '2 hours ago', icon: 'file-upload', color: 'text-blue-500' },
    { title: 'Diagnosis Report Generated', description: 'Monthly diagnosis distribution analysis', time: 'Yesterday', icon: 'chart-pie', color: 'text-green-500' },
    { title: 'System Updated', description: 'Database schema optimized for reporting', time: '2 days ago', icon: 'sync-alt', color: 'text-amber-500' },
]);
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 overflow-x-auto">
            <!-- Welcome header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Medical Data Portal</h1>
                    <p class="text-gray-600 mt-1">Upload, validate and analyze medical facility data</p>
                </div>
                <div class="flex items-center gap-3 bg-white p-3 rounded-lg shadow-sm">
                    <div class="bg-blue-100 w-10 h-10 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-md text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Welcome back</p>
                        <p class="font-medium">Dr. John Doe</p>
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
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Upload and Validate Data</h3>
                        <p class="text-gray-600 max-w-md">
                            Upload Excel data from MS Access, validate it, and process into MySQL database
                        </p>
                    </div>
                </Card>
                </Link>
                <Link :href="route('reports.index')">
                <Card class="flex flex-col items-center justify-center h-64 bg-gradient-to-br from-green-50 to-white border border-green-100 
                           transition-all duration-300 hover:shadow-lg hover:border-green-200 cursor-pointer group"
                    @click="goToReports">
                    <div class="text-center p-6">
                        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4 
                                   transition-transform duration-300 group-hover:scale-110 group-hover:bg-green-200">
                            <i class="fas fa-chart-bar text-3xl text-green-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">View Reports and Analysis</h3>
                        <p class="text-gray-600 max-w-md">
                            View detailed reports, visualizations, and analysis of medical facility and diagnosis data
                        </p>
                    </div>
                </Card>
                </Link>
            </div>

            <!-- Recent activity section -->
            <div class="mt-4">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Activity</h2>
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
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Smooth fade-in animation for page elements */
.card {
    animation: fadeIn 0.5s ease-out;
    animation-fill-mode: both;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Delay animations for each card */
.card:nth-child(1) {
    animation-delay: 0.1s;
}

.card:nth-child(2) {
    animation-delay: 0.2s;
}

.card:nth-child(3) {
    animation-delay: 0.3s;
}

.card:nth-child(4) {
    animation-delay: 0.4s;
}

/* Hover effects for action cards */
.group:hover h3 {
    color: #3b82f6;
    /* Blue for upload */
}

.grid>.card:last-child.group:hover h3 {
    color: #10b981;
    /* Green for reports */
}
</style>