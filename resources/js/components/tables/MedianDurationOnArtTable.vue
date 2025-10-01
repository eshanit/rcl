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
        indicator: number;
        median_duration: number;
        total_active_patients: number;
        breakdown: any;
        coverage: any;
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
                    {{ Math.round(data.male) }} <span class="text-xs text-gray-500">months</span>
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center"
                    :class="useGetDaysClass(data.female)">
                    {{ Math.round(data.female) }} <span class="text-xs text-gray-500">months</span>
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center" :class="useGetDaysClass(data.other)">
                    {{ Math.round(data.other) }} <span class="text-xs text-gray-500">months</span>
                </TableCell>
                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-center font-semibold"
                    :class="useGetDaysClass(data.total)">
                    {{ Math.round(data.total) }} <span class="text-xs text-gray-500">months</span>
                </TableCell>
            </TableRow>
        </TableBody>
        <!-- Footer section -->
        <TableFooter class="bg-gray-50 font-semibold">
            <TableRow>
                <TableCell class="px-4 py-3 text-sm border-r">Median Duration On ART</TableCell>
                <TableCell colspan="4" class="px-4 py-3 text-sm text-center text-blue-600 font-bold">
                    {{ Math.round(reportData.median_duration) }} months
                </TableCell>
            </TableRow>
        </TableFooter>
    </Table>
</template>