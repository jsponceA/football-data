<script setup lang="ts">
import { computed } from 'vue'
import { getCompetitions } from '@/api/football'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { formatDateTime } from '@/utils/format'

const { items, meta, page, loading, error, load } = usePaginatedList(getCompetitions)

const stale = computed(() => meta.value?.stale === true)
</script>

<template>
  <PageHeader title="Ligas" subtitle="Competiciones disponibles en el plan gratuito de football-data.org" />

  <v-alert v-if="stale" type="warning" variant="tonal" class="mb-4" density="compact">
    El servicio externo no está disponible ahora, se muestran los últimos datos guardados.
  </v-alert>

  <DataState
    :loading="loading"
    :error="error"
    :empty="items.length === 0"
    empty-title="No hay ligas"
    empty-text="Todavía no se pudo cargar el listado de ligas."
    empty-icon="mdi-trophy-outline"
    @retry="load"
  >
    <v-progress-linear :active="loading" indeterminate class="mb-2" />
    <v-row>
      <v-col v-for="competition in items" :key="competition.id" cols="12" sm="6" md="4">
        <v-card :to="{ name: 'competition', params: { id: competition.id } }" height="100%">
          <v-card-item>
            <template #prepend>
              <v-avatar rounded="0" size="48">
                <v-img v-if="competition.emblem" :src="competition.emblem" :alt="competition.name" contain />
                <v-icon v-else icon="mdi-trophy" />
              </v-avatar>
            </template>
            <v-card-title>{{ competition.name }}</v-card-title>
            <v-card-subtitle>{{ competition.area ?? '—' }}</v-card-subtitle>
          </v-card-item>
          <v-card-text class="d-flex flex-wrap align-center ga-2">
            <v-chip size="small" label>{{ competition.code }}</v-chip>
            <v-chip
              size="small"
              :color="competition.imported_at ? 'success' : undefined"
              :prepend-icon="competition.imported_at ? 'mdi-check' : 'mdi-cloud-download-outline'"
            >
              {{ competition.imported_at ? `Importada ${formatDateTime(competition.imported_at)}` : 'Sin importar' }}
            </v-chip>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>
</template>
