import { flushPromises, mount } from '@vue/test-utils'
import { defineComponent } from 'vue'
import { createMemoryHistory, createRouter } from 'vue-router'
import { describe, expect, it, vi } from 'vitest'
import { createTestVuetify } from '@/__tests__/helpers'
import { getCompetitions } from '@/api/football'
import CompetitionsView from '@/views/CompetitionsView.vue'

vi.mock('@/api/football', () => ({ getCompetitions: vi.fn() }))

const liga = (id: number, name: string, plan: string) => ({
  id,
  code: name.slice(0, 2).toUpperCase(),
  name,
  type: 'LEAGUE',
  emblem: null,
  plan,
  area: 'England',
  imported_at: null,
})

async function mountView() {
  vi.mocked(getCompetitions).mockResolvedValue({
    data: [liga(2021, 'Premier League', 'TIER_ONE'), liga(2152, 'Copa Libertadores', 'TIER_FOUR')],
    meta: { current_page: 1, last_page: 1, per_page: 15, total: 2 },
  } as Awaited<ReturnType<typeof getCompetitions>>)

  const stub = { template: '<div />' }
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: stub },
      { path: '/competitions', name: 'competitions', component: stub },
      { path: '/competitions/:id', name: 'competition', component: stub },
    ],
  })

  // la lista usa componentes de Vuetify que necesitan v-app
  const host = defineComponent({ components: { CompetitionsView }, template: '<v-app><CompetitionsView /></v-app>' })
  const wrapper = mount(host, { global: { plugins: [createTestVuetify(), router] } })
  await flushPromises()
  return wrapper
}

describe('CompetitionsView: ligas de pago', () => {
  it('la liga gratuita se puede abrir', async () => {
    const wrapper = await mountView()

    const enlaces = wrapper.findAll('a.competition-card')
    expect(enlaces).toHaveLength(1)
    expect(enlaces[0]!.attributes('href')).toBe('/competitions/2021')
  })

  it('la liga de pago se muestra bloqueada y sin enlace', async () => {
    const wrapper = await mountView()

    const bloqueada = wrapper.find('.competition-card--locked')
    expect(bloqueada.exists()).toBe(true)
    expect(bloqueada.text()).toContain('Copa Libertadores')
    expect(bloqueada.text()).toContain('Plan de pago')
    expect(bloqueada.element.tagName).not.toBe('A')
    expect(bloqueada.attributes('href')).toBeUndefined()
  })
})
