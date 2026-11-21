<script setup lang="ts">
import EmptyState from './EmptyState.vue'
import SkelBlock from './SkelBlock.vue'
import SkeletonCard from './SkeletonCard.vue'

withDefaults(
  defineProps<{
    loading: boolean
    error: string
    empty: boolean
    emptyTitle?: string
    emptyText?: string
    emptyIcon?: string
    // forma del esqueleto mientras carga por primera vez: debe parecerse a lo que va a aparecer
    skeleton?: 'table' | 'competitions' | 'teams' | 'players' | 'competition-detail' | 'team-detail'
  }>(),
  { emptyTitle: 'No hay resultados', emptyText: '', emptyIcon: 'mdi-database-off-outline', skeleton: 'table' },
)

defineEmits<{ retry: [] }>()
</script>

<template>
  <v-alert v-if="error" type="error" variant="tonal" class="my-4">
    {{ error }}
    <template #append>
      <v-btn variant="text" size="small" @click="$emit('retry')">Reintentar</v-btn>
    </template>
  </v-alert>

  <div v-else-if="loading && empty" role="status" aria-label="Cargando">
    <v-skeleton-loader v-if="skeleton === 'table'" type="table-thead, table-row@8" border />

    <v-row v-else-if="skeleton === 'competitions'">
      <v-col v-for="n in 12" :key="n" cols="12" sm="6" md="4" xl="3">
        <SkeletonCard kind="competition" />
      </v-col>
    </v-row>

    <v-row v-else-if="skeleton === 'teams'">
      <v-col v-for="n in 8" :key="n" cols="12" sm="6" md="4" xl="3">
        <SkeletonCard kind="team" />
      </v-col>
    </v-row>

    <v-row v-else-if="skeleton === 'players'">
      <v-col v-for="n in 8" :key="n" cols="12" sm="6" md="4" xl="3">
        <SkeletonCard kind="player" />
      </v-col>
    </v-row>

    <template v-else-if="skeleton === 'competition-detail'">
      <v-card border flat class="mb-6">
        <SkelBlock h="96px" r="0" />
        <div class="team-hero__layout">
          <div class="team-hero__info">
            <div class="team-hero__main">
              <SkelBlock w="112px" h="112px" r="20px" class="skel-overlap" />
              <div class="d-flex flex-column ga-2 flex-grow-1">
                <SkelBlock w="260px" h="26px" />
                <SkelBlock w="45%" h="14px" />
              </div>
            </div>
          </div>
          <div class="hero-stats">
            <SkelBlock w="72px" h="44px" />
            <SkelBlock w="72px" h="44px" />
          </div>
        </div>
      </v-card>
      <SkelBlock w="90px" h="22px" class="mb-3" />
      <v-row>
        <v-col v-for="n in 8" :key="n" cols="12" sm="6" md="4" xl="3">
          <SkeletonCard kind="team" />
        </v-col>
      </v-row>
    </template>

    <template v-else>
      <!-- team-detail: cabecera con la cancha a la derecha y debajo las tarjetas de jugadores -->
      <v-card border flat class="mb-6">
        <SkelBlock h="96px" r="0" />
        <div class="team-hero__layout">
          <div class="team-hero__info">
            <div class="team-hero__main">
              <SkelBlock w="112px" h="112px" r="20px" class="skel-overlap" />
              <div class="d-flex flex-column ga-2 flex-grow-1">
                <SkelBlock w="240px" h="26px" />
                <SkelBlock w="60%" h="14px" />
              </div>
            </div>
            <div class="d-flex flex-wrap ga-2 px-6 pb-3">
              <SkelBlock w="90px" h="24px" />
              <SkelBlock w="140px" h="32px" r="16px" />
            </div>
            <div class="d-flex flex-wrap ga-2 px-6 pb-4">
              <SkelBlock w="70px" h="14px" />
              <SkelBlock v-for="n in 4" :key="n" w="96px" h="22px" r="11px" />
            </div>
          </div>
          <div class="team-hero__pitch">
            <SkelBlock h="210px" r="10px" />
          </div>
        </div>
      </v-card>
      <SkelBlock w="120px" h="22px" class="mb-3" />
      <v-row>
        <v-col v-for="n in 6" :key="n" cols="12" sm="6" md="4" lg="3" xl="2">
          <SkeletonCard kind="player" />
        </v-col>
      </v-row>
    </template>
  </div>

  <EmptyState v-else-if="empty" :icon="emptyIcon" :title="emptyTitle" :text="emptyText" />

  <slot v-else />
</template>
