<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue'
import PageHeader from '@/components/PageHeader.vue'
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Página no encontrada' }]" />

  <EmptyState
    icon="mdi-map-marker-question-outline"
    title="Página no encontrada"
    text="La dirección que abriste no existe."
  >
    <v-btn :to="{ name: 'home' }" color="primary" variant="tonal">Volver al inicio</v-btn>
  </EmptyState>
</template>
