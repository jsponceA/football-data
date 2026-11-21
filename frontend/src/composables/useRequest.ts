import { onMounted, ref, type Ref } from 'vue'
import { errorMessage } from '@/utils/format'

// para pantallas de detalle: una sola petición con estados de carga y error
export function useRequest<T>(fn: () => Promise<T>) {
  const data = ref<T | null>(null) as Ref<T | null>
  const loading = ref(false)
  const error = ref('')

  async function load() {
    loading.value = true
    error.value = ''
    try {
      data.value = await fn()
    } catch (e) {
      error.value = errorMessage(e)
    } finally {
      loading.value = false
    }
  }

  onMounted(load)

  return { data, loading, error, load }
}
