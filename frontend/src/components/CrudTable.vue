<script setup lang="ts">
defineProps<{ loading?: boolean }>()
</script>

<template>
  <v-card border flat>
    <v-progress-linear :active="loading" indeterminate />
    <!-- el alto tiene tope (ver crud.css): con pocas filas la tabla se encoge y con muchas hace scroll por dentro -->
    <v-table class="crud-table" fixed-header>
      <slot />
    </v-table>
  </v-card>
</template>
