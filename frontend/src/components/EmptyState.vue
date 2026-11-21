<script setup lang="ts">
withDefaults(defineProps<{ icon?: string; title: string; text?: string }>(), {
  icon: 'mdi-information-outline',
  text: '',
})
</script>

<template>
  <div class="d-flex flex-column align-center text-center py-12 px-4">
    <v-icon :icon="icon" size="64" color="medium-emphasis" class="mb-4" />
    <h2 class="text-h6 mb-1">{{ title }}</h2>
    <p v-if="text" class="text-body-2 text-medium-emphasis mb-4">{{ text }}</p>
    <slot />
  </div>
</template>
