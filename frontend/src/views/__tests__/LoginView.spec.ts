import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { defineComponent } from 'vue'
import { createMemoryHistory, createRouter } from 'vue-router'
import { describe, expect, it, vi } from 'vitest'
import { createTestVuetify } from '@/__tests__/helpers'
import LoginView from '../LoginView.vue'

vi.mock('@/api/auth', () => ({ login: vi.fn(), logout: vi.fn(), me: vi.fn() }))

async function mountAt(url: string) {
  const stub = { template: '<div />' }
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login', name: 'login', component: LoginView },
      { path: '/register', name: 'register', component: stub },
      { path: '/forgot-password', name: 'forgot-password', component: stub },
      { path: '/', name: 'home', component: stub },
    ],
  })
  router.push(url)
  await router.isReady()

  // la pantalla usa v-main, que solo funciona dentro de un v-app
  const host = defineComponent({ components: { LoginView }, template: '<v-app><LoginView /></v-app>' })
  const wrapper = mount(host, { global: { plugins: [createTestVuetify(), createPinia(), router] } })
  await flushPromises()
  return wrapper
}

describe('LoginView', () => {
  it('si la sesión venció lo dice con claridad', async () => {
    const wrapper = await mountAt('/login?expired=1')

    expect(wrapper.text()).toContain('Tu sesión venció')
  })

  it('en un ingreso normal no muestra ese aviso', async () => {
    const wrapper = await mountAt('/login')

    expect(wrapper.text()).not.toContain('Tu sesión venció')
  })
})
