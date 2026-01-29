<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ICohort, IFacility, IIndicator, IReportData, ISite } from '@/types/reports';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import {
    ChartBarIcon,
    FunnelIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    UserGroupIcon,
    ArrowPathIcon,
    ChevronRightIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import TimeToFirstVL from '@/components/reports/TimeToFirstVL.vue';
import ARTPatientsDeath from '@/components/reports/ARTPatientsDeath.vue';
import MissedAppointmentsSummaryCard from '@/components/reports/Summary/MissedAppointmentsCard.vue';
import PregnantWomanSummaryCard from '@/components/reports/Summary/PregnantWomanCard.vue';
import PregnantWomanDiedSummaryCard from '@/components/reports/Summary/PregnantWomanDiedCard.vue';
import PregnantWomanLTFUSummaryCard from '@/components/reports/Summary/PregnantWomanLTFUCard.vue';
import RetainedSummaryCard from '@/components/reports/Summary/RetainedCard.vue';
import LTFandReengaged from '@/components/reports/LTFandReengaged.vue';
import MissedVisitRatesSeverity from '@/components/reports/MissedVisitRatesSeverity.vue';
import AppointmentAdherence from '@/components/reports/AppointmentAdherence.vue';
import ViralSuppression6Months from '@/components/reports/ViralSuppression6Months.vue';
import ViralSuppression12Months from '@/components/reports/ViralSuppression12Months.vue';
import ViralSuppression24Months from '@/components/reports/ViralSuppression24Months.vue';
import PregnantARTInitiated from '@/components/reports/PregnantARTInitiated.vue';
import MedianDurationOnArt from '@/components/reports/MedianDurationOnArt.vue';
import DeathOverTime from '@/components/reports/DeathOverTime.vue';
import AgeAtDeath from '@/components/reports/AgeAtDeath.vue';
import SurvivalAnalysis from '@/components/reports/SurvivalAnalysis.vue';

const props = defineProps<{
    cohorts: ICohort[];
    sites: ISite[];
    facilities: IFacility[];
    initialReport?: IReportData | null;
}>();

// Form state using Inertia's useForm
const form = useForm({
    indicator: 'TotalPatientsEverEnrolled',
    cohort_id: '',
    site_id: '',
    facility_id: '',
    start_date: '',
    end_date: ''
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Analytics', href: '/reports' },
    { title: 'Indicator Analysis', href: '' }
];

// Report data
const reportData = ref<IReportData | null | any>(props.initialReport || null);

// UI State
const activeTab = ref('filters');
const isLoading = ref(false);

// Indicator options with categories
const indicatorCategories = {
    'Enrollment & Demographics': [
        { label: 'Total Patients Ever Enrolled', value: 'TotalPatientsEverEnrolled' },
        { label: 'Patients Enrolled on ART', value: 'EnrolledOnART' },
        { label: 'Proportion of Children on ART', value: 'ProportionOfChildrenOnART' },
    ],
    'Retention & Adherence': [
        { label: 'Patients Retained After 12 Months (%)', value: 'PatientsRetainedAfter12Months' },
        { label: 'Patients Retained After 24 Months (%)', value: 'PatientsRetainedAfter24Months' },
        { label: 'Patients Retained After 60 Months (%)', value: 'PatientsRetainedAfter60Months' },
        { label: 'Patients Retained After 120 Months (%)', value: 'PatientsRetainedAfter120Months' },
        { label: 'Patients LTFU and Re-engaged', value: 'PatientsLTFUAndReengaged' },
        { label: 'Missed Appointment Visits (90+ days)', value: 'MissedAppointmentVisits' },
        { label: 'Missed Appointments (%)', value: 'MissedAppointments' },
        { label: 'Missed Visit Rates by Severity', value: 'MissedVisitRates' },
        { label: 'Appointment Adherence', value: 'AppointmentAdherence' },
    ],
    'Clinical Outcomes': [
        { label: 'Patients With Suppressed Viral Load', value: 'PatientsWithSuppressedViralLoad' },
        { label: 'Time to First Viral Load', value: 'TimeToFirstViralLoad' },
        { label: 'Viral Suppression at 6 Months', value: 'ViralSuppressionAt6Months' },
        { label: 'Viral Suppression at 12 Months', value: 'ViralSuppressionAt12Months' },
        { label: 'Viral Suppression at 24 Months', value: 'ViralSuppressionAt24Months' },
        { label: 'Patients Screened For TB', value: 'PatientsScreenedForTB' },
        { label: 'Patients on TB Treatment', value: 'PatientsOnTBTreatment' },
        { label: 'Median Duration on ART Among Active Patients', value: 'MedianDurationOnART' },
    ],
    'Mortality & Survival': [
        { label: 'Deaths among ART patients', value: 'DeathsAmongART' },
        { label: 'Deaths Over Time', value: 'DeathsOverTime' },
        { label: 'Age at death distribution', value: 'AgeAtDeath' },
        { label: 'Survival Analysis After ART', value: 'SurvivalAnalysis' },
    ],
    'Maternal & Child Health': [
        { label: 'Pregnant Women Retained After 12 Months', value: 'PregnantWomenRetainedAfter12Months' },
        { label: 'Pregnant Women Retained After 24 Months', value: 'PregnantWomenRetainedAfter24Months' },
        { label: 'Pregnant Women LTFU After 12 Months', value: 'PregnantWomenLTFUAfter12Months' },
        { label: 'Pregnant Women Died Within 12 Months', value: 'PregnantWomenDiedWithin12Months' },
        { label: 'Patients Initiated on ART Whilst Pregnant', value: 'PatientsInitiatedOnARTWhilstPregnant' },
    ],
    'Program Operations': [
        { label: 'Patients Transferred Out', value: 'TransferredOut' },
        { label: 'Regimen Switches (Side Effects/Treatment Failure)', value: 'RegimenSwitches' },
    ]
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Fetch report from backend
const fetchReport = async () => {
    isLoading.value = true;
    try {
        await form.get(route('reports.indicators', { indicator: form.indicator }), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                reportData.value = page.props.report;
                activeTab.value = 'results';
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    } catch (error) {
        isLoading.value = false;
        console.error('Error fetching report:', error);
    }
};

// Reset filters
const resetFilters = () => {
    form.indicator = 'TotalPatientsEverEnrolled';
    form.cohort_id = '';
    form.site_id = '';
    form.facility_id = '';
    form.start_date = '';
    form.end_date = '';
    reportData.value = null;
    activeTab.value = 'filters';
};

// Computed properties for UI logic
const hasBreakdown = () => reportData.value && 'breakdown' in reportData.value;
const hasPercentageMetrics = () => reportData.value && 'percentage' in reportData.value;
const isMissedAppointmentsReport = () => reportData.value?.indicator.includes('Missed Appointment');
const isPregnantWomenReport = () => reportData.value?.indicator.includes('Pregnant women');
const isPregnantWomenLTFUReport = () => reportData.value?.indicator.includes('Pregnant women LTFU');
const isPregnantWomenDiedReport = () => reportData.value?.indicator.includes('Pregnant women Died');

// Computed properties for cascading filters
const filteredSites = computed(() => {
    if (form.cohort_id) {
        return props.sites.filter(site => site.cohort_id == Number(form.cohort_id));
    }
    return props.sites;
});

const filteredFacilities = computed(() => {
    const siteIdNum = form.site_id ? Number(form.site_id) : null;
    const cohortIdNum = form.cohort_id ? Number(form.cohort_id) : null;

    let facilities = props.facilities;
    if (siteIdNum) {
        facilities = facilities.filter(facility => Number(facility.site_id) === siteIdNum);
    } else if (cohortIdNum) {
        const cohortSiteIds = props.sites
            .filter(site => Number(site.cohort_id) === cohortIdNum)
            .map(site => site.id);
        facilities = facilities.filter(facility => cohortSiteIds.includes(Number(facility.site_id)));
    }
    return facilities;
});

// Watchers for auto-selection logic
watch(() => form.site_id, (newSiteId) => {
    if (newSiteId) {
        const selectedSite = props.sites.find(site => site.id == Number(newSiteId));
        if (selectedSite && form.cohort_id != selectedSite.cohort_id.toString()) {
            form.cohort_id = selectedSite.cohort_id.toString();
        }
    }
});

watch(() => form.facility_id, (newFacilityId) => {
    if (newFacilityId) {
        const selectedFacility = props.facilities.find(facility => facility.id == Number(newFacilityId));
        if (selectedFacility && form.site_id != selectedFacility.site_id.toString()) {
            form.site_id = selectedFacility.site_id.toString();
            const selectedSite = props.sites.find(site => site.id == selectedFacility.site_id);
            if (selectedSite && form.cohort_id != selectedSite.cohort_id.toString()) {
                form.cohort_id = selectedSite.cohort_id.toString();
            }
        }
    }
});

// Table type determination
const tableType = computed(() => {
    if (!reportData.value || !hasBreakdown()) return null;

    if (reportData.value.indicator === 'Total patients ever enrolled on ART' || reportData.value.indicator === 'Patients Enrolled on ART') {
        return 'proportions';
    }

    if (reportData.value.indicator === 'Proportion of children on ART') {
        return 'art-children';
    }

    if (reportData.value.indicator === 'Pregnant women retained after 12 months' || reportData.value.indicator === 'Pregnant women retained after 24 months') {
        return 'pregnant-women';
    }

    if (reportData.value.indicator === 'Pregnant women LTFU after 12 months') {
        return 'pregnant-women-ltfu';
    }

    if (reportData.value.indicator === 'Pregnant women died within 12 months') {
        return 'pregnant-women-died';
    }

    return 'standard';
});

const isChildAgeGroup = (ageGroup: string | number) => {
    const childGroups = [
        '≤2 months', '3-12 months', '13-24 months', '25-59 months',
        '5-9 years', '10-14 years', '15-18 years'
    ];
    return childGroups.includes(String(ageGroup));
};

// Helper functions
const getProportionClass = (proportion: number) => {
    if (proportion > 75) return 'text-emerald-600 bg-emerald-50';
    if (proportion > 50) return 'text-amber-600 bg-amber-50';
    return 'text-rose-600 bg-rose-50';
};

const getSeverityClass = (value: number, type: 'mortality' | 'retention' = 'retention') => {
    if (type === 'mortality') {
        if (value > 5) return 'text-rose-600 bg-rose-50';
        if (value > 2) return 'text-amber-600 bg-amber-50';
        return 'text-emerald-600 bg-emerald-50';
    } else {
        if (value > 75) return 'text-emerald-600 bg-emerald-50';
        if (value > 50) return 'text-amber-600 bg-amber-50';
        return 'text-rose-600 bg-rose-50';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50/30">
            <!-- Header -->
            <div class="border-b border-gray-200/50 bg-white/80 backdrop-blur-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 
                                          flex items-center justify-center shadow-md">
                                    <ChartBarIcon class="h-6 w-6 text-white" />
                                </div>
                                <div class="text-sm font-medium text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full">
                                    INDICATOR ANALYSIS
                                </div>
                            </div>
                            <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900">
                                Advanced <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    Indicator Analysis
                                </span>
                            </h1>
                            <p class="text-gray-600 mt-2">
                                Analyze specific program performance indicators with precision and granularity
                            </p>
                        </div>

                        <!-- Quick Stats -->
                        <div class="flex flex-wrap gap-4">
                            <div class="bg-white/90 backdrop-blur-sm rounded-xl p-3 border border-gray-200/50 
                                      shadow-sm min-w-[140px]">
                                <p class="text-xs text-gray-500 mb-1">Cohorts</p>
                                <p class="text-lg font-semibold text-gray-800">{{ cohorts.length }}</p>
                            </div>
                            <div class="bg-white/90 backdrop-blur-sm rounded-xl p-3 border border-gray-200/50 
                                      shadow-sm min-w-[140px]">
                                <p class="text-xs text-gray-500 mb-1">Sites</p>
                                <p class="text-lg font-semibold text-gray-800">{{ sites.length }}</p>
                            </div>
                            <div class="bg-white/90 backdrop-blur-sm rounded-xl p-3 border border-gray-200/50 
                                      shadow-sm min-w-[140px]">
                                <p class="text-xs text-gray-500 mb-1">Facilities</p>
                                <p class="text-lg font-semibold text-gray-800">{{ facilities.length }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 overflow-x-auto">
                            <button @click="activeTab = 'filters'" :class="[
                                activeTab === 'filters'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                            ]">
                                <FunnelIcon class="h-5 w-5" />
                                Filters & Parameters
                            </button>
                            <button @click="activeTab = 'results'" :class="[
                                activeTab === 'results'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2',
                                { 'opacity-50': !reportData }
                            ]" :disabled="!reportData">
                                <ChartBarIcon class="h-5 w-5" />
                                Analysis Results
                                <span v-if="reportData" class="ml-2 bg-blue-100 text-blue-600 text-xs px-2 py-0.5 rounded-full">
                                    {{ Object.keys(reportData.breakdown || {}).length }} groups
                                </span>
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Filters Panel -->
                <div v-show="activeTab === 'filters'">
                    <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 
                               shadow-sm overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-800">Configure Analysis Parameters</h2>
                                    <p class="text-sm text-gray-600 mt-1">Select indicator and filter criteria to generate your report</p>
                                </div>
                                <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-800 
                                      flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-gray-100 
                                      transition-colors duration-200">
                                    <ArrowPathIcon class="h-4 w-4" />
                                    Reset All
                                </button>
                            </div>

                            <form @submit.prevent="fetchReport" class="space-y-6">
                                <!-- Indicator Selection -->
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <Label class="text-sm font-medium text-gray-700">Indicator Category</Label>
                                        <InformationCircleIcon class="h-4 w-4 text-gray-400" />
                                    </div>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <div v-for="(categoryIndicators, categoryName) in indicatorCategories" :key="categoryName" 
                                             class="bg-gray-50/50 rounded-xl p-4 border border-gray-200">
                                            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ categoryName }}</h3>
                                            <div class="space-y-2">
                                                <label v-for="indicator in categoryIndicators" :key="indicator.value"
                                                       class="flex items-center p-2 rounded-lg hover:bg-white/50 cursor-pointer transition-colors duration-150">
                                                    <input type="radio" v-model="form.indicator" :value="indicator.value"
                                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                    <span class="ml-3 text-sm text-gray-700">{{ indicator.label }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filter Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <!-- Cohort -->
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                            <UserGroupIcon class="h-4 w-4" />
                                            Cohort
                                        </Label>
                                        <Select v-model="form.cohort_id">
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="Select Cohort" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id.toString()">
                                                    {{ cohort.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <!-- Site -->
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                            <BuildingOfficeIcon class="h-4 w-4" />
                                            Site
                                        </Label>
                                        <Select v-model="form.site_id" :disabled="!form.cohort_id">
                                            <SelectTrigger class="w-full">
                                                <SelectValue :placeholder="form.cohort_id ? 'Select Site' : 'Select Cohort first'" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="site in filteredSites" :key="site.id" :value="site.id.toString()">
                                                    {{ site.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <!-- Facility -->
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                            <BuildingOfficeIcon class="h-4 w-4" />
                                            Facility
                                        </Label>
                                        <Select v-model="form.facility_id" :disabled="!form.site_id && !form.cohort_id">
                                            <SelectTrigger class="w-full">
                                                <SelectValue :placeholder="form.site_id ? 'Select Facility' : 'Select Site first'" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="facility in filteredFacilities" :key="facility.id" :value="facility.id.toString()">
                                                    {{ facility.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <!-- Start Date -->
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                            <CalendarIcon class="h-4 w-4" />
                                            Start Date
                                        </Label>
                                        <Input type="date" v-model="form.start_date"
                                               class="w-full" />
                                    </div>

                                    <!-- End Date -->
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                            <CalendarIcon class="h-4 w-4" />
                                            End Date
                                        </Label>
                                        <Input type="date" v-model="form.end_date"
                                               class="w-full" />
                                    </div>

                                    <!-- Action Button -->
                                    <div class="flex items-end">
                                        <Button type="submit" :disabled="form.processing || isLoading"
                                                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 
                                                       text-white px-6 py-3 rounded-xl font-medium shadow-md hover:shadow-lg 
                                                       transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <div class="flex items-center justify-center gap-2">
                                                <span v-if="!isLoading">Generate Analysis</span>
                                                <span v-else>Analyzing...</span>
                                                <ChevronRightIcon class="h-5 w-5" />
                                            </div>
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </Card>

                    <!-- Quick Stats Preview -->
                    <div v-if="reportData" class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Last Analysis Preview</h3>
                        <Card class="bg-gradient-to-r from-blue-50/50 to-white rounded-2xl border border-blue-100/50 
                                   p-4 cursor-pointer hover:shadow-md transition-shadow duration-300"
                              @click="activeTab = 'results'">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Last Generated</p>
                                    <p class="font-semibold text-gray-800">{{ reportData?.indicator }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Results</p>
                                    <p class="text-lg font-bold text-blue-600">
                                        {{ Object.keys(reportData?.breakdown || {}).length }} age groups
                                    </p>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Results Panel -->
                <div v-show="activeTab === 'results' && reportData">
                    <div class="space-y-6">
                        <!-- Report Header -->
                        <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm">
                            <div class="p-6">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 
                                                      flex items-center justify-center shadow-md">
                                                <ChartBarIcon class="h-6 w-6 text-white" />
                                            </div>
                                            <div>
                                                <h2 class="text-xl md:text-2xl font-bold text-gray-800">
                                                    {{ reportData?.indicator }}
                                                </h2>
                                                <div class="flex flex-wrap gap-2 mt-2">
                                                    <span class="text-xs px-3 py-1 rounded-full bg-blue-50 text-blue-600">
                                                        {{ form.cohort_id ? cohorts.find(c => c.id === Number(form.cohort_id))?.name : 'All Cohorts' }}
                                                    </span>
                                                    <span v-if="form.site_id" class="text-xs px-3 py-1 rounded-full bg-green-50 text-green-600">
                                                        {{ filteredSites.find(s => s.id === Number(form.site_id))?.name }}
                                                    </span>
                                                    <span v-if="form.facility_id" class="text-xs px-3 py-1 rounded-full bg-purple-50 text-purple-600">
                                                        {{ filteredFacilities.find(f => f.id === Number(form.facility_id))?.name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-600">Date Range</p>
                                        <p class="font-medium text-gray-800">
                                            {{ formatDate(form.start_date) || 'Start' }} - {{ formatDate(form.end_date) || 'End' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">Generated just now</p>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Specialized Components -->
                        <template v-if="reportData && reportData.indicator === 'Time to first Viral Load test'">
                            <TimeToFirstVL :report-data="reportData"
                                           :start-date="formatDate(form.start_date) || 'Start'"
                                           :end-date="formatDate(form.end_date) || 'End'" />
                        </template>

                        <template v-if="reportData && reportData.indicator === 'Deaths among ART patients'">
                            <ARTPatientsDeath :report-data="reportData"
                                              :start-date="formatDate(form.start_date) || 'Start'"
                                              :end-date="formatDate(form.end_date) || 'End'" />
                        </template>

                        <template v-if="reportData && reportData.indicator === 'Patients lost to follow-up and re-engaged'">
                            <LTFandReengaged :report-data="reportData"
                                             :start-date="formatDate(form.start_date) || 'Start'"
                                             :end-date="formatDate(form.end_date) || 'End'" />
                        </template>

                        <!-- Continue with other specialized components... -->

                        <!-- Standard Breakdown Tables -->
                        <template v-if="hasBreakdown() && reportData && !['Time to first Viral Load test', 'Deaths among ART patients'].includes(reportData.indicator)">
                            <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm overflow-hidden">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-semibold text-gray-800">Detailed Breakdown</h3>
                                        <div class="text-sm text-gray-600">
                                            {{ Object.keys(reportData.breakdown).length }} age groups analyzed
                                        </div>
                                    </div>

                                    <!-- Table for standard indicators -->
                                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                                        <table v-if="tableType === 'standard'" class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Age Group
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Male
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Female
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Other
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Total
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup"
                                                    class="hover:bg-gray-50/50 transition-colors duration-150">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {{ ageGroup }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                        {{ data.male }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                        {{ data.female }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                        {{ data.other }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                        {{ data.total }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot v-if="reportData.total !== undefined" class="bg-gray-50/50">
                                                <tr>
                                                    <td class="px-6 py-4 text-sm font-semibold">Grand Total</td>
                                                    <td colspan="3"></td>
                                                    <td class="px-6 py-4 text-sm font-semibold text-blue-600">
                                                        {{ reportData.total }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>

                                        <!-- Continue with other table types... -->
                                    </div>
                                </div>
                            </Card>
                        </template>

                        <!-- Summary Cards -->
                        <div v-if="hasPercentageMetrics()" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template v-if="isMissedAppointmentsReport()">
                                <MissedAppointmentsSummaryCard :report-data="reportData" />
                            </template>

                            <template v-if="isPregnantWomenReport() && !isPregnantWomenLTFUReport()">
                                <PregnantWomanSummaryCard :report-data="reportData" />
                            </template>

                            <!-- Continue with other summary cards... -->
                        </div>

                        <!-- Export & Actions -->
                        <Card class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm">
                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Export Options</h3>
                                        <p class="text-sm text-gray-600">Download this analysis for further processing</p>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <Button variant="outline" class="flex items-center gap-2">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Export as PDF
                                        </Button>
                                        <Button variant="outline" class="flex items-center gap-2">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Export as CSV
                                        </Button>
                                        <Button @click="activeTab = 'filters'" class="bg-gradient-to-r from-blue-600 to-blue-700">
                                            New Analysis
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="activeTab === 'results' && !reportData" class="text-center py-16">
                    <div class="max-w-md mx-auto">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-50 
                                  flex items-center justify-center mx-auto mb-6">
                            <ChartBarIcon class="h-10 w-10 text-blue-400" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">No Analysis Generated</h3>
                        <p class="text-gray-600 mb-6">
                            Configure your analysis parameters and generate a report to view detailed insights.
                        </p>
                        <Button @click="activeTab = 'filters'" class="bg-gradient-to-r from-blue-600 to-blue-700">
                            Configure Analysis
                        </Button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="fixed inset-0 bg-black/10 backdrop-blur-sm flex items-center justify-center z-50">
                    <div class="bg-white rounded-2xl p-8 shadow-xl border border-gray-200/50">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-4 text-gray-700 font-medium">Analyzing data...</p>
                        <p class="text-sm text-gray-500 mt-2">This may take a moment</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Smooth transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Scrollbar styling */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Table hover effects */
table tbody tr {
    transition: all 0.2s ease;
}

table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

/* Card hover effects */
.card-hover {
    transition: all 0.3s ease;
}

.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Gradient text animation */
.gradient-text {
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    background-size: 200% 200%;
    animation: gradient 3s ease infinite;
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
</style>