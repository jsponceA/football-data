<script setup lang="ts">
import type { CompetitionBrief } from '@/types/api'

defineProps<{ competitions: CompetitionBrief[] }>()
</script>

<template>
  <div class="d-flex align-center ga-1">
    <div v-for="c in competitions" :key="c.id" class="emblem-tile emblem-tile--sm">
      <img v-if="c.emblem" :src="c.emblem" :alt="c.name" />
      <v-icon v-else icon="mdi-trophy" size="14" color="grey" />
      <v-tooltip activator="parent" location="top" :text="c.name" />
    </div>
    <span v-if="!competitions.length" class="text-caption text-medium-emphasis">Sin liga</span>
  </div>
</template>
