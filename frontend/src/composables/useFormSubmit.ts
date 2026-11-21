import { ref } from 'vue'
import type { SubmitEventPromise } from 'vuetify'
import { ApiError } from '@/api/http'

// lo común de todo formulario que envía datos al backend: carga, error general y errores por campo
export function useFormSubmit() {
  const loading = ref(false)
  const error = ref('')
  const fieldErrors = ref<Record<string, string[]>>({})

  function reset() {
    error.value = ''
    fieldErrors.value = {}
  }

  // devuelve true si el formulario era válido y la acción terminó bien
  async function handleSubmit(event: SubmitEventPromise, action: () => Promise<void>) {
    const { valid } = await event
    if (!valid) return false

    loading.value = true
    reset()

    try {
      await action()
      return true
    } catch (e) {
      if (e instanceof ApiError) {
        error.value = e.message
        fieldErrors.value = e.errors
      } else {
        error.value = 'Ocurrió un error inesperado.'
      }
      return false
    } finally {
      loading.value = false
    }
  }

  return { loading, error, fieldErrors, handleSubmit, reset }
}
