import { Ref } from 'vue';
export const useGetGenderAverage = (gender: string, reportData: any  ) => {
  if (!reportData.breakdown) return '0';

  let total = 0;
  let count = 0;

  for (const ageGroup in reportData.breakdown) {
    const value = reportData.breakdown[ageGroup][gender];
    if (value > 0) {
      total += value;
      count++;
    }
  }

  return count ? (total / count).toFixed(1): '0';
};
