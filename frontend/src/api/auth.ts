import type { SessionUser } from '@/types/api'
import { http } from './http'

// aquí el usuario viene directo; en /me viene dentro de "data"
export async function login(email: string, password: string) {
  const { data } = await http.post<{ token: string; user: SessionUser }>('/login', {
    email,
    password,
  })
  return data
}

export function logout() {
  return http.post('/logout')
}

export async function me() {
  const { data } = await http.get<{ data: SessionUser }>('/me')
  return data.data
}
