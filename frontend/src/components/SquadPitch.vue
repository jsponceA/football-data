<script setup lang="ts">
import { computed } from 'vue'
import type { Player } from '@/types/api'
import { age, initials, positionLabel } from '@/utils/format'

export interface SquadGroup {
  label: string
  color: string
  players: Player[]
}

const props = defineProps<{ groups: SquadGroup[]; compact?: boolean }>()

// el delantero queda arriba y el portero abajo, como se ve una cancha vertical
const lines = computed(() => props.groups.filter((g) => g.color !== 'grey').reverse())
const unplaced = computed(() => props.groups.filter((g) => g.color === 'grey'))

function detail(player: Player) {
  const years = age(player.date_of_birth)
  return [player.name, positionLabel(player.position), player.nationality, years !== null ? `${years} años` : null]
    .filter(Boolean)
    .join(' · ')
}
</script>

<template>
  <div class="pitch" :class="{ 'pitch--compact': compact }">
    <span class="pitch__circle" />
    <span class="pitch__box pitch__box--top" />
    <span class="pitch__box pitch__box--bottom" />

    <div v-for="line in lines" :key="line.label" class="pitch__line">
      <span class="pitch__label">{{ line.label }}</span>
      <div v-for="player in line.players" :key="player.id" class="pitch__player">
        <v-avatar :color="line.color" :size="compact ? 26 : 44" class="pitch__avatar">{{ initials(player.name) }}</v-avatar>
        <div v-if="!compact" class="pitch__name">{{ player.name }}</div>
        <v-tooltip activator="parent" location="top" :text="detail(player)" />
      </div>
    </div>
  </div>

  <!-- jugadores sin posición conocida: no se pueden colocar en la cancha -->
  <div v-for="group in unplaced" :key="group.label" class="mt-4">
    <div class="text-body-2 text-medium-emphasis mb-2">{{ group.label }}</div>
    <div class="d-flex flex-wrap ga-2">
      <v-chip v-for="player in group.players" :key="player.id" size="small">{{ player.name }}</v-chip>
    </div>
  </div>
</template>
