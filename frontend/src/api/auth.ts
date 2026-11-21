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

export interface ProfilePayload {
  name: string
  email: string
  password?: string | null
  current_password?: string | null
}

// el perfil propio devuelve lo mismo que /me, así la sesión se actualiza con la respuesta
export async function updateProfile(payload: ProfilePayload) {
  const { data } = await http.put<{ data: SessionUser }>('/me', payload)
  return data.data
}

export async function uploadMyPhoto(file: File) {
  const body = new FormData()
  body.append('photo', file)
  const { data } = await http.post<{ data: SessionUser }>('/me/photo', body)
  return data.data
}

export async function deleteMyPhoto() {
  const { data } = await http.delete<{ data: SessionUser }>('/me/photo')
  return data.data
}
