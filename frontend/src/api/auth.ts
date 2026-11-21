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

// responde igual que el login: la persona queda con la sesión abierta
export async function register(payload: { name: string; email: string; password: string; password_confirmation: string }) {
  const { data } = await http.post<{ token: string; user: SessionUser }>('/register', payload)
  return data
}

// devuelve el texto que el backend quiere mostrar; es el mismo exista o no el correo
export async function forgotPassword(email: string) {
  const { data } = await http.post<{ message: string }>('/forgot-password', { email })
  return data.message
}

export async function resetPassword(payload: {
  token: string
  email: string
  password: string
  password_confirmation: string
}) {
  const { data } = await http.post<{ message: string }>('/reset-password', payload)
  return data.message
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
