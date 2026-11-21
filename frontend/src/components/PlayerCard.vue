<script setup lang="ts">
import CountryFlag from '@/components/CountryFlag.vue'
import LeagueBadges from '@/components/LeagueBadges.vue'
import type { Player } from '@/types/api'
import { age, initials, positionColor, positionLabel } from '@/utils/format'

// el pie con equipo y ligas solo sale cuando el jugador trae su equipo (en el detalle de un equipo no hace falta)
defineProps<{ player: Player }>()
</script>

<template>
  <v-card
    border
    flat
    class="entity-card"
    height="100%"
    :style="{ '--entity-color': `var(--v-theme-${positionColor(player.position)})` }"
  >
    <div class="entity-card__banner" />
    <div class="entity-card__body">
      <!-- football-data no trae fotos de jugadores, así que va un avatar con las iniciales -->
      <v-avatar :color="positionColor(player.position)" size="72" class="entity-card__avatar">
        {{ initials(player.name) }}
      </v-avatar>
      <div class="text-subtitle-1 font-weight-bold text-truncate mt-3" :title="player.name">
        {{ player.name }}
      </div>
      <v-chip size="x-small" label variant="tonal" :color="positionColor(player.position)" class="mt-1">
        {{ positionLabel(player.position) }}
      </v-chip>
      <div class="d-flex flex-wrap gc-4 gr-1 text-body-2 text-medium-emphasis mt-2">
        <CountryFlag :name="player.nationality" label />
        <span class="d-flex align-center ga-1">
          <v-icon icon="mdi-cake-variant-outline" size="16" />
          {{ age(player.date_of_birth) !== null ? `${age(player.date_of_birth)} años` : '—' }}
        </span>
      </div>
    </div>

    <template v-if="player.team">
      <v-divider />
      <div class="d-flex align-center ga-2 px-4 py-2">
        <router-link
          :to="{ name: 'team', params: { id: player.team.id } }"
          class="d-flex align-center ga-2 team-link overflow-hidden"
        >
          <div class="emblem-tile emblem-tile--sm">
            <img v-if="player.team.crest" :src="player.team.crest" :alt="player.team.name" />
            <v-icon v-else icon="mdi-shield-half-full" size="14" color="grey" />
          </div>
          <span class="text-body-2 text-truncate">{{ player.team.name }}</span>
        </router-link>
        <v-spacer />
        <LeagueBadges :competitions="player.team.competitions" />
      </div>
    </template>
  </v-card>
</template>
