import { mount, type VueWrapper } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h, nextTick, ref } from 'vue'
import { VApp } from 'vuetify/components'
import { createTestVuetify } from '@/__tests__/helpers'
import PwaStatus from '../PwaStatus.vue'

// el módulo "virtual:pwa-register/vue" lo genera el plugin al compilar; aquí se simula
const sw = vi.hoisted(() => ({
  updateServiceWorker: vi.fn(),
  registered: undefined as undefined | ((url: string, registration?: { update: () => void }) => void),
}))

vi.mock('virtual:pwa-register/vue', async () => {
  const { ref } = await import('vue')
  return {
    useRegisterSW: (opciones: { onRegisteredSW?: typeof sw.registered }) => {
      sw.registered = opciones.onRegisteredSW
      return { needRefresh: ref(false), offlineReady: ref(false), updateServiceWorker: sw.updateServiceWorker }
    },
  }
})

function setOnline(valor: boolean) {
  Object.defineProperty(window.navigator, 'onLine', { value: valor, configurable: true })
  window.dispatchEvent(new Event(valor ? 'online' : 'offline'))
}

// montado dentro de v-app y en el body, porque los avisos de Vuetify se dibujan en una capa aparte
function montar(): VueWrapper {
  return mount(
    defineComponent({ render: () => h(VApp, null, { default: () => h(PwaStatus) }) }),
    { global: { plugins: [createTestVuetify()] }, attachTo: document.body },
  )
}

const texto = () => document.body.textContent ?? ''

describe('PwaStatus', () => {
  let wrapper: VueWrapper | undefined

  beforeEach(() => {
    vi.useFakeTimers()
    sw.updateServiceWorker.mockClear()
    Object.defineProperty(window.navigator, 'onLine', { value: true, configurable: true })
  })

  afterEach(() => {
    wrapper?.unmount()
    wrapper = undefined
    vi.useRealTimers()
  })

  it('con conexión y sin versión nueva no muestra ningún aviso', async () => {
    wrapper = montar()
    await nextTick()

    expect(texto()).not.toContain('Sin conexión')
    expect(texto()).not.toContain('versión nueva')
  })

  it('avisa cuando se pierde la conexión y lo quita cuando vuelve', async () => {
    wrapper = montar()

    setOnline(false)
    await nextTick()
    await nextTick()
    expect(texto()).toContain('Sin conexión')

    setOnline(true)
    await nextTick()
    await nextTick()
    vi.advanceTimersByTime(500)
    await nextTick()
    expect(document.querySelector('[data-test="offline-notice"]')?.getAttribute('style') ?? '').not.toContain('display: block')
  })

  it('busca una versión nueva cada hora, y solo si hay conexión', () => {
    wrapper = montar()
    const update = vi.fn()
    sw.registered?.('/sw.js', { update })

    vi.advanceTimersByTime(60 * 60 * 1000)
    expect(update).toHaveBeenCalledTimes(1)

    setOnline(false)
    vi.advanceTimersByTime(60 * 60 * 1000)
    expect(update).toHaveBeenCalledTimes(1)

    setOnline(true)
    vi.advanceTimersByTime(60 * 60 * 1000)
    expect(update).toHaveBeenCalledTimes(2)
  })

  it('si el navegador no devuelve registro no programa nada', () => {
    wrapper = montar()

    expect(() => sw.registered?.('/sw.js', undefined)).not.toThrow()
    vi.advanceTimersByTime(2 * 60 * 60 * 1000)
  })
})

describe('PwaStatus: aviso de versión nueva', () => {
  it('"Actualizar" activa el service worker nuevo y recarga; "Después" cierra el aviso', async () => {
    vi.resetModules()
    const needRefresh = ref(true)
    const updateServiceWorker = vi.fn()
    vi.doMock('virtual:pwa-register/vue', () => ({
      useRegisterSW: () => ({ needRefresh, offlineReady: ref(false), updateServiceWorker }),
    }))
    const { default: Componente } = await import('../PwaStatus.vue')

    const montado = mount(
      defineComponent({ render: () => h(VApp, null, { default: () => h(Componente) }) }),
      { global: { plugins: [createTestVuetify()] }, attachTo: document.body },
    )
    await nextTick()
    await nextTick()

    expect(document.body.textContent).toContain('Hay una versión nueva')

    ;(document.querySelector('[data-test="update-now"]') as HTMLElement).click()
    expect(updateServiceWorker).toHaveBeenCalledWith(true)

    ;(document.querySelector('[data-test="update-later"]') as HTMLElement).click()
    expect(needRefresh.value).toBe(false)

    montado.unmount()
    vi.doUnmock('virtual:pwa-register/vue')
  })
})
