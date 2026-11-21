<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance'

const { isDark, toggle } = useAppearance()
</script>

<template>
  <v-btn
    :icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
    variant="text"
    :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
    :title="isDark ? 'Modo claro' : 'Modo oscuro'"
    @click="toggle"
  />
</template>
