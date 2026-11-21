<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useAbility } from '@casl/vue'
import SettingsDrawer from '@/components/SettingsDrawer.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { sections } from '@/router/navigation'
import logo from '@/assets/img/logo.webp'
import type { AppAbility } from '@/plugins/ability'

const auth = useAuthStore()
const ui = useUiStore()
const router = useRouter()
const { mobile } = useDisplay()
const { can } = useAbility<AppAbility>()

// en móvil el menú es un cajón que se abre y cierra; en escritorio siempre está y solo se reduce a iconos
const drawer = ref(!mobile.value)
watch(mobile, (value) => (drawer.value = !value))

const isRail = computed(() => ui.rail && !mobile.value)

function toggleMenu() {
  if (mobile.value) {
    drawer.value = !drawer.value
  } else {
    ui.rail = !ui.rail
  }
}

const settings = ref(false)
const year = new Date().getFullYear()

const visibleSections = computed(() => sections.filter((s) => can(s.ability.action, s.ability.subject)))

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <v-navigation-drawer v-model="drawer" :rail="isRail">
    <!-- no uso v-list-item: reserva un hueco fijo junto al icono y no deja centrar el logo en el modo compacto -->
    <div class="d-flex align-center py-3" :class="isRail ? 'justify-center' : 'px-4'">
      <v-avatar color="primary" size="40">
        <img :src="logo" alt="" width="34" height="34" />
      </v-avatar>
      <span v-if="!isRail" class="text-subtitle-1 font-weight-medium ml-3">Football Data</span>
    </div>
    <v-divider />
    <v-list nav density="comfortable">
      <v-list-item
        v-for="section in visibleSections"
        :key="section.route"
        :to="{ name: section.route }"
        :prepend-icon="section.icon"
        :title="section.title"
        rounded="lg"
      >
        <!-- compacto solo se ven los iconos, así que el nombre sale al pasar el cursor -->
        <v-tooltip v-if="isRail" activator="parent" location="end" :text="section.title" />
      </v-list-item>
    </v-list>
  </v-navigation-drawer>

  <v-app-bar flat border="b">
    <v-app-bar-nav-icon
      :aria-label="mobile ? 'Mostrar u ocultar el menú' : 'Contraer o expandir el menú'"
      @click="toggleMenu"
    />
    <v-spacer />
    <ThemeToggle />
    <v-btn
      icon="mdi-cog-outline"
      variant="text"
      aria-label="Personalizar"
      title="Personalizar"
      @click="settings = true"
    />

    <v-menu>
      <template #activator="{ props }">
        <v-btn v-bind="props" variant="text" prepend-icon="mdi-account-circle" class="mr-2">
          <span class="d-none d-sm-inline">{{ auth.user?.name }}</span>
        </v-btn>
      </template>
      <v-card min-width="260">
        <v-card-text>
          <div class="font-weight-medium">{{ auth.user?.name }}</div>
          <div class="text-body-2 text-medium-emphasis mb-2">{{ auth.user?.email }}</div>
          <v-chip v-for="role in auth.user?.roles" :key="role" size="small" class="mr-1">
            {{ role }}
          </v-chip>
        </v-card-text>
        <v-divider />
        <v-list density="compact">
          <v-list-item prepend-icon="mdi-logout" title="Cerrar sesión" @click="logout" />
        </v-list>
      </v-card>
    </v-menu>
  </v-app-bar>

  <SettingsDrawer v-model="settings" />

  <!-- el footer va dentro del main y no fijo: baja con el contenido y se pega al final si hay poco -->
  <v-main class="d-flex flex-column">
    <v-container fluid class="py-4 px-4 px-md-6 flex-grow-1">
      <router-view />
    </v-container>

    <v-footer border height="44" class="flex-grow-0 flex-shrink-0 text-caption text-medium-emphasis">
      <div class="d-flex justify-end w-100 px-2">
        © {{ year }} Football Data. Todos los derechos reservados.
      </div>
    </v-footer>
  </v-main>
</template>
