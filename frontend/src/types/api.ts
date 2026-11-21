export type Action = 'view' | 'manage'
export type Subject = 'competitions' | 'teams' | 'players' | 'users' | 'roles'

export interface AbilityRule {
  action: Action
  subject: Subject
}

export interface SessionUser {
  id: number
  name: string
  email: string
  is_active: boolean
  roles: string[]
  permissions: string[]
  abilities: AbilityRule[]
}

export interface Competition {
  id: number
  code: string
  name: string
  type: string
  emblem: string | null
  plan: string
  area: string | null
  imported_at: string | null
  teams?: Team[]
}

export interface Team {
  id: number
  name: string
  short_name: string | null
  tla: string | null
  crest: string | null
  venue: string | null
  founded: number | null
  players_count?: number
  competitions?: { id: number; code: string; name: string }[]
  players?: Player[]
}

export interface Player {
  id: number
  name: string
  position: string | null
  shirt_number: number | null
  date_of_birth: string | null
  nationality: string | null
  team?: { id: number; name: string }
}

export interface ManagedUser {
  id: number
  name: string
  email: string
  is_active: boolean
  roles: string[]
  created_at: string | null
}

export interface Role {
  id: number
  name: string
  protected: boolean
  permissions?: string[]
  users_count?: number
}

export interface Permission {
  id: number
  name: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  // solo lo manda /competitions: la lista viene de la copia local
  stale?: boolean
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}

export interface Single<T> {
  data: T
  meta?: { stale?: boolean }
}
