<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAbility } from '@casl/vue'
import { getCompetitions, getTeams } from '@/api/football'
import DataState from '@/components/DataState.vue'
import FilterBar from '@/components/FilterBar.vue'
import FilterField from '@/components/FilterField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PerPageSelect from '@/components/PerPageSelect.vue'
import TeamCard from '@/components/TeamCard.vue'
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
    :empty-text="filtered ? 'Prueba con otros filtros.' : 'Abre una liga en la sección Ligas para cargar sus equipos.'"
    empty-icon="mdi-shield-off-outline"
    skeleton="teams"
    @retry="load"
  >
    <div class="mb-3">
      <PerPageSelect v-model="perPage" />
    </div>

    <v-progress-linear :active="loading" indeterminate class="mb-2" />
    <v-row>
      <v-col v-for="team in items" :key="team.id" cols="12" sm="6" md="4" xl="3">
        <TeamCard :team="team" />
      </v-col>
    </v-row>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>
</template>
