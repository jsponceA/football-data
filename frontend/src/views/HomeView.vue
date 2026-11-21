<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue'
import PageHeader from '@/components/PageHeader.vue'
</script>

<template>
  <PageHeader :crumbs="[]" />

  <!-- solo se llega aquí si el usuario no tiene permiso para ninguna sección -->
  <EmptyState
    icon="mdi-lock-outline"
    title="Sin secciones disponibles"
    text="Tu usuario no tiene permisos asignados todavía. Pídele a un administrador que te asigne un rol."
  />
</template>
