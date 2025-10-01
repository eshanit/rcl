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
        male_count: number;
        male_proportion: number;
        female_count: number;
        female_proportion: number;
        total: number;
        breakdown: any
    };
}>()
</script>

<template>
    <Table class="min-w-full divide-y divide-gray-200">
        <TableHeader class="bg-gray-50">
            <TableRow>
                <TableHead class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Age Group
                </TableHead>
                <TableHead class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Male
                </TableHead>
                <TableHead class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Female
                </TableHead>
                <TableHead class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Other
                </TableHead>
                <TableHead class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Total
                </TableHead>
            </TableRow>
        </TableHeader>
        <TableBody class="bg-white divide-y divide-gray-200">
            <TableRow v-for="(data, ageGroup) in reportData.breakdown" :key="ageGroup">
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ ageGroup }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ data.male }} <span class="text-xs text-gray-400">({{ data.male_proportion }}%)</span>
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ data.female }} <span class="text-xs text-gray-400">({{ data.female_proportion }}%)</span>
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ data.other }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                    {{ data.total }}
                </TableCell>
            </TableRow>
            
            <!-- Footer row -->
            <TableRow v-if="reportData.total !== undefined" class="bg-gray-50 font-semibold">
                <TableCell class="px-6 py-4 text-sm">
                    Grand Total
                </TableCell>
                <TableCell class="px-6 py-4 text-sm">
                    {{ reportData.male_count }} <span class="text-xs text-gray-400">({{ reportData.male_proportion }}%)</span>
                </TableCell>
                <TableCell class="px-6 py-4 text-sm">
                    {{ reportData.total - reportData.male_count }} 
                    <span class="text-xs text-gray-400">({{ (100 - reportData.male_proportion).toFixed(2) }}%)</span>
                </TableCell>
                <TableCell class="px-6 py-4 text-sm">
                    -
                </TableCell>
                <TableCell class="px-6 py-4 text-sm">
                    {{ reportData.total }}
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>