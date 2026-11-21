const subjects: Record<string, { label: string; icon: string }> = {
  competitions: { label: 'Ligas', icon: 'mdi-trophy' },
  teams: { label: 'Equipos', icon: 'mdi-shield-half-full' },
  players: { label: 'Jugadores', icon: 'mdi-account-group' },
  users: { label: 'Usuarios', icon: 'mdi-account-cog' },
  roles: { label: 'Roles y permisos', icon: 'mdi-shield-key' },
}

// columnas de la matriz de permisos, en el orden en que se muestran
export const permissionActions = [
  { verb: 'view', label: 'Ver', hint: 'Consultar listados y detalle' },
  { verb: 'manage', label: 'Administrar', hint: 'Crear, editar y eliminar' },
]

// "teams.view" -> { key: "teams", verb: "view", subject: "Equipos", action: "Ver", ... }
export function describePermission(name: string) {
  const [key = '', verb = ''] = name.split('.')
  return {
    key,
    verb,
    subject: subjects[key]?.label ?? key,
    icon: subjects[key]?.icon ?? 'mdi-key-outline',
    action: permissionActions.find((a) => a.verb === verb)?.label ?? verb,
  }
}
