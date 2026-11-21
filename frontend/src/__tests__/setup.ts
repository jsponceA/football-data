import { vi } from 'vitest'

// lo mínimo del navegador que Vuetify consulta al montar sus componentes
class ObserverStub {
  observe() {}
  unobserve() {}
  disconnect() {}
}

globalThis.ResizeObserver = ObserverStub as unknown as typeof ResizeObserver
globalThis.IntersectionObserver = ObserverStub as unknown as typeof IntersectionObserver

window.matchMedia ??= ((query: string) => ({
  matches: false,
  media: query,
  onchange: null,
  addEventListener() {},
  removeEventListener() {},
  addListener() {},
  removeListener() {},
  dispatchEvent: () => false,
})) as typeof window.matchMedia

URL.createObjectURL = vi.fn(() => 'blob:prueba')
URL.revokeObjectURL = vi.fn()
