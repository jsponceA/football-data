<script setup lang="ts">
// etiqueta arriba y el control debajo, como en los filtros; el control va en el slot
defineProps<{ label: string; required?: boolean }>()
</script>

<template>
  <div class="form-field">
    <span class="form-label">
      {{ label }}<span v-if="required" class="form-label__req" aria-hidden="true">*</span>
    </span>
    <slot />
  </div>
</template>
