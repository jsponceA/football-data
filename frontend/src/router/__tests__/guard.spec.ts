import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as authApi from '@/api/auth'
import { ability } from '@/plugins/ability'
import type { SessionUser } from '@/types/api'
import { useAuthStore } from '@/stores/auth'
import router from '../index'

vi.mock('@/api/auth', () => ({ login: vi.fn(), logout: vi.fn(), me: vi.fn() }))

const consulta: SessionUser = {
  id: 2,
  name: 'Usuario de consulta',
  email: 'consulta@example.com',
  is_active: true,
  roles: ['solo-consulta'],
  permissions: [],
  abilities: [
    { action: 'view', subject: 'competitions' },
    { action: 'view', subject: 'teams' },
    { action: 'view', subject: 'players' },
  ],
}

const admin: SessionUser = {
  ...consulta,
  id: 1,
  name: 'Administrador',
  roles: ['admin'],
  abilities: [
    ...consulta.abilities,
    { action: 'manage', subject: 'users' },
    { action: 'manage', subject: 'roles' },
  ],
}

function sessionAs(user: SessionUser | null) {
  const auth = useAuthStore()
  if (user) {
    auth.token = 'token'
    auth.setUser(user)
    // sin esto el guard pediría /me a la API la primera vez
    auth.refreshed = true
  } else {
    auth.clear()
  }
  return auth
}

beforeEach(async () => {
  setActivePinia(createPinia())
  vi.mocked(authApi.me).mockReset()
  // punto de partida neutro: una ruta que cualquier usuario con sesión puede ver
  sessionAs(admin)
  await router.push('/forbidden')
})

describe('sin sesión', () => {
  it('una ruta privada lleva al login y recuerda a dónde iba', async () => {
    sessionAs(null)

    await router.push('/users')

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/users')
  })

  it('el login es accesible', async () => {
    sessionAs(null)

    await router.push('/login')

    expect(router.currentRoute.value.name).toBe('login')
  })

  it.each(['/register', '/forgot-password', '/reset-password?token=abc&email=a%40b.com'])(
    '%s es accesible sin sesión',
    async (path) => {
      sessionAs(null)

      await router.push(path)

      expect(router.currentRoute.value.fullPath).toBe(path)
    },
  )
})

describe('con sesión', () => {
  it('quien ya entró no vuelve a ver el login: lo manda a su primera sección', async () => {
    sessionAs(admin)

    await router.push('/login')

    expect(router.currentRoute.value.name).toBe('competitions')
  })

  it.each(['/register', '/forgot-password'])('quien ya entró no ve %s', async (path) => {
    sessionAs(admin)

    await router.push(path)

    expect(router.currentRoute.value.name).toBe('competitions')
  })

  it('el enlace del correo abre el restablecimiento aunque ya haya sesión', async () => {
    sessionAs(admin)

    await router.push('/reset-password?token=abc&email=a%40b.com')

    expect(router.currentRoute.value.name).toBe('reset-password')
    expect(router.currentRoute.value.query.token).toBe('abc')
  })

  it('la raíz lleva a la primera sección que el usuario puede ver', async () => {
    sessionAs({ ...consulta, abilities: [{ action: 'view', subject: 'players' }] })

    await router.push('/')

    expect(router.currentRoute.value.name).toBe('players')
  })

  it('sin el permiso de la ruta lleva a "sin acceso"', async () => {
    sessionAs(consulta)

    await router.push('/users')

    expect(router.currentRoute.value.name).toBe('forbidden')
  })

  it('con el permiso entra', async () => {
    sessionAs(admin)

    await router.push('/roles')

    expect(router.currentRoute.value.name).toBe('roles')
  })

  it('las páginas de crear y editar piden el mismo permiso que su listado', async () => {
    sessionAs(consulta)
    await router.push('/users/new')
    expect(router.currentRoute.value.name).toBe('forbidden')

    sessionAs(admin)
    await router.push('/users/3/edit')
    expect(router.currentRoute.value.name).toBe('user-edit')
    expect(router.currentRoute.value.params.id).toBe('3')
  })

  it('el perfil propio no pide ningún permiso', async () => {
    sessionAs({ ...consulta, abilities: [] })

    await router.push('/profile')

    expect(router.currentRoute.value.name).toBe('profile')
  })

  it('un id que no es número no coincide con el detalle y cae en 404', async () => {
    sessionAs(admin)

    await router.push('/teams/abc')

    expect(router.currentRoute.value.name).toBe('not-found')
  })
})

describe('primera carga de la página', () => {
  it('pide /me una vez para tomar los permisos al día', async () => {
    const auth = sessionAs(consulta)
    auth.refreshed = false
    vi.mocked(authApi.me).mockResolvedValue(admin)

    await router.push('/users')

    expect(authApi.me).toHaveBeenCalledOnce()
    // los permisos nuevos (admin) ya se aplicaron antes de decidir si entra
    expect(router.currentRoute.value.name).toBe('users')
  })

  it('si /me responde que la sesión ya no vale, lleva al login', async () => {
    const auth = sessionAs(consulta)
    auth.refreshed = false
    vi.mocked(authApi.me).mockImplementation(async () => {
      auth.clear()
      throw new Error('401')
    })

    await router.push('/teams')

    expect(router.currentRoute.value.name).toBe('login')
  })
})

describe('título de la página', () => {
  it('cada ruta pone su título en la pestaña', async () => {
    sessionAs(admin)

    await router.push('/roles')

    expect(document.title).toBe('Roles y permisos | Football Data')
  })
})

describe('permisos de la sesión', () => {
  it('el guard usa las reglas CASL actuales', () => {
    sessionAs(consulta)

    expect(ability.can('manage', 'users')).toBe(false)
    expect(ability.can('view', 'teams')).toBe(true)
  })
})
