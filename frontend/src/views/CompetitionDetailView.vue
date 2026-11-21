<script setup lang="ts">
import { computed } from 'vue'
import { getCompetition } from '@/api/football'
import CountryFlag from '@/components/CountryFlag.vue'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import TeamCard from '@/components/TeamCard.vue'
import { usePageTitle } from '@/composables/usePageTitle'
import { useRequest } from '@/composables/useRequest'
import { formatDateTime, timeAgo, typeLabel } from '@/utils/format'

const props = defineProps<{ id: number }>()

// la primera vez que se abre una liga el backend la importa de football-data, puede tardar unos segundos
const { data, loading, error, load } = useRequest(() => getCompetition(props.id))

const competition = computed(() => data.value?.data)
usePageTitle(() => competition.value?.name)

const teams = computed(() => competition.value?.teams ?? [])
const playersTotal = computed(() => teams.value.reduce((sum, t) => sum + (t.players?.length ?? 0), 0))
</script>

<template>
  <PageHeader
    :crumbs="[{ title: 'Ligas', to: { name: 'competitions' } }, { title: competition?.name ?? 'Liga' }]"
  />

  <DataState
    :loading="loading"
    :error="error"
    :empty="!competition"
    skeleton="competition-detail"
    empty-title="Liga no encontrada"
    @retry="load"
  >
    <template v-if="competition">
      <v-alert v-if="data?.meta?.stale" type="warning" variant="tonal" class="mb-4" density="compact">
        El servicio externo no está disponible ahora, se muestran los últimos datos guardados.
      </v-alert>

      <v-card border flat class="team-hero mb-6">
        <div class="team-hero__banner" />
        <div class="team-hero__layout">
          <div class="team-hero__info">
            <div class="team-hero__main">
              <div class="emblem-tile emblem-tile--xl">
                <img v-if="competition.emblem" :src="competition.emblem" :alt="competition.name" />
                <v-icon v-else icon="mdi-trophy" size="48" color="grey" />
              </div>
              <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-center ga-2">
                  <h1 class="text-h5 font-weight-bold">{{ competition.name }}</h1>
                  <v-chip size="small" label>{{ competition.code }}</v-chip>
                  <v-chip size="small" label variant="tonal">{{ typeLabel(competition.type) }}</v-chip>
                </div>
                <div class="d-flex flex-wrap gc-5 gr-1 text-body-2 text-medium-emphasis mt-1">
                  <CountryFlag :name="competition.area" label />
                  <span class="d-flex align-center ga-1" :title="formatDateTime(competition.imported_at)">
                    <v-icon icon="mdi-update" size="18" /> Datos actualizados {{ timeAgo(competition.imported_at) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="hero-stats">
            <div class="hero-stat">
              <div class="hero-stat__value">{{ teams.length }}</div>
              <div class="hero-stat__label">Equipos</div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat__value">{{ playersTotal }}</div>
              <div class="hero-stat__label">Jugadores</div>
            </div>
          </div>
        </div>
      </v-card>

      <h2 class="text-h6 mb-3">Equipos</h2>

      <v-row v-if="teams.length">
        <v-col v-for="team in teams" :key="team.id" cols="12" sm="6" md="4" xl="3">
          <TeamCard :team="team" :show-leagues="false" />
        </v-col>
      </v-row>
      <p v-else class="text-medium-emphasis">Esta liga no tiene equipos registrados.</p>
    </template>
  </DataState>
</template>
