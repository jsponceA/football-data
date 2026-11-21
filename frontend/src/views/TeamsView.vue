<script setup lang="ts">
import { getTeams } from '@/api/football'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'

const { items, meta, page, search, loading, error, load } = usePaginatedList(getTeams)
</script>

<template>
  <PageHeader title="Equipos" subtitle="Equipos de las ligas ya importadas">
    <v-text-field
      v-model="search"
      label="Buscar equipo"
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
    :empty-title="search ? 'Sin coincidencias' : 'Todavía no hay equipos'"
    :empty-text="search ? 'Prueba con otro nombre.' : 'Abre una liga en la sección Ligas para importar sus equipos.'"
    empty-icon="mdi-shield-off-outline"
    @retry="load"
  >
    <v-card>
      <v-progress-linear :active="loading" indeterminate />
      <div style="overflow-x: auto">
        <v-table hover>
          <thead>
            <tr>
              <th></th>
              <th>Equipo</th>
              <th>Abrev.</th>
              <th>Estadio</th>
              <th>Fundado</th>
              <th class="text-right">Jugadores</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="team in items"
              :key="team.id"
              style="cursor: pointer"
              @click="$router.push({ name: 'team', params: { id: team.id } })"
            >
              <td width="56">
                <v-avatar rounded="0" size="32">
                  <v-img v-if="team.crest" :src="team.crest" :alt="team.name" contain />
                  <v-icon v-else icon="mdi-shield-half-full" />
                </v-avatar>
              </td>
              <td>{{ team.name }}</td>
              <td>{{ team.tla ?? '—' }}</td>
              <td>{{ team.venue ?? '—' }}</td>
              <td>{{ team.founded ?? '—' }}</td>
              <td class="text-right">{{ team.players_count ?? 0 }}</td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>
</template>
