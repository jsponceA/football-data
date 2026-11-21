import { nextTick } from 'vue'
import { describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/http'
import type { Paginated } from '@/types/api'
import { flush, withSetup } from '@/__tests__/helpers'
import { usePaginatedList } from '../usePaginatedList'

const answer = (names: string[], total = names.length) =>
  ({ data: names.map((name, id) => ({ id, name })), meta: { total, current_page: 1, last_page: 1 } }) as unknown as Paginated<{
    id: number
    name: string
  }>

function setup(fetcher: ReturnType<typeof vi.fn>, filters = { search: '', role: null as string | null }) {
  return withSetup(() => usePaginatedList(fetcher as never, filters)).result
}

describe('usePaginatedList', () => {
  it('al montarse pide la primera página de 15', async () => {
    const fetcher = vi.fn().mockResolvedValue(answer(['Ana', 'Luis']))

    const list = setup(fetcher)
    await flush()

    expect(fetcher).toHaveBeenCalledWith({ page: 1, per_page: 15 })
    expect(list.items.value).toHaveLength(2)
    expect(list.loading.value).toBe(false)
  })

  it('lo escrito en los filtros no consulta hasta pulsar Filtrar', async () => {
    const fetcher = vi.fn().mockResolvedValue(answer(['Ana']))
    const list = setup(fetcher)
    await flush()
    fetcher.mockClear()

    list.filters.search = 'ana'
    await flush()
    expect(fetcher).not.toHaveBeenCalled()

    list.apply()
    await flush()
    expect(fetcher).toHaveBeenCalledWith({ page: 1, per_page: 15, search: 'ana' })
  })

  it('no manda filtros vacíos ni null y recorta los espacios', async () => {
    const fetcher = vi.fn().mockResolvedValue(answer([]))
    const list = setup(fetcher)
    await flush()
    fetcher.mockClear()

    list.filters.search = '  ana  '
    list.filters.role = null
    list.apply()
    await flush()

    expect(fetcher).toHaveBeenLastCalledWith({ page: 1, per_page: 15, search: 'ana' })
  })

  it('al filtrar vuelve a la primera página', async () => {
    const fetcher = vi.fn().mockResolvedValue(answer(['Ana']))
    const list = setup(fetcher)
    await flush()
    list.page.value = 3
    await flush()

    list.filters.search = 'ana'
    list.apply()
    await flush()

    expect(list.page.value).toBe(1)
    expect(fetcher).toHaveBeenLastCalledWith({ page: 1, per_page: 15, search: 'ana' })
  })

  it('cambiar de página vuelve a pedir los datos', async () => {
    const fetcher = vi.fn().mockResolvedValue(answer(['Ana']))
    const list = setup(fetcher)
    await flush()

    list.page.value = 2
    await flush()

    expect(fetcher).toHaveBeenLastCalledWith({ page: 2, per_page: 15 })
  })

  it('cambiar el tamaño de página regresa a la primera', async () => {
    const fetcher = vi.fn().mockResolvedValue(answer(['Ana']))
    const list = setup(fetcher)
    await flush()
    list.page.value = 2
    await flush()

    list.perPage.value = 30
    await flush()

    expect(list.page.value).toBe(1)
    expect(fetcher).toHaveBeenLastCalledWith({ page: 1, per_page: 30 })
  })

  it('filtered indica si hay filtros aplicados y clear los quita', async () => {
    const fetcher = vi.fn().mockResolvedValue(answer(['Ana']))
    const list = setup(fetcher)
    await flush()
    expect(list.filtered.value).toBe(false)

    list.filters.role = 'admin'
    list.apply()
    await flush()
    expect(list.filtered.value).toBe(true)

    list.clear()
    await flush()
    expect(list.filtered.value).toBe(false)
    expect(list.filters.role).toBeNull()
    expect(fetcher).toHaveBeenLastCalledWith({ page: 1, per_page: 15 })
  })

  it('si la petición falla guarda el mensaje y deja de cargar', async () => {
    const fetcher = vi.fn().mockRejectedValue(new ApiError(503, 'Servicio no disponible.'))

    const list = setup(fetcher)
    await flush()

    expect(list.error.value).toBe('Servicio no disponible.')
    expect(list.loading.value).toBe(false)
  })

  it('una respuesta lenta que llega tarde no pisa a la más nueva', async () => {
    let resolveSlow: (value: unknown) => void = () => {}
    const fetcher = vi
      .fn()
      .mockImplementationOnce(() => Promise.resolve(answer(['inicial'])))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => Promise.resolve(answer(['nueva'])))
    const list = setup(fetcher)
    await flush()

    list.page.value = 2 // petición lenta
    await nextTick()
    list.page.value = 3 // petición rápida, más reciente
    await flush()
    expect(list.items.value[0]).toMatchObject({ name: 'nueva' })

    resolveSlow(answer(['vieja']))
    await flush()

    expect(list.items.value[0]).toMatchObject({ name: 'nueva' })
  })
})
