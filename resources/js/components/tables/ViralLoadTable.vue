<script setup lang="ts">
import { useGetDaysClass } from '@/composables/useGetDaysClass';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

const props = defineProps<{
    reportData: {
        average_days: number;
        total_patients: number;
        median_days: number;
        patients_in_target: number;
        breakdown: any
    };
}>();

</script>
<template>
        <Table class="min-w-full border border-gray-200">
        <TableHeader class="bg-gray-50">
            <TableRow>
                <TableHead
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Age Group
                </TableHead>
                <TableHead
                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Male
                </TableHead>
                <TableHead
                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Female
                </TableHead>
                <TableHead
                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                    Other
                </TableHead>
                <TableHead class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Total
                </TableHead>
            </TableRow>
        </TableHeader>
        <TableBody class="bg-white">
            <TableRow v-for="(data, ageGroup) in props.reportData.breakdown" :key="ageGroup">
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">
                    {{ ageGroup }}
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center" :class="useGetDaysClass(data.male)">
                    {{ data.male }} <span class="text-xs text-gray-500">days</span>
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center"
                    :class="useGetDaysClass(data.female)">
                    {{ data.female }} <span class="text-xs text-gray-500">days</span>
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center" :class="useGetDaysClass(data.other)">
                    {{ data.other }} <span class="text-xs text-gray-500">days</span>
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center font-semibold"
                    :class="useGetDaysClass(data.total)">
                    {{ data.total }} <span class="text-xs text-gray-500">days</span>
                </TableCell>
            </TableRow>
        </TableBody>
        <!-- Footer section -->
        <TableFooter class="bg-gray-50 font-semibold">
            <TableRow>
                <TableCell class="px-4 py-3 text-sm border-r">Overall Average</TableCell>
                <TableCell colspan="4" class="px-4 py-3 text-sm text-center text-blue-600 font-bold">
                    {{ reportData.average_days }} days
                </TableCell>
            </TableRow>
        </TableFooter>
    </Table>
</template>