<template>
  <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden" :class="{'animate': animate}">
    <div class="h-full bg-teal-600 rounded-full" :style="{ width: `${value}%` }"></div>
  </div>
</template>

<script setup>
defineProps({
  value: {
    type: Number,
    default: 0
  },
  animate: {
    type: Boolean,
    default: true
  }
});
</script>

<style scoped>


.progress-fill {
 
  transition: width 0.5s ease-in-out;
}

.animate .progress-fill {
  transition: width 0.5s ease-in-out;
}
</style>