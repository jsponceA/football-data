import type { Action, Subject } from '@/types/api'

export interface Section {
  title: string
  icon: string
  route: string
  ability: { action: Action; subject: Subject }
}

// el menú y la redirección inicial salen de esta lista
export const sections: Section[] = [
  { title: 'Ligas', icon: 'mdi-trophy', route: 'competitions', ability: { action: 'view', subject: 'competitions' } },
  { title: 'Equipos', icon: 'mdi-shield-half-full', route: 'teams', ability: { action: 'view', subject: 'teams' } },
  { title: 'Jugadores', icon: 'mdi-account-group', route: 'players', ability: { action: 'view', subject: 'players' } },
  { title: 'Usuarios', icon: 'mdi-account-cog', route: 'users', ability: { action: 'manage', subject: 'users' } },
  { title: 'Roles y permisos', icon: 'mdi-shield-key', route: 'roles', ability: { action: 'manage', subject: 'roles' } },
]
