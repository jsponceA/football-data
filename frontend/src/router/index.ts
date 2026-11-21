import { createRouter, createWebHistory } from 'vue-router'
import { ability } from '@/plugins/ability'
import { useAuthStore } from '@/stores/auth'
import type { Action, Subject } from '@/types/api'
import { sections } from './navigation'

declare module 'vue-router' {
  interface RouteMeta {
    // solo para visitantes (login)
    guest?: boolean
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
      meta: { guest: true },
    },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      children: [
        { path: '', name: 'home', component: () => import('@/views/HomeView.vue') },
        {
          path: 'competitions',
          name: 'competitions',
          component: () => import('@/views/CompetitionsView.vue'),
          meta: { ability: { action: 'view', subject: 'competitions' } },
        },
        {
          path: 'competitions/:id(\\d+)',
          name: 'competition',
          component: () => import('@/views/CompetitionDetailView.vue'),
          props: (route) => ({ id: Number(route.params.id) }),
          meta: { ability: { action: 'view', subject: 'competitions' } },
        },
        {
          path: 'teams',
          name: 'teams',
          component: () => import('@/views/TeamsView.vue'),
          meta: { ability: { action: 'view', subject: 'teams' } },
        },
        {
          path: 'teams/:id(\\d+)',
          name: 'team',
          component: () => import('@/views/TeamDetailView.vue'),
          props: (route) => ({ id: Number(route.params.id) }),
          meta: { ability: { action: 'view', subject: 'teams' } },
        },
        {
          path: 'players',
          name: 'players',
          component: () => import('@/views/PlayersView.vue'),
          meta: { ability: { action: 'view', subject: 'players' } },
        },
        {
          path: 'users',
          name: 'users',
          component: () => import('@/views/UsersView.vue'),
          meta: { ability: { action: 'manage', subject: 'users' } },
        },
        {
          path: 'roles',
          name: 'roles',
          component: () => import('@/views/RolesView.vue'),
          meta: { ability: { action: 'manage', subject: 'roles' } },
        },
        { path: 'forbidden', name: 'forbidden', component: () => import('@/views/ForbiddenView.vue') },
        { path: ':pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFoundView.vue') },
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

export default router
