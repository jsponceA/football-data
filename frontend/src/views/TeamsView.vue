<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAbility } from '@casl/vue'
import { getCompetitions, getTeams } from '@/api/football'
import CrudTable from '@/components/CrudTable.vue'
import DataState from '@/components/DataState.vue'
import FilterBar from '@/components/FilterBar.vue'
import FilterField from '@/components/FilterField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PerPageSelect from '@/components/PerPageSelect.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import type { AppAbility } from '@/plugins/ability'

const { items, meta, page, perPage, filters, filtered, loading, error, load, apply, clear } =
  usePaginatedList(getTeams, { search: '', competition: null as number | null })

const { can } = useAbility<AppAbility>()
const leagues = ref<{ title: string; value: number }[]>([])

// el filtro por liga necesita el listado de ligas, que solo ven quienes tienen ese permiso
onMounted(async () => {
  if (!can('view', 'competitions')) return
  try {
    const result = await getCompetitions({ per_page: 100 })
    leagues.value = result.data.map((c) => ({ title: c.name, value: c.id }))
  } catch {
    // sin ligas el filtro queda vacío, el resto de la pantalla sigue funcionando
  }
})
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Equipos' }]" />

  <FilterBar @apply="apply" @clear="clear">
    <FilterField label="Buscar" wide>
      <v-text-field
        v-model="filters.search"
        placeholder="Nombre del equipo…"
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        hide-details
      />
    </FilterField>
    <FilterField v-if="leagues.length" label="Liga">
      <v-select
        v-model="filters.competition"
        :items="leagues"
        placeholder="Todas las ligas"
        variant="outlined"
        density="compact"
        hide-details
        clearable
      />
    </FilterField>
  </FilterBar>

  <DataState
    :loading="loading"
    :error="error"
    :empty="items.length === 0"
    :empty-title="filtered ? 'Sin coincidencias' : 'Todavía no hay equipos'"
    :empty-text="filtered ? 'Prueba con otros filtros.' : 'Abre una liga en la sección Ligas para importar sus equipos.'"
    empty-icon="mdi-shield-off-outline"
    @retry="load"
  >
    <div class="mb-3">
      <PerPageSelect v-model="perPage" />
    </div>

    <CrudTable :loading="loading">
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Equipo</th>
          <th>Abrev.</th>
          <th>Estadio</th>
          <th>Fundado</th>
          <th class="text-right">Jugadores</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="team in items" :key="team.id">
          <td>
            <div class="row-actions">
              <v-btn
                icon="mdi-eye-outline"
                size="x-small"
                variant="tonal"
                color="info"
                rounded="lg"
                :to="{ name: 'team', params: { id: team.id } }"
                :aria-label="`Ver ${team.name}`"
              />
            </div>
          </td>
          <td class="text-no-wrap">
            <v-avatar rounded="0" size="28" class="mr-2">
              <v-img v-if="team.crest" :src="team.crest" :alt="team.name" contain />
              <v-icon v-else icon="mdi-shield-half-full" />
            </v-avatar>
            {{ team.name }}
          </td>
          <td>{{ team.tla ?? '—' }}</td>
          <td>{{ team.venue ?? '—' }}</td>
          <td>{{ team.founded ?? '—' }}</td>
          <td class="text-right">{{ team.players_count ?? 0 }}</td>
        </tr>
      </tbody>
    </CrudTable>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>
</template>
