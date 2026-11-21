import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { defineComponent } from 'vue'
import { createMemoryHistory, createRouter } from 'vue-router'
import { describe, expect, it, vi } from 'vitest'
import { createTestVuetify } from '@/__tests__/helpers'
import ResetPasswordView from '../ResetPasswordView.vue'

vi.mock('@/api/auth', () => ({ resetPassword: vi.fn() }))

async function mountAt(url: string) {
  const stub = { template: '<div />' }
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/reset-password', name: 'reset-password', component: ResetPasswordView },
      { path: '/forgot-password', name: 'forgot-password', component: stub },
      { path: '/login', name: 'login', component: stub },
    ],
  })
  router.push(url)
  await router.isReady()

  // la pantalla usa v-main, que solo funciona dentro de un v-app
  const host = defineComponent({
    components: { ResetPasswordView },
    template: '<v-app><ResetPasswordView /></v-app>',
  })
  const wrapper = mount(host, { global: { plugins: [createTestVuetify(), createPinia(), router] } })
  await flushPromises()
  return wrapper
}

describe('ResetPasswordView', () => {
  it('sin token ni correo en el enlace avisa y ofrece pedir uno nuevo', async () => {
    const wrapper = await mountAt('/reset-password')

    expect(wrapper.text()).toContain('Este enlace no es válido')
    expect(wrapper.text()).toContain('Pedir un enlace nuevo')
    expect(wrapper.find('input[type=password]').exists()).toBe(false)
  })

  it('con solo el token tampoco alcanza: falta el correo', async () => {
    const wrapper = await mountAt('/reset-password?token=abc')

    expect(wrapper.text()).toContain('Este enlace no es válido')
  })

  it('con el enlace completo muestra el correo y los dos campos de contraseña', async () => {
    const wrapper = await mountAt('/reset-password?token=abc&email=ana%40example.com')

    expect(wrapper.text()).toContain('ana@example.com')
    expect(wrapper.text()).not.toContain('Este enlace no es válido')
    expect(wrapper.findAll('input[type=password]')).toHaveLength(2)
  })
})
