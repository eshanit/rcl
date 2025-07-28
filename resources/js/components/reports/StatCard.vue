<script setup>
import { defineProps, computed } from 'vue';
import {
    UserGroupIcon,
    BeakerIcon,
    HeartIcon,
    UserIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';
import {
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon
} from '@heroicons/vue/24/solid';

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    color: {
        type: String,
        default: 'primary'
    },
    trend: Object
});

const iconComponent = computed(() => {
    switch (props.icon) {
        case 'users': return UserGroupIcon;
        case 'vial': return BeakerIcon;
        case 'lungs': return HeartIcon;
        case 'baby': return UserIcon;
        case 'clock': return ClockIcon;
        default: return UserGroupIcon;
    }
});

const trendClass = computed(() => {
    const base = 'flex items-center text-sm';
    switch (props.trend.direction) {
        case 'up': return `${base} text-green-600`;
        case 'down': return `${base} text-yellow-600`;
        default: return `${base} text-gray-600`;
    }
});
</script>
<template>
    <div class="bg-white rounded-xl shadow p-6 transition-all hover:shadow-lg">
        <div class="flex justify-between">
            <div>
                <p class="text-gray-500">{{ title }}</p>
                <h3 class="text-3xl font-bold mt-2">{{ value }}</h3>
            </div>
            <div :class="`bg-${color} bg-opacity-10 p-3 rounded-lg`">
                <div :class="`bg-${color} bg-opacity-20 rounded-full p-2`">
                    <component :is="iconComponent" :class="`h-6 w-6 text-${color}`" />
                </div>
            </div>
        </div>
        <div class="mt-4">
            <div v-if="trend" :class="trendClass">
                <ArrowTrendingUpIcon v-if="trend.direction === 'up'" class="h-4 w-4 mr-1" />
                <ArrowTrendingDownIcon v-else-if="trend.direction === 'down'" class="h-4 w-4 mr-1" />
                <span>{{ trend.value }}</span>
            </div>
        </div>
    </div>
</template>
