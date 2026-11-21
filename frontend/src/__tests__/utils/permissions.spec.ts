import { describe, expect, it } from 'vitest'
import { countryCode } from '@/utils/countries'
import { describePermission, permissionActions } from '@/utils/permissions'

describe('describePermission', () => {
  it('separa sección y acción y las pone en español', () => {
    expect(describePermission('teams.view')).toMatchObject({
      key: 'teams',
      verb: 'view',
      subject: 'Equipos',
      action: 'Ver',
    })
    expect(describePermission('roles.manage')).toMatchObject({ subject: 'Roles y permisos', action: 'Administrar' })
  })

  it('con un permiso desconocido devuelve el texto original', () => {
    expect(describePermission('cosas.borrar')).toMatchObject({ subject: 'cosas', action: 'borrar' })
  })

  it('las columnas de la matriz son Ver y Administrar', () => {
    expect(permissionActions.map((a) => a.verb)).toEqual(['view', 'manage'])
  })
})

describe('countryCode', () => {
  it('ignora mayúsculas y espacios', () => {
    expect(countryCode('  Spain ')).toBe('es')
  })

  it('entiende las variantes raras de football-data', () => {
    expect(countryCode('Congo DR')).toBe('cd')
    expect(countryCode('DR Congo')).toBe('cd')
    expect(countryCode('Korea, South')).toBe('kr')
    expect(countryCode('England')).toBe('gb-eng')
  })

  it('un país que no conoce no tiene bandera', () => {
    expect(countryCode('Atlantis')).toBeUndefined()
    expect(countryCode(null)).toBeUndefined()
    expect(countryCode(undefined)).toBeUndefined()
  })
})
