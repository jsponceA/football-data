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
    // forma del esqueleto mientras carga por primera vez
    skeleton?: 'table' | 'cards' | 'detail'
  }>(),
  { emptyTitle: 'No hay resultados', emptyText: '', emptyIcon: 'mdi-database-off-outline', skeleton: 'table' },
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

  <div v-else-if="loading && empty" role="status" aria-label="Cargando">
    <v-skeleton-loader v-if="skeleton === 'table'" type="table-thead, table-row@8" border />

    <v-row v-else-if="skeleton === 'cards'">
      <v-col v-for="n in 8" :key="n" cols="12" sm="6" md="4" xl="3">
        <v-skeleton-loader type="list-item-avatar-two-line, chip" border />
      </v-col>
    </v-row>

    <v-skeleton-loader v-else type="list-item-avatar-two-line, heading, table-row@6" />
  </div>

  <EmptyState v-else-if="empty" :icon="emptyIcon" :title="emptyTitle" :text="emptyText" />

  <slot v-else />
</template>
