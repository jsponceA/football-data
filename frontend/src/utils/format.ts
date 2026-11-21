import dayjs from 'dayjs'
import 'dayjs/locale/es'
import relativeTime from 'dayjs/plugin/relativeTime'
import { ApiError } from '@/api/http'

dayjs.extend(relativeTime)
dayjs.locale('es')

export function formatDate(value: string | null) {
  return value ? dayjs(value).format('DD/MM/YYYY') : '—'
}

export function formatDateTime(value: string | null) {
  return value ? dayjs(value).format('DD/MM/YYYY hh:mm A') : '—'
}

// "hace 3 horas": se entiende mejor que una fecha para saber qué tan frescos están los datos
export function timeAgo(value: string | null) {
  return value ? dayjs(value).fromNow() : '—'
}

const positions: Record<string, string> = {
  Goalkeeper: 'Portero',
  Defence: 'Defensa',
  'Centre-Back': 'Defensa central',
  'Left-Back': 'Lateral izquierdo',
  'Right-Back': 'Lateral derecho',
  Midfield: 'Mediocampista',
  'Defensive Midfield': 'Mediocampista defensivo',
  'Central Midfield': 'Mediocampista central',
  'Attacking Midfield': 'Mediocampista ofensivo',
  Offence: 'Delantero',
  'Left Winger': 'Extremo izquierdo',
  'Right Winger': 'Extremo derecho',
  'Centre-Forward': 'Delantero centro',
}

// si llega una posición que no conozco se muestra tal cual
export function positionLabel(value: string | null) {
  return value ? (positions[value] ?? value) : '—'
}

const competitionTypes: Record<string, string> = { LEAGUE: 'Liga', CUP: 'Copa', PLAYOFFS: 'Playoffs' }

export function typeLabel(value: string) {
  return competitionTypes[value] ?? value
}

// el plan gratuito de football-data solo da acceso a las ligas TIER_ONE; las demás se rechazan con 403
const FREE_PLAN = 'TIER_ONE'

export function isFreePlan(plan: string) {
  return plan === FREE_PLAN
}

// líneas del campo, en el orden en que se muestran; el color distingue cada una de un vistazo
export const positionGroups = [
  { label: 'Porteros', color: 'warning', test: /^goalkeeper$/i },
  { label: 'Defensas', color: 'info', test: /back|defen[cs]e/i },
  { label: 'Mediocampistas', color: 'success', test: /midfield/i },
  { label: 'Delanteros', color: 'error', test: /offen[cs]e|winger|forward/i },
]

export function positionGroup(value: string | null) {
  return value ? positionGroups.find((g) => g.test.test(value)) : undefined
}

export function positionColor(value: string | null) {
  return positionGroup(value)?.color ?? 'grey'
}

export function initials(name: string) {
  const parts = name.split(/\s+/).filter(Boolean)
  const first = parts[0] ?? ''
  const last = parts[parts.length - 1] ?? ''
  const letters = parts.length > 1 ? first.charAt(0) + last.charAt(0) : name.slice(0, 2)
  return letters.toUpperCase()
}

export function age(value: string | null) {
  return value ? dayjs().diff(dayjs(value), 'year') : null
}

export function errorMessage(e: unknown) {
  if (e instanceof ApiError) {
    if (e.status === 429 && e.retryAfter) {
      return `${e.message} Vuelve a intentar en ${e.retryAfter} segundos.`
    }
    return e.message
  }
  return 'Ocurrió un error inesperado.'
}
