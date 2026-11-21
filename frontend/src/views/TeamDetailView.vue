<script setup lang="ts">
import { getTeam } from '@/api/football'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { usePageTitle } from '@/composables/usePageTitle'
import { useRequest } from '@/composables/useRequest'
import { formatDate, positionLabel } from '@/utils/format'

const props = defineProps<{ id: number }>()

const { data: team, loading, error, load } = useRequest(() => getTeam(props.id))

usePageTitle(() => team.value?.name)
</script>

<template>
  <v-btn :to="{ name: 'teams' }" variant="text" prepend-icon="mdi-arrow-left" class="mb-2">
    Equipos
  </v-btn>

  <DataState
    :loading="loading"
    :error="error"
    :empty="!team"
    empty-title="Equipo no encontrado"
    @retry="load"
  >
    <template v-if="team">
      <div class="d-flex align-center ga-4 mb-4">
        <v-avatar rounded="0" size="72">
          <v-img v-if="team.crest" :src="team.crest" :alt="team.name" contain />
          <v-icon v-else icon="mdi-shield-half-full" size="48" />
        </v-avatar>
        <PageHeader
          :title="team.name"
          :subtitle="[team.venue, team.founded ? `Fundado en ${team.founded}` : null].filter(Boolean).join(' · ')"
        />
      </div>

      <div v-if="team.competitions?.length" class="mb-4 d-flex flex-wrap ga-2">
        <v-chip v-for="c in team.competitions" :key="c.id" :to="{ name: 'competition', params: { id: c.id } }">
          {{ c.name }}
        </v-chip>
      </div>

      <h2 class="text-h6 mb-3">Plantilla ({{ team.players?.length ?? 0 }})</h2>

      <v-card v-if="team.players?.length">
        <div style="overflow-x: auto">
          <v-table density="comfortable">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Posición</th>
                <th>Nacionalidad</th>
                <th>Nacimiento</th>
                <th class="text-right">N.º</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="player in team.players" :key="player.id">
                <td>{{ player.name }}</td>
                <td>{{ positionLabel(player.position) }}</td>
                <td>{{ player.nationality ?? '—' }}</td>
                <td>{{ formatDate(player.date_of_birth) }}</td>
                <td class="text-right">{{ player.shirt_number ?? '—' }}</td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </v-card>
      <p v-else class="text-medium-emphasis">Este equipo no tiene jugadores registrados.</p>
    </template>
  </DataState>
</template>
