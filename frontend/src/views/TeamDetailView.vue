<script setup lang="ts">
import { computed } from 'vue'
import { getTeam } from '@/api/football'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PlayerCard from '@/components/PlayerCard.vue'
import SquadPitch from '@/components/SquadPitch.vue'
import { usePageTitle } from '@/composables/usePageTitle'
import { useRequest } from '@/composables/useRequest'
import { positionGroup, positionGroups } from '@/utils/format'

const props = defineProps<{ id: number }>()

const { data: team, loading, error, load } = useRequest(() => getTeam(props.id))

usePageTitle(() => team.value?.name)

// la plantilla se muestra por líneas (porteros, defensas...); lo que no encaja en ninguna va al final
const squad = computed(() => {
  const players = team.value?.players ?? []
  const groups = positionGroups.map((g) => ({
    label: g.label,
    color: g.color,
    players: players.filter((p) => positionGroup(p.position) === g),
  }))
  const others = players.filter((p) => !positionGroup(p.position))
  if (others.length) groups.push({ label: 'Sin posición', color: 'grey', players: others })
  return groups.filter((g) => g.players.length)
})
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Equipos', to: { name: 'teams' } }, { title: team?.name ?? 'Equipo' }]" />

  <DataState
    :loading="loading"
    :error="error"
    :empty="!team"
    skeleton="team-detail"
    empty-title="Equipo no encontrado"
    @retry="load"
  >
    <template v-if="team">
      <v-card border flat class="team-hero mb-6">
        <div class="team-hero__banner" />
        <div class="team-hero__layout">
          <div class="team-hero__info">
            <div class="team-hero__main">
              <div class="emblem-tile emblem-tile--xl">
                <img v-if="team.crest" :src="team.crest" :alt="team.name" />
                <v-icon v-else icon="mdi-shield-half-full" size="48" color="grey" />
              </div>
              <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-center ga-2">
                  <h1 class="text-h5 font-weight-bold">{{ team.name }}</h1>
                  <v-chip v-if="team.tla" size="small" label>{{ team.tla }}</v-chip>
                </div>
                <div class="d-flex flex-wrap gc-5 gr-1 text-body-2 text-medium-emphasis mt-1">
                  <span class="d-flex align-center ga-1">
                    <v-icon icon="mdi-stadium-outline" size="18" /> {{ team.venue ?? '—' }}
                  </span>
                  <span class="d-flex align-center ga-1">
                    <v-icon icon="mdi-calendar-outline" size="18" /> {{ team.founded ? `Fundado en ${team.founded}` : '—' }}
                  </span>
                  <span class="d-flex align-center ga-1">
                    <v-icon icon="mdi-account-group-outline" size="18" /> {{ team.players?.length ?? 0 }} jugadores
                  </span>
                </div>
              </div>
            </div>

            <div v-if="team.competitions?.length" class="d-flex flex-wrap align-center ga-2 px-6 pb-3">
              <span class="text-body-2 text-medium-emphasis mr-1">Compite en</span>
              <v-chip
                v-for="c in team.competitions"
                :key="c.id"
                :to="{ name: 'competition', params: { id: c.id } }"
                variant="outlined"
              >
                <template #prepend>
                  <div class="emblem-tile emblem-tile--xs mr-2">
                    <img v-if="c.emblem" :src="c.emblem" :alt="c.name" />
                  </div>
                </template>
                {{ c.name }}
              </v-chip>
            </div>

            <div v-if="squad.length" class="d-flex flex-wrap align-center ga-2 px-6 pb-4">
              <span class="text-body-2 text-medium-emphasis mr-1">Plantilla</span>
              <v-chip v-for="group in squad" :key="group.label" size="small" variant="tonal" :color="group.color">
                {{ group.label }} {{ group.players.length }}
              </v-chip>
            </div>
          </div>

          <!-- el API gratuito no trae alineaciones de partido: la cancha solo ordena la plantilla por línea -->
          <div v-if="squad.length" class="team-hero__pitch">
            <SquadPitch :groups="squad" compact />
          </div>
        </div>
      </v-card>

      <p v-if="!squad.length" class="text-medium-emphasis">Este equipo no tiene jugadores registrados.</p>

      <section v-for="group in squad" :key="group.label" class="mb-6">
        <div class="d-flex align-center ga-2 mb-3">
          <v-icon icon="mdi-circle" size="10" :color="group.color" />
          <h2 class="text-h6">{{ group.label }}</h2>
          <span class="text-body-2 text-medium-emphasis">{{ group.players.length }}</span>
        </div>
        <v-row>
          <v-col v-for="player in group.players" :key="player.id" cols="12" sm="6" md="4" lg="3" xl="2">
            <PlayerCard :player="player" />
          </v-col>
        </v-row>
      </section>
    </template>
  </DataState>
</template>
