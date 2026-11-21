<script setup lang="ts">
import { computed } from 'vue'
import LeagueBadges from '@/components/LeagueBadges.vue'
import type { Team } from '@/types/api'

// dentro de una liga ya se sabe en qué liga está, así que allí se oculta el pie con las ligas
const props = withDefaults(defineProps<{ team: Team; showLeagues?: boolean }>(), { showLeagues: true })

// en el listado el backend manda el conteo; dentro de una liga vienen los jugadores completos
const playersCount = computed(() => props.team.players_count ?? props.team.players?.length ?? 0)
</script>

<template>
  <v-card :to="{ name: 'team', params: { id: team.id } }" border flat class="entity-card" height="100%">
    <div class="entity-card__banner" />
    <div class="entity-card__body">
      <div class="emblem-tile emblem-tile--lg">
        <img v-if="team.crest" :src="team.crest" :alt="team.name" />
        <v-icon v-else icon="mdi-shield-half-full" size="36" color="grey" />
      </div>
      <div class="d-flex align-center ga-2 mt-3">
        <div class="text-subtitle-1 font-weight-bold text-truncate" :title="team.name">{{ team.name }}</div>
        <v-chip v-if="team.tla" size="x-small" label class="flex-none">{{ team.tla }}</v-chip>
      </div>
      <div class="d-flex align-center ga-1 text-body-2 text-medium-emphasis mt-1">
        <v-icon icon="mdi-stadium-outline" size="16" />
        <span class="text-truncate">{{ team.venue ?? '—' }}</span>
      </div>
      <div class="d-flex ga-4 text-body-2 text-medium-emphasis mt-1">
        <span class="d-flex align-center ga-1">
          <v-icon icon="mdi-calendar-outline" size="16" /> {{ team.founded ?? '—' }}
        </span>
        <span class="d-flex align-center ga-1">
          <v-icon icon="mdi-account-group-outline" size="16" /> {{ playersCount }} jugadores
        </span>
      </div>
    </div>
    <template v-if="showLeagues">
      <v-divider />
      <div class="d-flex align-center px-4 py-2">
        <LeagueBadges :competitions="team.competitions ?? []" />
        <v-spacer />
        <v-icon icon="mdi-chevron-right" size="20" class="text-medium-emphasis" />
      </div>
    </template>
  </v-card>
</template>
