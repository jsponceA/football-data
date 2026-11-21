<script setup lang="ts">
import { getPlayers } from '@/api/football'
import DataState from '@/components/DataState.vue'
import FilterBar from '@/components/FilterBar.vue'
import FilterField from '@/components/FilterField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PerPageSelect from '@/components/PerPageSelect.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { formatDate, positionLabel } from '@/utils/format'

const { items, meta, page, perPage, filters, filtered, loading, error, load, apply, clear } =
  usePaginatedList(getPlayers, { search: '' })
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Jugadores' }]" />

  <FilterBar @apply="apply" @clear="clear">
    <FilterField label="Buscar" wide>
      <v-text-field
        v-model="filters.search"
        placeholder="Nombre del jugador…"
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        hide-details
      />
    </FilterField>
  </FilterBar>

  <DataState
    :loading="loading"
    :error="error"
    :empty="items.length === 0"
    :empty-title="filtered ? 'Sin coincidencias' : 'Todavía no hay jugadores'"
    :empty-text="filtered ? 'Prueba con otro nombre.' : 'Abre una liga en la sección Ligas para importar sus jugadores.'"
    empty-icon="mdi-account-off-outline"
    @retry="load"
  >
    <div class="mb-3">
      <PerPageSelect v-model="perPage" />
    </div>

    <v-card border flat>
      <v-progress-linear :active="loading" indeterminate />
      <div style="overflow-x: auto">
        <v-table class="crud-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Equipo</th>
              <th>Posición</th>
              <th>Nacionalidad</th>
              <th>Nacimiento</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="player in items" :key="player.id">
              <td class="text-no-wrap">{{ player.name }}</td>
              <td>{{ player.team?.name ?? '—' }}</td>
              <td>{{ positionLabel(player.position) }}</td>
              <td>{{ player.nationality ?? '—' }}</td>
              <td>{{ formatDate(player.date_of_birth) }}</td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>
</template>
