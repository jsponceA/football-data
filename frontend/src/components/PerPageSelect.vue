<script setup lang="ts">
import { PER_PAGE_OPTIONS } from '@/composables/usePaginatedList'

const perPage = defineModel<number>({ required: true })

const items = PER_PAGE_OPTIONS.map((n) => ({ title: `${n} / pág.`, value: n }))
</script>

<template>
  <v-select
    v-model="perPage"
    :items="items"
    density="compact"
    variant="outlined"
    hide-details
    aria-label="Filas por página"
    class="per-page"
    style="max-width: 140px"
  />
</template>
