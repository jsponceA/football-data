<script setup lang="ts">
import EmptyState from './EmptyState.vue'

withDefaults(
  defineProps<{
    loading: boolean
    error: string
    empty: boolean
    emptyTitle?: string
    emptyText?: string
    emptyIcon?: string
  }>(),
  { emptyTitle: 'No hay resultados', emptyText: '', emptyIcon: 'mdi-database-off-outline' },
)

defineEmits<{ retry: [] }>()
</script>

<template>
  <v-alert v-if="error" type="error" variant="tonal" class="my-4">
    {{ error }}
    <template #append>
      <v-btn variant="text" size="small" @click="$emit('retry')">Reintentar</v-btn>
    </template>
  </v-alert>

  <div v-else-if="loading && empty" class="d-flex justify-center py-12">
    <v-progress-circular indeterminate color="primary" aria-label="Cargando" />
  </div>

  <EmptyState v-else-if="empty" :icon="emptyIcon" :title="emptyTitle" :text="emptyText" />

  <slot v-else />
</template>
