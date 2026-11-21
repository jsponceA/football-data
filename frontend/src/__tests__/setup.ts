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

// los avisos (v-snackbar) se colocan según el área visible de la pantalla
globalThis.visualViewport ??= Object.assign(new EventTarget(), {
  width: 1024,
  height: 768,
  offsetLeft: 0,
  offsetTop: 0,
  pageLeft: 0,
  pageTop: 0,
  scale: 1,
}) as unknown as VisualViewport

URL.createObjectURL =vi.fn(() => 'blob:prueba')
URL.revokeObjectURL = vi.fn()
