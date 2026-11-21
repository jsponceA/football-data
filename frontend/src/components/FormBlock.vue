<script setup lang="ts">
defineProps<{ title: string; icon?: string }>()
</script>

<template>
  <section class="form-block">
    <div class="form-block__head">
      <h2 class="form-block__title">
        <v-icon v-if="icon" :icon="icon" size="20" />
        {{ title }}
      </h2>
      <slot name="append" />
    </div>
    <slot />
  </section>
</template>
