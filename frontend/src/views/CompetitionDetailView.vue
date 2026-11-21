<script setup lang="ts">
import { computed } from 'vue'
import { getCompetition } from '@/api/football'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useRequest } from '@/composables/useRequest'
import { formatDateTime } from '@/utils/format'

const props = defineProps<{ id: number }>()

// la primera vez que se abre una liga el backend la importa de football-data, puede tardar unos segundos
const { data, loading, error, load } = useRequest(() => getCompetition(props.id))

const competition = computed(() => data.value?.data)
const teams = computed(() => competition.value?.teams ?? [])
</script>

<template>
  <v-btn :to="{ name: 'competitions' }" variant="text" prepend-icon="mdi-arrow-left" class="mb-2">
    Ligas
  </v-btn>

  <DataState
    :loading="loading"
    :error="error"
    :empty="!competition"
    empty-title="Liga no encontrada"
    @retry="load"
  >
    <template v-if="competition">
      <v-alert v-if="data?.meta?.stale" type="warning" variant="tonal" class="mb-4" density="compact">
        El servicio externo no está disponible ahora, se muestran los últimos datos guardados.
      </v-alert>

      <PageHeader :title="competition.name" :subtitle="competition.area ?? undefined">
        <v-chip label>{{ competition.code }}</v-chip>
        <v-chip>Actualizada {{ formatDateTime(competition.imported_at) }}</v-chip>
      </PageHeader>

      <h2 class="text-h6 mb-3">Equipos ({{ teams.length }})</h2>

      <v-row v-if="teams.length">
        <v-col v-for="team in teams" :key="team.id" cols="12" sm="6" md="4" lg="3">
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
