<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps<{ url?: string | null }>()

// el archivo elegido (aún sin subir) y si se pidió quitar la foto que ya existe
const file = defineModel<File | null>('file', { default: null })
const removed = defineModel<boolean>('removed', { default: false })

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp']
const MAX_BYTES = 2 * 1024 * 1024

const input = ref<HTMLInputElement | null>(null)
const dragging = ref(false)
const problem = ref('')
const preview = ref<string | null>(null)

watch(
  file,
  (value) => {
    if (preview.value) URL.revokeObjectURL(preview.value)
    preview.value = value ? URL.createObjectURL(value) : null
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  if (preview.value) URL.revokeObjectURL(preview.value)
})

const shown = computed(() => preview.value ?? (removed.value ? null : (props.url ?? null)))

function pick(candidate: File | undefined) {
  problem.value = ''
  if (!candidate) return

  if (!ACCEPTED.includes(candidate.type)) {
    problem.value = 'Usa una imagen JPG, PNG o WEBP.'
  } else if (candidate.size > MAX_BYTES) {
    problem.value = 'La imagen pesa más de 2 MB.'
  } else {
    file.value = candidate
    removed.value = false
  }
}

function onInput(event: Event) {
  const target = event.target as HTMLInputElement
  pick(target.files?.[0])
  // sin esto, volver a elegir el mismo archivo no dispara el evento
  target.value = ''
}

function onDrop(event: DragEvent) {
  dragging.value = false
  pick(event.dataTransfer?.files[0])
}

function clear() {
  problem.value = ''
  file.value = null
  // solo hay algo que quitar en el servidor si ya había una foto guardada
  removed.value = !!props.url
}
</script>

<template>
  <div class="photo-upload">
    <div
      class="photo-upload__zone"
      :class="{ 'photo-upload__zone--drag': dragging, 'photo-upload__zone--error': problem }"
      role="button"
      tabindex="0"
      aria-label="Cargar foto de perfil"
      @click="input?.click()"
      @keydown.enter.prevent="input?.click()"
      @keydown.space.prevent="input?.click()"
      @dragover.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
    >
      <v-img v-if="shown" :src="shown" cover class="photo-upload__img" alt="Foto de perfil" />
      <div v-else class="photo-upload__empty">
        <v-icon icon="mdi-tray-arrow-up" size="32" />
        <span>Cargar imagen o arrastrar aquí</span>
      </div>
    </div>

    <input ref="input" type="file" accept="image/jpeg,image/png,image/webp" hidden @change="onInput" />

    <div class="text-caption text-medium-emphasis mt-2">JPG, PNG o WEBP. Máx. 2 MB</div>
    <div v-if="problem" class="text-caption text-error mt-1">{{ problem }}</div>

    <div class="d-flex ga-2 mt-2">
      <v-btn size="small" variant="outlined" prepend-icon="mdi-upload" @click="input?.click()">Seleccionar</v-btn>
      <v-btn v-if="shown" size="small" variant="text" color="error" prepend-icon="mdi-delete-outline" @click="clear">
        Quitar
      </v-btn>
    </div>
  </div>
</template>
