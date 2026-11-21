<script setup lang="ts">
import { computed } from 'vue'
import type { Crumb } from '@/types/ui'

const props = defineProps<{ crumbs: Crumb[] }>()

// "Inicio" siempre va primero; la raíz redirige a la primera sección que el usuario pueda ver
const items = computed(() => [{ title: 'Inicio', to: { name: 'home' } }, ...props.crumbs])
</script>

<template>
  <div class="d-flex flex-wrap align-center justify-space-between ga-2 mb-3 pb-2 border-b">
    <v-breadcrumbs :items="items" class="page-crumbs pa-0 ma-0">
      <template #prepend>
        <v-icon icon="mdi-view-dashboard-outline" size="small" class="mr-2" />
      </template>
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="small" />
      </template>
    </v-breadcrumbs>

    <div v-if="$slots.default" class="d-flex flex-wrap align-center ga-2">
      <slot />
    </div>
  </div>
</template>
