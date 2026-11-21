<script setup lang="ts">
defineProps<{ label: string; wide?: boolean }>()
</script>

<template>
  <div class="filter-field" :class="{ 'filter-field--wide': wide }">
    <span class="filter-label">{{ label }}</span>
    <slot />
  </div>
</template>
