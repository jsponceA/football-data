<script setup lang="ts">
import { getPlayers } from '@/api/football'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { formatDate, positionLabel } from '@/utils/format'

const { items, meta, page, search, loading, error, load } = usePaginatedList(getPlayers)
</script>

<template>
  <PageHeader title="Jugadores" subtitle="Jugadores de los equipos ya importados">
    <v-text-field
      v-model="search"
      label="Buscar jugador"
      prepend-inner-icon="mdi-magnify"
      density="compact"
      variant="outlined"
      hide-details
      clearable
      style="min-width: 240px"
    />
  </PageHeader>

  <DataState
    :loading="loading"
    :error="error"
    :empty="items.length === 0"
    :empty-title="search ? 'Sin coincidencias' : 'Todavía no hay jugadores'"
    :empty-text="search ? 'Prueba con otro nombre.' : 'Abre una liga en la sección Ligas para importar sus jugadores.'"
    empty-icon="mdi-account-off-outline"
    @retry="load"
  >
    <v-card>
      <v-progress-linear :active="loading" indeterminate />
      <div style="overflow-x: auto">
        <v-table hover>
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
              <td>{{ player.name }}</td>
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
