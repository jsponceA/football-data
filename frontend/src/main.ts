import './assets/main.css'

import { createApp } from 'vue'

// Vuetify
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

import { createPinia } from 'pinia'
import { createPersistedState } from 'pinia-plugin-persistedstate'

import App from './App.vue'
import router from './router'

const vuetify = createVuetify({
  components,
  directives,
})

const pinia = createPinia()
// Persistencia de estado en localStorage puedes cambiarlo a sessionStorage si quieres que se borre al cerrar el navegador
pinia.use(
  createPersistedState({
    storage: localStorage,
  }),
)

const app = createApp(App)
app.use(vuetify)
app.use(pinia)
app.use(router)

app.mount('#app')
