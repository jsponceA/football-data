<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { getTeams } from '@/api/football'

interface Option {
  id: number
  name: string
}

const model = defineModel<number | null>({ required: true })

const results = ref<Option[]>([])
const selected = ref<Option | null>(null)
const search = ref('')
const loading = ref(false)

// hay cientos de equipos, así que no se cargan todos: se busca en el servidor mientras se escribe
const options = computed(() => {
  const list = [...results.value]
  if (selected.value && !list.some((o) => o.id === selected.value?.id)) list.unshift(selected.value)
  return list
})

async function fetchTeams(term: string) {
  loading.value = true
  try {
    const result = await getTeams({ search: term || undefined, per_page: 20 })
    results.value = result.data.map((t) => ({ id: t.id, name: t.name }))
  } catch {
    // si falla la búsqueda el campo queda sin opciones y el resto del filtro sigue sirviendo
  } finally {
    loading.value = false
  }
}

let timer: ReturnType<typeof setTimeout> | undefined

watch(search, (term) => {
  // al elegir una opción el texto pasa a ser su nombre: eso no es una búsqueda nueva
  if (selected.value && term === selected.value.name) return
  clearTimeout(timer)
  timer = setTimeout(() => fetchTeams(term ?? ''), 300)
})

watch(model, (id) => {
  if (id === null) selected.value = null
  else selected.value = options.value.find((o) => o.id === id) ?? selected.value
})

onMounted(() => fetchTeams(''))
onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <v-autocomplete
    v-model="model"
    v-model:search="search"
    :items="options"
    item-title="name"
    item-value="id"
    :loading="loading"
    placeholder="Todos los equipos"
    no-data-text="Sin equipos"
    no-filter
    clearable
    variant="outlined"
    density="compact"
    hide-details
  />
</template>
