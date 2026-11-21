import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createTestVuetify } from '@/__tests__/helpers'
import DataState from '@/components/DataState.vue'

const base = { loading: false, error: '', empty: false }

function mountState(props: Record<string, unknown> = {}) {
  return mount(DataState, {
    props: { ...base, ...props },
    slots: { default: '<p id="contenido">Datos</p>' },
    global: { plugins: [createTestVuetify()] },
  })
}

describe('DataState', () => {
  it('con datos muestra el contenido', () => {
    const wrapper = mountState()

    expect(wrapper.find('#contenido').exists()).toBe(true)
    expect(wrapper.find('[role=status]').exists()).toBe(false)
  })

  it('la primera vez que carga muestra el esqueleto y no el contenido', () => {
    const wrapper = mountState({ loading: true, empty: true })

    expect(wrapper.find('[role=status]').attributes('aria-label')).toBe('Cargando')
    expect(wrapper.find('#contenido').exists()).toBe(false)
  })

  it('al recargar con datos ya visibles se queda el contenido, sin esqueleto', () => {
    const wrapper = mountState({ loading: true, empty: false })

    expect(wrapper.find('#contenido').exists()).toBe(true)
    expect(wrapper.find('[role=status]').exists()).toBe(false)
  })

  it('cada tipo de esqueleto se dibuja sin romperse', () => {
    for (const skeleton of ['table', 'competitions', 'teams', 'players', 'competition-detail', 'team-detail', 'form']) {
      const wrapper = mountState({ loading: true, empty: true, skeleton })
      expect(wrapper.find('[role=status]').exists(), skeleton).toBe(true)
    }
  })

  it('sin resultados muestra el aviso con su título y texto', () => {
    const wrapper = mountState({ empty: true, emptyTitle: 'Sin coincidencias', emptyText: 'Prueba con otros filtros.' })

    expect(wrapper.text()).toContain('Sin coincidencias')
    expect(wrapper.text()).toContain('Prueba con otros filtros.')
    expect(wrapper.find('#contenido').exists()).toBe(false)
  })

  it('con error muestra el mensaje y un botón para reintentar', async () => {
    const wrapper = mountState({ error: 'No se pudo conectar con el servidor.', empty: true })

    expect(wrapper.text()).toContain('No se pudo conectar con el servidor.')
    expect(wrapper.find('#contenido').exists()).toBe(false)

    await wrapper.findAll('button').find((b) => b.text().includes('Reintentar'))!.trigger('click')

    expect(wrapper.emitted('retry')).toHaveLength(1)
  })

  it('el error tiene prioridad sobre la carga y el vacío', () => {
    const wrapper = mountState({ error: 'Falló.', loading: true, empty: true })

    expect(wrapper.text()).toContain('Falló.')
    expect(wrapper.find('[role=status]').exists()).toBe(false)
  })
})
