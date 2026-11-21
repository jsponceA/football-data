import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import * as authApi from '@/api/auth'
import { ability } from '@/plugins/ability'
import type { SessionUser } from '@/types/api'

export const useAuthStore = defineStore(
  'auth',
  () => {
    const token = ref<string | null>(null)
    const user = ref<SessionUser | null>(null)
    // no se persiste: en cada carga se vuelve a pedir /me para tomar permisos nuevos
    const refreshed = ref(false)

    const isLoggedIn = computed(() => token.value !== null)

    function applyUser(value: SessionUser | null) {
      user.value = value
      ability.update(value?.abilities ?? [])
    }

    function startSession(data: { token: string; user: SessionUser }) {
      token.value = data.token
      refreshed.value = true
      applyUser(data.user)
    }

    async function login(email: string, password: string) {
      startSession(await authApi.login(email, password))
    }

    // el registro deja la sesión abierta, igual que el login
    async function register(name: string, email: string, password: string, confirmation: string) {
      startSession(await authApi.register({ name, email, password, password_confirmation: confirmation }))
    }

    async function refresh() {
      try {
        applyUser(await authApi.me())
        refreshed.value = true
      } catch {
        // si fue un 401 el interceptor ya cerró la sesión; otro error no debe sacar al usuario
      }
    }

    // se limpia la sesión local aunque el servidor no responda
    async function logout() {
      try {
        await authApi.logout()
      } catch {
        // da igual: el token se descarta de todos modos
      }
      clear()
    }

    // tras editar el perfil la respuesta ya trae al usuario completo
    function setUser(value: SessionUser) {
      applyUser(value)
    }

    function clear() {
      token.value = null
      refreshed.value = false
      applyUser(null)
    }

    return { token, user, refreshed, isLoggedIn, login, register, refresh, setUser, logout, clear }
  },
  {
    persist: { pick: ['token', 'user'] },
  },
)
