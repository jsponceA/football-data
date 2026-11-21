import type { ManagedUser, Paginated, Permission, Role } from '@/types/api'
import { http } from './http'

export interface UserPayload {
  name: string
  email: string
  password?: string | null
  is_active: boolean
  roles: string[]
}

export interface RolePayload {
  name: string
  permissions: string[]
}

export async function getUsers(
  params: { page?: number; per_page?: number; search?: string; role?: string } = {},
) {
  const { data } = await http.get<Paginated<ManagedUser>>('/users', { params })
  return data
}

export async function createUser(payload: UserPayload) {
  const { data } = await http.post<{ data: ManagedUser }>('/users', payload)
  return data.data
}

export async function updateUser(id: number, payload: UserPayload) {
  const { data } = await http.put<{ data: ManagedUser }>(`/users/${id}`, payload)
  return data.data
}

export async function getRoles(params: { page?: number; per_page?: number } = {}) {
  const { data } = await http.get<Paginated<Role>>('/roles', { params })
  return data
}

export async function createRole(payload: RolePayload) {
  const { data } = await http.post<{ data: Role }>('/roles', payload)
  return data.data
}

export async function updateRole(id: number, payload: RolePayload) {
  const { data } = await http.put<{ data: Role }>(`/roles/${id}`, payload)
  return data.data
}

export function deleteRole(id: number) {
  return http.delete(`/roles/${id}`)
}

export async function getPermissions() {
  const { data } = await http.get<Paginated<Permission>>('/permissions', {
    params: { per_page: 100 },
  })
  return data.data
}
