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
        total: number;
        breakdown: any;
        chart_data: any;
    };
}>();
</script>

<template>
    <Table class="min-w-full divide-y divide-gray-200">
        <TableHeader class="bg-gray-50">
            <TableRow>
                <TableHead class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Month
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
            <TableRow v-for="(month, index) in reportData.chart_data.labels" :key="month">
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ month }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ reportData.chart_data.datasets[0].data[index] }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ reportData.chart_data.datasets[1].data[index] }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ reportData.chart_data.datasets[2].data[index] }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                    {{ reportData.chart_data.datasets[0].data[index] + reportData.chart_data.datasets[1].data[index] + reportData.chart_data.datasets[2].data[index] }}
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>