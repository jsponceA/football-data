import { onBeforeUnmount, onMounted, ref, watch, type Ref } from 'vue'
import type { Paginated, PaginationMeta } from '@/types/api'
import { errorMessage } from '@/utils/format'

export const PER_PAGE = 15

interface Params {
  page: number
  per_page: number
  search?: string
}

interface Options {
  // si cambia algo externo (un filtro) se vuelve a la página 1
  filters?: () => unknown
}

export function usePaginatedList<T>(
  fetcher: (params: Params) => Promise<Paginated<T>>,
  options: Options = {},
) {
  const items = ref([]) as Ref<T[]>
  const meta = ref<PaginationMeta | null>(null)
  const page = ref(1)
  const search = ref('')
  const loading = ref(false)
  const error = ref('')

  // si una respuesta lenta llega después de otra más nueva, se descarta
  let lastRequest = 0
  let timer: ReturnType<typeof setTimeout> | undefined

  async function load() {
    const request = ++lastRequest
    loading.value = true
    error.value = ''

    try {
      const result = await fetcher({
        page: page.value,
        per_page: PER_PAGE,
        search: search.value.trim() || undefined,
      })
      if (request !== lastRequest) return
      items.value = result.data
      meta.value = result.meta
    } catch (e) {
      if (request !== lastRequest) return
      error.value = errorMessage(e)
    } finally {
      if (request === lastRequest) loading.value = false
    }
  }

  function backToFirstPage() {
    if (page.value === 1) {
      load()
    } else {
      page.value = 1
    }
  }

  watch(page, load)

  // espera a que termine de escribir para no pedir en cada tecla
  watch(search, () => {
    clearTimeout(timer)
    timer = setTimeout(backToFirstPage, 400)
  })

  if (options.filters) {
    watch(options.filters, backToFirstPage)
  }

  onMounted(load)
  onBeforeUnmount(() => clearTimeout(timer))

  return { items, meta, page, search, loading, error, load }
}
