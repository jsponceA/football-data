<script setup lang="ts">
import { computed } from 'vue'
import { getCompetitions } from '@/api/football'
import CountryFlag from '@/components/CountryFlag.vue'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { formatDateTime, isFreePlan, timeAgo, typeLabel } from '@/utils/format'

const { items, meta, page, loading, error, load } = usePaginatedList(getCompetitions)

const stale = computed(() => meta.value?.stale === true)
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Ligas' }]" />

  <v-alert v-if="stale" type="warning" variant="tonal" class="mb-4" density="compact">
    El servicio externo no está disponible ahora, se muestran los últimos datos guardados.
  </v-alert>

  <DataState
    :loading="loading"
    :error="error"
    :empty="items.length === 0"
    skeleton="competitions"
    empty-title="No hay ligas"
    empty-text="Todavía no se pudo cargar el listado de ligas."
    empty-icon="mdi-trophy-outline"
    @retry="load"
  >
    <v-progress-linear :active="loading" indeterminate class="mb-2" />
    <v-row>
      <v-col v-for="competition in items" :key="competition.id" cols="12" sm="6" md="4" xl="3">
        <!-- las ligas de pago no se pueden abrir: el servicio gratuito las rechaza, así que no se ofrece la entrada -->
        <v-card
          :to="isFreePlan(competition.plan) ? { name: 'competition', params: { id: competition.id } } : undefined"
          border
          flat
          class="competition-card"
          :class="{ 'competition-card--locked': !isFreePlan(competition.plan) }"
          height="100%"
          :title="isFreePlan(competition.plan) ? undefined : 'No está disponible con el plan gratuito'"
        >
          <div class="d-flex align-center ga-4 pa-4">
            <div class="emblem-tile">
              <img v-if="competition.emblem" :src="competition.emblem" :alt="competition.name" />
              <v-icon v-else icon="mdi-trophy" color="grey" />
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <div class="text-subtitle-1 font-weight-medium text-truncate" :title="competition.name">
                {{ competition.name }}
              </div>
              <div class="text-body-2 text-medium-emphasis text-truncate">
                <CountryFlag :name="competition.area" label />
              </div>
            </div>
          </div>
          <v-divider />
          <div class="d-flex align-center ga-2 px-4 py-2 text-caption">
            <v-chip size="x-small" label>{{ competition.code }}</v-chip>
            <v-chip size="x-small" label variant="tonal">{{ typeLabel(competition.type) }}</v-chip>
            <v-spacer />
            <span v-if="!isFreePlan(competition.plan)" class="d-flex align-center ga-1 text-medium-emphasis">
              <v-icon icon="mdi-lock-outline" size="16" />
              Plan de pago
            </span>
            <span
              v-else-if="competition.imported_at"
              class="d-flex align-center ga-1 text-success"
              :title="`Datos actualizados el ${formatDateTime(competition.imported_at)}`"
            >
              <v-icon icon="mdi-check-circle-outline" size="16" />
              Actualizada {{ timeAgo(competition.imported_at) }}
            </span>
            <span
              v-else
              class="d-flex align-center ga-1 text-medium-emphasis"
              title="Los equipos y jugadores se descargan al abrir la liga"
            >
              <v-icon icon="mdi-cloud-download-outline" size="16" />
              Sin datos aún
            </span>
          </div>
        </v-card>
      </v-col>
    </v-row>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>
</template>
