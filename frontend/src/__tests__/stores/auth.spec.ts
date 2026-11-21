import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as authApi from '@/api/auth'
import { ability } from '@/plugins/ability'
import type { SessionUser } from '@/types/api'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/api/auth', () => ({ login: vi.fn(), register: vi.fn(), logout: vi.fn(), me: vi.fn() }))

const consulta: SessionUser = {
  id: 2,
  name: 'Usuario de consulta',
  email: 'consulta@example.com',
  is_active: true,
  roles: ['solo-consulta'],
  permissions: ['teams.view'],
  abilities: [{ action: 'view', subject: 'teams' }],
}

const admin: SessionUser = {
  ...consulta,
  id: 1,
  name: 'Administrador',
  roles: ['admin'],
  permissions: ['teams.view', 'users.manage'],
  abilities: [
    { action: 'view', subject: 'teams' },
    { action: 'manage', subject: 'users' },
  ],
}

beforeEach(() => {
  setActivePinia(createPinia())
  ability.update([])
  vi.mocked(authApi.login).mockReset()
  vi.mocked(authApi.register).mockReset()
  vi.mocked(authApi.logout).mockReset()
  vi.mocked(authApi.me).mockReset()
})

describe('sesión', () => {
  it('empieza sin sesión', () => {
    const auth = useAuthStore()

    expect(auth.isLoggedIn).toBe(false)
    expect(auth.user).toBeNull()
  })

  it('al entrar guarda el token, el usuario y sus permisos (CASL)', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ token: 'abc', user: consulta })
    const auth = useAuthStore()

    await auth.login('consulta@example.com', 'password')

    expect(authApi.login).toHaveBeenCalledWith('consulta@example.com', 'password')
    expect(auth.isLoggedIn).toBe(true)
    expect(auth.token).toBe('abc')
    expect(auth.user?.name).toBe('Usuario de consulta')
    expect(auth.refreshed).toBe(true)
    expect(ability.can('view', 'teams')).toBe(true)
    expect(ability.can('manage', 'users')).toBe(false)
  })

  it('si el login falla no queda nada guardado', async () => {
    vi.mocked(authApi.login).mockRejectedValue(new Error('credenciales'))
    const auth = useAuthStore()

    await expect(auth.login('x@example.com', 'mala')).rejects.toThrow()

    expect(auth.isLoggedIn).toBe(false)
    expect(auth.user).toBeNull()
  })

  it('al registrarse deja la sesión abierta con los permisos del rol por defecto', async () => {
    vi.mocked(authApi.register).mockResolvedValue({ token: 'nuevo', user: consulta })
    const auth = useAuthStore()

    await auth.register('Usuario de consulta', 'consulta@example.com', 'password', 'password')

    expect(authApi.register).toHaveBeenCalledWith({
      name: 'Usuario de consulta',
      email: 'consulta@example.com',
      password: 'password',
      password_confirmation: 'password',
    })
    expect(auth.isLoggedIn).toBe(true)
    expect(auth.token).toBe('nuevo')
    expect(auth.refreshed).toBe(true)
    expect(ability.can('view', 'teams')).toBe(true)
    expect(ability.can('manage', 'users')).toBe(false)
  })

  it('si hay captcha, el token viaja con el registro', async () => {
    vi.mocked(authApi.register).mockResolvedValue({ token: 'nuevo', user: consulta })
    const auth = useAuthStore()

    await auth.register('Ana', 'ana@example.com', 'password', 'password', 'token-captcha')

    expect(authApi.register).toHaveBeenCalledWith(expect.objectContaining({ turnstile_token: 'token-captcha' }))
  })

  it('si el registro falla no queda sesión', async () => {
    vi.mocked(authApi.register).mockRejectedValue(new Error('correo repetido'))
    const auth = useAuthStore()

    await expect(auth.register('Ana', 'ana@example.com', 'password', 'password')).rejects.toThrow()

    expect(auth.isLoggedIn).toBe(false)
    expect(auth.user).toBeNull()
  })

  it('refresh trae los permisos nuevos', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ token: 'abc', user: consulta })
    vi.mocked(authApi.me).mockResolvedValue(admin)
    const auth = useAuthStore()
    await auth.login('consulta@example.com', 'password')

    await auth.refresh()

    expect(auth.user?.roles).toEqual(['admin'])
    expect(ability.can('manage', 'users')).toBe(true)
  })

  it('si refresh falla por red, la sesión sigue como estaba', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ token: 'abc', user: consulta })
    vi.mocked(authApi.me).mockRejectedValue(new Error('sin red'))
    const auth = useAuthStore()
    await auth.login('consulta@example.com', 'password')

    await auth.refresh()

    expect(auth.isLoggedIn).toBe(true)
    expect(auth.user?.name).toBe('Usuario de consulta')
  })

  it('setUser actualiza al usuario y sus permisos', async () => {
    const auth = useAuthStore()

    auth.setUser(admin)

    expect(auth.user?.name).toBe('Administrador')
    expect(ability.can('manage', 'users')).toBe(true)
  })

  it('al salir limpia todo', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ token: 'abc', user: admin })
    vi.mocked(authApi.logout).mockResolvedValue({} as never)
    const auth = useAuthStore()
    await auth.login('admin@example.com', 'password')

    await auth.logout()

    expect(authApi.logout).toHaveBeenCalled()
    expect(auth.isLoggedIn).toBe(false)
    expect(auth.user).toBeNull()
    expect(auth.refreshed).toBe(false)
    expect(ability.can('view', 'teams')).toBe(false)
  })

  it('al salir limpia la sesión local aunque el servidor no responda', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ token: 'abc', user: admin })
    vi.mocked(authApi.logout).mockRejectedValue(new Error('sin red'))
    const auth = useAuthStore()
    await auth.login('admin@example.com', 'password')

    await auth.logout()

    expect(auth.isLoggedIn).toBe(false)
    expect(ability.can('manage', 'users')).toBe(false)
  })
})
