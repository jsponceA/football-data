import { describe, expect, it } from 'vitest'
import { emailFormat, minLength, required } from '../rules'

describe('required', () => {
  it('rechaza vacíos, espacios, null y listas vacías', () => {
    for (const value of ['', '   ', null, undefined, [], false]) {
      expect(required(value)).toBe('Este campo es obligatorio.')
    }
  })

  it('acepta texto, números y listas con algo', () => {
    expect(required('Ana')).toBe(true)
    expect(required(['admin'])).toBe(true)
    expect(required(5)).toBe(true)
  })
})

describe('emailFormat', () => {
  it('acepta un correo normal', () => {
    expect(emailFormat('ana@example.com')).toBe(true)
  })

  it('rechaza lo que no parece un correo', () => {
    for (const value of ['ana', 'ana@', 'ana@example', '@example.com']) {
      expect(emailFormat(value)).toBe('Escribe un correo válido.')
    }
  })
})

describe('minLength', () => {
  const min8 = minLength(8)

  it('deja pasar el vacío: en la edición la contraseña es opcional', () => {
    expect(min8('')).toBe(true)
  })

  it('exige el mínimo cuando hay algo escrito', () => {
    expect(min8('corta')).toBe('Mínimo 8 caracteres.')
    expect(min8('12345678')).toBe(true)
  })
})
