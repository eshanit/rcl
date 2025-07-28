<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  }
});
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 300px;
}
</style>
<template>
  <div class="bg-white rounded-xl shadow p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-lg font-semibold text-gray-800">{{ title }}</h2>
      <div v-if="$slots.actions" class="flex items-center space-x-2">
        <slot name="actions"></slot>
      </div>
      <button v-else class="text-gray-500 hover:text-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
        </svg>
      </button>
    </div>
    <div class="chart-container">
      <slot></slot>
    </div>
    <div v-if="description" class="mt-4 text-sm text-gray-500">
      {{ description }}
    </div>
  </div>
</template>

