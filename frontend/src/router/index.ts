import { createRouter, createWebHistory } from 'vue-router'
import { ability } from '@/plugins/ability'
import { useAuthStore } from '@/stores/auth'
import type { Action, Subject } from '@/types/api'
import { pageTitle } from '@/composables/usePageTitle'
import { sections } from './navigation'

declare module 'vue-router' {
  interface RouteMeta {
    // solo para visitantes (login)
    guest?: boolean
    title?: string
    // ruta del menú que debe verse activa cuando esta página es un detalle de esa sección
    section?: string
    ability?: { action: Action; subject: Subject }
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true, title: 'Iniciar sesión' },
    },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('@/views/HomeView.vue'),
          meta: { title: 'Inicio' },
        },
        {
          path: 'competitions',
          name: 'competitions',
          component: () => import('@/views/CompetitionsView.vue'),
          meta: { title: 'Ligas', ability: { action: 'view', subject: 'competitions' } },
        },
        {
          path: 'competitions/:id(\\d+)',
          name: 'competition',
          component: () => import('@/views/CompetitionDetailView.vue'),
          props: (route) => ({ id: Number(route.params.id) }),
          meta: { title: 'Liga', section: 'competitions', ability: { action: 'view', subject: 'competitions' } },
        },
        {
          path: 'teams',
          name: 'teams',
          component: () => import('@/views/TeamsView.vue'),
          meta: { title: 'Equipos', ability: { action: 'view', subject: 'teams' } },
        },
        {
          path: 'teams/:id(\\d+)',
          name: 'team',
          component: () => import('@/views/TeamDetailView.vue'),
          props: (route) => ({ id: Number(route.params.id) }),
          meta: { title: 'Equipo', section: 'teams', ability: { action: 'view', subject: 'teams' } },
        },
        {
          path: 'players',
          name: 'players',
          component: () => import('@/views/PlayersView.vue'),
          meta: { title: 'Jugadores', ability: { action: 'view', subject: 'players' } },
        },
        {
          path: 'users',
          name: 'users',
          component: () => import('@/views/UsersView.vue'),
          meta: { title: 'Usuarios', ability: { action: 'manage', subject: 'users' } },
        },
        {
          path: 'roles',
          name: 'roles',
          component: () => import('@/views/RolesView.vue'),
          meta: { title: 'Roles y permisos', ability: { action: 'manage', subject: 'roles' } },
        },
        {
          path: 'forbidden',
          name: 'forbidden',
          component: () => import('@/views/ForbiddenView.vue'),
          meta: { title: 'Sin acceso' },
        },
        {
          path: ':pathMatch(.*)*',
          name: 'not-found',
          component: () => import('@/views/NotFoundView.vue'),
          meta: { title: 'Página no encontrada' },
        },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.guest) {
    return auth.isLoggedIn ? { name: 'home' } : true
  }

  if (!auth.isLoggedIn) {
    return { name: 'login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } }
  }

  // una vez por carga: así un cambio de permisos o un usuario desactivado se nota al recargar
  if (!auth.refreshed) {
    await auth.refresh()
    if (!auth.isLoggedIn) {
      return { name: 'login' }
    }
  }

  // la raíz lleva a la primera sección que el usuario pueda ver
  if (to.name === 'home') {
    const first = sections.find((s) => ability.can(s.ability.action, s.ability.subject))
    if (first) {
      return { name: first.route }
    }
  }

  const required = to.meta.ability
  if (required && !ability.can(required.action, required.subject)) {
    return { name: 'forbidden' }
  }

  return true
})

router.afterEach((to) => {
  document.title = pageTitle(to.meta.title)
})

export default router
