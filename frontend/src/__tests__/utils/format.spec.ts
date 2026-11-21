import { afterEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/http'
import {
  age,
  errorMessage,
  formatDate,
  formatDateTime,
  initials,
  isFreePlan,
  positionColor,
  positionGroup,
  positionLabel,
  timeAgo,
  typeLabel,
} from '@/utils/format'

afterEach(() => vi.useRealTimers())

describe('fechas', () => {
  it('muestra la hora en formato de 12 horas con AM/PM', () => {
    // sin zona horaria en el texto: se interpreta en la hora local, así el resultado no depende de la máquina
    expect(formatDateTime('2026-11-20T19:21:00')).toBe('20/11/2026 07:21 PM')
    expect(formatDateTime('2026-11-20T07:05:00')).toBe('20/11/2026 07:05 AM')
    expect(formatDateTime('2026-11-20T00:30:00')).toBe('20/11/2026 12:30 AM')
  })

  it('formatDate no lleva hora', () => {
    expect(formatDate('2026-11-20T19:21:00')).toBe('20/11/2026')
  })

  it('sin valor muestra un guion', () => {
    expect(formatDate(null)).toBe('—')
    expect(formatDateTime(null)).toBe('—')
    expect(timeAgo(null)).toBe('—')
  })

  it('timeAgo lo dice en español', () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-11-21T12:00:00'))
    expect(timeAgo('2026-11-21T09:00:00')).toBe('hace 3 horas')
  })

  it('age calcula los años cumplidos', () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-06-15T12:00:00'))
    expect(age('2000-06-16')).toBe(25)
    expect(age('2000-06-15')).toBe(26)
    expect(age(null)).toBeNull()
  })
})

describe('posiciones', () => {
  it('traduce las conocidas y deja tal cual las desconocidas', () => {
    expect(positionLabel('Goalkeeper')).toBe('Portero')
    expect(positionLabel('Sweeper')).toBe('Sweeper')
    expect(positionLabel(null)).toBe('—')
  })

  it('agrupa por línea del campo', () => {
    expect(positionGroup('Goalkeeper')?.label).toBe('Porteros')
    expect(positionGroup('Centre-Back')?.label).toBe('Defensas')
    expect(positionGroup('Defence')?.label).toBe('Defensas')
    expect(positionGroup('Central Midfield')?.label).toBe('Mediocampistas')
    expect(positionGroup('Left Winger')?.label).toBe('Delanteros')
    expect(positionGroup('Offence')?.label).toBe('Delanteros')
  })

  it('sin posición conocida no hay grupo y el color es gris', () => {
    expect(positionGroup(null)).toBeUndefined()
    expect(positionGroup('Mascota')).toBeUndefined()
    expect(positionColor(null)).toBe('grey')
    expect(positionColor('Goalkeeper')).toBe('warning')
  })
})

describe('textos', () => {
  it('typeLabel traduce el tipo de competición', () => {
    expect(typeLabel('LEAGUE')).toBe('Liga')
    expect(typeLabel('CUP')).toBe('Copa')
    expect(typeLabel('OTRA')).toBe('OTRA')
  })

  it('isFreePlan solo acepta el plan gratuito de football-data', () => {
    expect(isFreePlan('TIER_ONE')).toBe(true)
    expect(isFreePlan('TIER_FOUR')).toBe(false)
    expect(isFreePlan('')).toBe(false)
  })

  it('initials usa la primera y la última palabra', () => {
    expect(initials('Lionel Andrés Messi')).toBe('LM')
    expect(initials('Pelé')).toBe('PE')
    expect(initials('  ana   ruiz ')).toBe('AR')
    expect(initials('')).toBe('')
  })
})

describe('errorMessage', () => {
  it('usa el mensaje del backend', () => {
    expect(errorMessage(new ApiError(404, 'No existe.'))).toBe('No existe.')
  })

  it('en un 429 con Retry-After dice cuánto esperar', () => {
    expect(errorMessage(new ApiError(429, 'Límite alcanzado.', {}, 30))).toBe(
      'Límite alcanzado. Vuelve a intentar en 30 segundos.',
    )
  })

  it('un 429 sin Retry-After no inventa el tiempo', () => {
    expect(errorMessage(new ApiError(429, 'Límite alcanzado.'))).toBe('Límite alcanzado.')
  })

  it('cualquier otra cosa es un error inesperado', () => {
    expect(errorMessage(new Error('boom'))).toBe('Ocurrió un error inesperado.')
    expect(errorMessage('texto')).toBe('Ocurrió un error inesperado.')
  })
})
