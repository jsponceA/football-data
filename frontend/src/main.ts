import { createApp } from 'vue'

// Vuetify
import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import { es } from 'vuetify/locale'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

import { createPinia } from 'pinia'
import { createPersistedState } from 'pinia-plugin-persistedstate'
import { abilitiesPlugin } from '@casl/vue'

import App from './App.vue'
import router from './router'
import { setAuthHandlers } from './api/http'
import { ability } from './plugins/ability'
import { useAuthStore } from './stores/auth'

const vuetify = createVuetify({
  components,
  directives,
  locale: { locale: 'es', messages: { es } },
})

const pinia = createPinia()
// el token queda en localStorage para sobrevivir a recargas; con sessionStorage se pierde al cerrar la pestaña
pinia.use(
  createPersistedState({
    storage: localStorage,
  }),
)

const app = createApp(App)
app.use(vuetify)
app.use(pinia)

const auth = useAuthStore()
// las reglas de CASL no se persisten, se reconstruyen desde el usuario guardado
ability.update(auth.user?.abilities ?? [])
setAuthHandlers({
  getToken: () => auth.token,
  onUnauthorized: () => {
    auth.clear()
    if (router.currentRoute.value.name !== 'login') {
      router.push({ name: 'login' })
    }
  },
})

app.use(abilitiesPlugin, ability)
app.use(router)

app.mount('#app')
