<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue'
import PageHeader from '@/components/PageHeader.vue'
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Sin acceso' }]" />

  <EmptyState
    icon="mdi-shield-lock-outline"
    title="No tienes acceso a esta sección"
    text="Tu rol no incluye el permiso necesario para verla."
  >
    <v-btn :to="{ name: 'home' }" color="primary" variant="tonal">Volver al inicio</v-btn>
  </EmptyState>
</template>
