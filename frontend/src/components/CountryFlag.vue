<script setup lang="ts">
import { computed } from 'vue'
import { countryCode } from '@/utils/countries'

// muestra la bandera y, si se pide, el nombre al lado; sin bandera conocida queda un icono genérico
const props = defineProps<{ name: string | null | undefined; label?: boolean }>()

const code = computed(() => countryCode(props.name))
</script>

<template>
  <span class="d-inline-flex align-center ga-2">
    <span v-if="code" :class="`fi fi-${code} country-flag`" :title="name ?? undefined" role="img" :aria-label="name ?? undefined" />
    <v-icon v-else icon="mdi-earth" size="16" :title="name ?? undefined" />
    <span v-if="label">{{ name ?? '—' }}</span>
  </span>
</template>
