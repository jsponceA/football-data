<script setup lang="ts">
import { getPlayers } from '@/api/football'
import DataState from '@/components/DataState.vue'
import FilterBar from '@/components/FilterBar.vue'
import FilterField from '@/components/FilterField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PlayerCard from '@/components/PlayerCard.vue'
import PerPageSelect from '@/components/PerPageSelect.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'

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
    :empty-text="filtered ? 'Prueba con otro nombre.' : 'Abre una liga en la sección Ligas para cargar sus jugadores.'"
    empty-icon="mdi-account-off-outline"
    skeleton="players"
    @retry="load"
  >
    <div class="mb-3">
      <PerPageSelect v-model="perPage" />
    </div>

    <v-progress-linear :active="loading" indeterminate class="mb-2" />
    <v-row>
      <v-col v-for="player in items" :key="player.id" cols="12" sm="6" md="4" xl="3">
        <PlayerCard :player="player" />
      </v-col>
    </v-row>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>
</template>
