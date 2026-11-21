import { createApp, defineComponent, h } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

export function createTestVuetify() {
  return createVuetify({
    components,
    directives,
    theme: { themes: { light: { colors: { primary: '#1b8f4c' } }, dark: { colors: { primary: '#34c471' } } } },
  })
}

// ejecuta un composable dentro de un componente real, porque usan onMounted, watch, etc.
export function withSetup<T>(composable: () => T) {
  let result!: T
  const pinia = createPinia()
  setActivePinia(pinia)

  const app = createApp(
    defineComponent({
      setup() {
        result = composable()
        return () => h('div')
      },
    }),
  )
  app.use(pinia)
  app.use(createTestVuetify())
  app.mount(document.createElement('div'))

  return { result, app }
}

// deja que se resuelvan las promesas pendientes (la carga inicial de un listado, por ejemplo)
export const flush = () => new Promise((resolve) => setTimeout(resolve, 0))
