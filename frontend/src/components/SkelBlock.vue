<script setup lang="ts">
// un bloque gris animado de tamaño libre; con él se dibuja la forma de cada tarjeta mientras carga
withDefaults(defineProps<{ w?: string; h?: string; r?: string }>(), { w: '100%', h: '16px', r: '6px' })
</script>

<template>
  <v-skeleton-loader type="image" class="skel-block" :style="{ '--w': w, '--h': h, '--r': r }" />
</template>
