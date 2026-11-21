import { computed, onMounted, reactive, ref, watch, type Ref } from 'vue'
import type { Paginated, PaginationMeta } from '@/types/api'
import { errorMessage } from '@/utils/format'

export const PER_PAGE_OPTIONS = [15, 30, 50]

type FilterValues = Record<string, string | number | null>

type ListParams<F> = { page: number; per_page: number } & {
  [K in keyof F]?: NonNullable<F[K]>
}

export function usePaginatedList<T, F extends FilterValues = Record<never, never>>(
  fetcher: (params: ListParams<F>) => Promise<Paginated<T>>,
  initialFilters: F = {} as F,
) {
  const items = ref([]) as Ref<T[]>
  const meta = ref<PaginationMeta | null>(null)
  const page = ref(1)
  const perPage = ref(PER_PAGE_OPTIONS[0] ?? 15)
  const loading = ref(false)
  const error = ref('')

  // lo que se escribe en los campos vive en "filters"; recién al pulsar Filtrar pasa a "applied" y se consulta
  const filters = reactive<FilterValues>({ ...initialFilters }) as unknown as F
  const applied = ref<FilterValues>({ ...initialFilters })

  const filtered = computed(() =>
    Object.values(applied.value).some((v) => v !== null && String(v).trim() !== ''),
  )

  // si una respuesta lenta llega después de otra más nueva, se descarta
  let lastRequest = 0

  async function load() {
    const request = ++lastRequest
    loading.value = true
    error.value = ''

    const params: Record<string, unknown> = { page: page.value, per_page: perPage.value }
    for (const [key, value] of Object.entries(applied.value)) {
      const clean = typeof value === 'string' ? value.trim() : value
      if (clean !== null && clean !== '') params[key] = clean
    }

    try {
      const result = await fetcher(params as ListParams<F>)
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

  function apply() {
    applied.value = { ...(filters as FilterValues) }
    backToFirstPage()
  }

  function clear() {
    Object.assign(filters, initialFilters)
    apply()
  }

  watch(page, load)
  watch(perPage, backToFirstPage)
  onMounted(load)

  return { items, meta, page, perPage, filters, filtered, loading, error, load, apply, clear }
}
