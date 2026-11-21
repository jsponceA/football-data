import dayjs from 'dayjs'
import 'dayjs/locale/es'
import { ApiError } from '@/api/http'

dayjs.locale('es')

export function formatDate(value: string | null) {
  return value ? dayjs(value).format('DD/MM/YYYY') : '—'
}

export function formatDateTime(value: string | null) {
  return value ? dayjs(value).format('DD/MM/YYYY HH:mm') : '—'
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

export function errorMessage(e: unknown) {
  if (e instanceof ApiError) {
    if (e.status === 429 && e.retryAfter) {
      return `${e.message} Vuelve a intentar en ${e.retryAfter} segundos.`
    }
    return e.message
  }
  return 'Ocurrió un error inesperado.'
}
