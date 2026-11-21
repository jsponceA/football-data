import { ref } from 'vue'
import { ApiError } from '@/api/http'
import type TurnstileWidget from '@/components/TurnstileWidget.vue'
import { turnstileEnabled } from '@/utils/turnstile'

// lo común de los formularios con captcha: el token, el widget y cuándo hay que renovarlo
export function useCaptcha() {
  const token = ref('')
  const widget = ref<InstanceType<typeof TurnstileWidget> | null>(null)

  // tras un envío fallido el widget tarda unos segundos en dar un token nuevo;
  // si la persona pulsa antes, se avisa aquí en vez de mandar una petición que el backend rechazaría
  function require() {
    if (turnstileEnabled && !token.value) {
      throw new ApiError(0, 'Espera a que termine la verificación e inténtalo de nuevo.')
    }
  }

  // cada token sirve una sola vez: solo se renueva si llegó a usarse en una petición
  function renew() {
    widget.value?.reset()
  }

  return { token, widget, require, renew }
}
