<script setup lang="ts">
import { useThemeToggle } from '@/composables/useThemeToggle'

const { isDark, toggle } = useThemeToggle()
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
