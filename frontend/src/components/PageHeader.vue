<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-4">
    <div>
      <h1 class="text-h5">{{ title }}</h1>
      <p v-if="subtitle" class="text-body-2 text-medium-emphasis">{{ subtitle }}</p>
    </div>
    <div class="d-flex align-center ga-2">
      <slot />
    </div>
  </div>
</template>
