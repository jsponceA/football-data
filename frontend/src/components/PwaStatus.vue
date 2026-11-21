<script setup lang="ts">
import { computed } from 'vue'
import { useRegisterSW } from 'virtual:pwa-register/vue'
import { useOnline } from '@/composables/useOnline'

const REVISAR_CADA_MS = 60 * 60 * 1000

// needRefresh pasa a true cuando el service worker descargó una versión nueva y está esperando
const { needRefresh, updateServiceWorker } = useRegisterSW({
  onRegisteredSW(_url, registration) {
    // una app instalada puede quedarse abierta días: se busca versión nueva cada hora
    if (!registration) return
    setInterval(() => {
      if (navigator.onLine) void registration.update()
    }, REVISAR_CADA_MS)
  },
})

const { online } = useOnline()

// v-model de solo lectura para el aviso de sin conexión: aparece y desaparece solo con la red
const sinConexion = computed({ get: () => !online.value, set: () => {} })

const actualizar = () => updateServiceWorker(true)
const despues = () => {
  needRefresh.value = false
}
</script>

<template>
  <v-snackbar v-model="sinConexion" color="warning" :timeout="-1" location="top" data-test="offline-notice">
    <v-icon icon="mdi-wifi-off" class="mr-2" />
    Sin conexión. Verás lo ya cargado, pero los datos no se actualizarán hasta que vuelva la red.
  </v-snackbar>

  <v-snackbar v-model="needRefresh" color="primary" :timeout="-1" multi-line data-test="update-notice">
    Hay una versión nueva de Football Data.
    <template #actions>
      <v-btn variant="text" data-test="update-later" @click="despues">Después</v-btn>
      <v-btn variant="tonal" data-test="update-now" @click="actualizar">Actualizar</v-btn>
    </template>
  </v-snackbar>
</template>
