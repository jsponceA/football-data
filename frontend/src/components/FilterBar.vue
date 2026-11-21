<script setup lang="ts">
defineEmits<{ apply: []; clear: [] }>()
</script>

<template>
  <!-- es un form para que Enter en cualquier campo también aplique los filtros -->
  <v-form class="filter-bar mb-3" @submit.prevent="$emit('apply')">
    <slot />
    <div class="d-flex ga-2">
      <v-btn type="submit" color="primary" prepend-icon="mdi-filter-variant">Filtrar</v-btn>
      <v-btn variant="outlined" prepend-icon="mdi-close" @click="$emit('clear')">Limpiar</v-btn>
    </div>
  </v-form>
</template>
