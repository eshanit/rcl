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
        age_distribution: any;
    };
}>();
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
            <TableRow v-for="(data, ageGroup) in reportData.age_distribution" :key="ageGroup">
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ ageGroup }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ data.male }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ data.female }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ data.other }}
                </TableCell>
                <TableCell class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                    {{ data.total }}
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>