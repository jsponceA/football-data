<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useAbility } from '@casl/vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { useAuthStore } from '@/stores/auth'
import { sections } from '@/router/navigation'
import logo from '@/assets/img/logo.webp'
import type { AppAbility } from '@/plugins/ability'

const auth = useAuthStore()
const router = useRouter()
const { mobile } = useDisplay()
const { can } = useAbility<AppAbility>()

// null = abierto en escritorio y cerrado en móvil, donde lo abre el botón de la barra
const drawer = ref<boolean | null>(null)

const visibleSections = computed(() => sections.filter((s) => can(s.ability.action, s.ability.subject)))

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <v-navigation-drawer v-model="drawer">
    <v-list-item title="Football Data" subtitle="Ligas, equipos y jugadores" class="py-3">
      <template #prepend>
        <v-avatar color="primary" size="40" class="mr-3">
          <img :src="logo" alt="" width="34" height="34" />
        </v-avatar>
      </template>
    </v-list-item>
    <v-divider />
    <v-list nav density="comfortable">
      <v-list-item
        v-for="section in visibleSections"
        :key="section.route"
        :to="{ name: section.route }"
        :prepend-icon="section.icon"
        :title="section.title"
        rounded="lg"
      />
    </v-list>
  </v-navigation-drawer>

  <v-app-bar flat border="b">
    <v-app-bar-nav-icon v-if="mobile" aria-label="Abrir menú" @click="drawer = !drawer" />
    <v-spacer />
    <ThemeToggle />

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

  <v-main>
    <v-container class="py-6" style="max-width: 1200px">
      <router-view />
    </v-container>
  </v-main>
</template>
