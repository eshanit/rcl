import { computed } from 'vue';

export const useGetDaysClass = (days: number) => {
    if (days > 180) return 'text-red-600 font-semibold';
    if (days > 90) return 'text-orange-500';
    if (days > 60) return 'text-yellow-500';
    return 'text-green-600';
};