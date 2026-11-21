<script setup lang="ts">
import type { PaginationMeta } from '@/types/api'

defineProps<{ meta: PaginationMeta | null }>()

const page = defineModel<number>({ required: true })
</script>

<template>
  <div v-if="meta && meta.last_page > 1" class="d-flex flex-column align-center mt-4">
    <v-pagination v-model="page" :length="meta.last_page" :total-visible="5" density="comfortable" />
    <span class="text-caption text-medium-emphasis">{{ meta.total }} resultados</span>
  </div>
</template>
