<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { loadTurnstile, turnstileEnabled, turnstileSiteKey } from '@/utils/turnstile'

// captcha de Cloudflare. Sin clave del sitio no dibuja nada, así el formulario funciona igual en local.
const token = defineModel<string>({ default: '' })

const box = ref<HTMLElement | null>(null)
let widgetId: string | undefined

onMounted(async () => {
  if (!turnstileEnabled || !box.value) return

  try {
    const turnstile = await loadTurnstile()
    // la persona pudo salir de la página mientras cargaba el script
    if (!box.value) return

    widgetId = turnstile.render(box.value, {
      sitekey: turnstileSiteKey,
      theme: 'auto',
      callback: (value: string) => (token.value = value),
      // el token vence a los 5 minutos y no se puede reutilizar: se pide otro
      'expired-callback': () => (token.value = ''),
      'error-callback': () => (token.value = ''),
    })
  } catch {
    // sin red a Cloudflare no hay widget; el backend responderá que falta la verificación
  }
})

onBeforeUnmount(() => {
  if (widgetId !== undefined) window.turnstile?.remove(widgetId)
})

// cada token sirve una sola vez: tras un envío fallido hay que pedir uno nuevo
function reset() {
  token.value = ''
  if (widgetId !== undefined) window.turnstile?.reset(widgetId)
}

defineExpose({ reset })
</script>

<template>
  <div v-if="turnstileEnabled" ref="box" class="turnstile-box mb-4" />
</template>

<style scoped>
.turnstile-box {
  display: flex;
  justify-content: center;
  min-height: 65px;
}
</style>
