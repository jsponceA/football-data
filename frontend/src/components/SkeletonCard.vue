<script setup lang="ts">
import SkelBlock from '@/components/SkelBlock.vue'

// cada tipo copia la estructura de la tarjeta real para que no salte el diseño al cargar
defineProps<{ kind: 'competition' | 'team' | 'player' }>()
</script>

<template>
  <v-card border flat height="100%">
    <template v-if="kind === 'competition'">
      <div class="d-flex align-center ga-4 pa-4">
        <SkelBlock w="56px" h="56px" r="12px" />
        <div class="flex-grow-1 d-flex flex-column ga-2">
          <SkelBlock w="70%" h="18px" />
          <SkelBlock w="40%" h="14px" />
        </div>
      </div>
      <v-divider />
      <div class="d-flex align-center ga-2 px-4 py-2">
        <SkelBlock w="40px" h="18px" />
        <SkelBlock w="48px" h="18px" />
        <v-spacer />
        <SkelBlock w="110px" h="14px" />
      </div>
    </template>

    <template v-else>
      <SkelBlock h="64px" r="0" />
      <div class="entity-card__body">
        <SkelBlock
          w="72px"
          h="72px"
          :r="kind === 'player' ? '50%' : '16px'"
          class="skel-overlap"
        />
        <div class="d-flex flex-column ga-2 mt-3">
          <SkelBlock w="65%" h="18px" />
          <SkelBlock :w="kind === 'player' ? '70px' : '50%'" :h="kind === 'player' ? '18px' : '14px'" />
          <SkelBlock w="55%" h="14px" />
        </div>
      </div>
      <v-divider />
      <div class="d-flex align-center ga-2 px-4 py-2">
        <SkelBlock w="28px" h="28px" r="8px" />
        <SkelBlock v-if="kind === 'player'" w="45%" h="14px" />
        <v-spacer />
        <SkelBlock v-if="kind === 'player'" w="28px" h="28px" r="8px" />
      </div>
    </template>
  </v-card>
</template>
