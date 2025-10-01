<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

const props = defineProps<{
    reportData: {
        breakdown: any
    };
}>()
</script>

<template>
    <Table class="min-w-full divide-y divide-gray-200 border border-gray-200">
        <TableHeader class="bg-gray-50">
            <TableRow>
                <TableHead class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Age Group
                </TableHead>
                <TableHead class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Retained
                </TableHead>
                <TableHead class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Total Pregnant*
                </TableHead>
                <TableHead class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Proportion
                </TableHead>
            </TableRow>
        </TableHeader>
        <TableBody class="bg-white divide-y divide-gray-200">
            <TableRow v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">
                    {{ ageGroup }}
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center border-r">
                    {{ data.retained }}
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center border-r">
                    {{ data.total_pregnant }}
                </TableCell>
                <TableCell 
                    class="px-4 py-3 whitespace-nowrap text-sm text-center font-semibold"
                    :class="{ 
                        'text-green-600': data.proportion > 75, 
                        'text-yellow-500': data.proportion > 50 && data.proportion <= 75, 
                        'text-red-600': data.proportion <= 50 
                    }"
                >
                    {{ data.proportion }}%
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
    
    <!-- Info note about proportions -->
    <div class="mt-4 bg-blue-50 p-3 rounded-lg border border-blue-200">
        <p class="text-sm text-blue-700 flex items-start">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 mt-0.5 flex-shrink-0" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                    clip-rule="evenodd" />
            </svg>
            Proportion represents the percentage of retained pregnant women relative to the total pregnant
            population in each age group
        </p>
    </div>
</template>