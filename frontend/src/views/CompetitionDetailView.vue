<script setup lang="ts">
import { computed } from 'vue'
import { getCompetition } from '@/api/football'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { usePageTitle } from '@/composables/usePageTitle'
import { useRequest } from '@/composables/useRequest'
import { formatDateTime } from '@/utils/format'

const props = defineProps<{ id: number }>()

// la primera vez que se abre una liga el backend la importa de football-data, puede tardar unos segundos
const { data, loading, error, load } = useRequest(() => getCompetition(props.id))

const competition = computed(() => data.value?.data)
usePageTitle(() => competition.value?.name)

const teams = computed(() => competition.value?.teams ?? [])
</script>

<template>
  <PageHeader
    :crumbs="[{ title: 'Ligas', to: { name: 'competitions' } }, { title: competition?.name ?? 'Liga' }]"
  />

  <DataState
    :loading="loading"
    :error="error"
    :empty="!competition"
    skeleton="detail"
    empty-title="Liga no encontrada"
    @retry="load"
  >
    <template v-if="competition">
      <v-alert v-if="data?.meta?.stale" type="warning" variant="tonal" class="mb-4" density="compact">
        El servicio externo no está disponible ahora, se muestran los últimos datos guardados.
      </v-alert>

      <div class="d-flex flex-wrap align-center ga-4 mb-6">
        <v-avatar rounded="0" size="64">
          <v-img v-if="competition.emblem" :src="competition.emblem" :alt="competition.name" contain />
          <v-icon v-else icon="mdi-trophy" size="40" />
        </v-avatar>
        <div>
          <h1 class="text-h5">{{ competition.name }}</h1>
          <p class="text-body-2 text-medium-emphasis">{{ competition.area ?? '—' }}</p>
        </div>
        <v-spacer />
        <v-chip label>{{ competition.code }}</v-chip>
        <v-chip>Actualizada {{ formatDateTime(competition.imported_at) }}</v-chip>
      </div>

      <h2 class="text-h6 mb-3">Equipos ({{ teams.length }})</h2>

      <v-row v-if="teams.length">
        <v-col v-for="team in teams" :key="team.id" cols="12" sm="6" md="4" lg="3" xl="2">
          <v-card :to="{ name: 'team', params: { id: team.id } }" height="100%">
            <v-card-item>
              <template #prepend>
                <v-avatar rounded="0" size="40">
                  <v-img v-if="team.crest" :src="team.crest" :alt="team.name" contain />
                  <v-icon v-else icon="mdi-shield-half-full" />
                </v-avatar>
              </template>
              <v-card-title class="text-body-1">{{ team.name }}</v-card-title>
              <v-card-subtitle>{{ team.players?.length ?? 0 }} jugadores</v-card-subtitle>
            </v-card-item>
          </v-card>
        </v-col>
      </v-row>
      <p v-else class="text-medium-emphasis">Esta liga no tiene equipos registrados.</p>
    </template>
  </DataState>
</template>
