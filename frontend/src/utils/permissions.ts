const subjects: Record<string, string> = {
  competitions: 'Ligas',
  teams: 'Equipos',
  players: 'Jugadores',
  users: 'Usuarios',
  roles: 'Roles y permisos',
}

const actions: Record<string, string> = {
  view: 'Ver',
  manage: 'Administrar',
}

// "teams.view" -> { subject: "Equipos", action: "Ver" }
export function describePermission(name: string) {
  const [subject = '', action = ''] = name.split('.')
  return { subject: subjects[subject] ?? subject, action: actions[action] ?? action }
}
