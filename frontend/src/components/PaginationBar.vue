<script setup lang="ts">
import { computed } from 'vue'
import type { PaginationMeta } from '@/types/api'

const props = defineProps<{ meta: PaginationMeta | null }>()

const page = defineModel<number>({ required: true })

const from = computed(() => (props.meta ? (props.meta.current_page - 1) * props.meta.per_page + 1 : 0))
const to = computed(() =>
  props.meta ? Math.min(props.meta.current_page * props.meta.per_page, props.meta.total) : 0,
)
</script>

<template>
  <div
    v-if="meta && meta.total > 0"
    class="d-flex flex-wrap align-center justify-space-between ga-2 mt-3"
  >
    <span class="text-caption text-medium-emphasis">
      Mostrando del {{ from }} al {{ to }} de {{ meta.total }} entradas
    </span>
    <v-pagination
      v-if="meta.last_page > 1"
      v-model="page"
      :length="meta.last_page"
      :total-visible="5"
      density="comfortable"
      class="w-auto"
    />
  </div>
</template>
