<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue'
</script>

<template>
  <EmptyState
    icon="mdi-shield-lock-outline"
    title="No tienes acceso a esta sección"
    text="Tu rol no incluye el permiso necesario para verla."
  >
    <v-btn :to="{ name: 'home' }" color="primary" variant="tonal">Volver al inicio</v-btn>
  </EmptyState>
</template>
