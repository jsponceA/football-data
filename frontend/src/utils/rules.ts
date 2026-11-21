// reglas para los v-text-field / v-select de Vuetify: devuelven true o el mensaje de error

export const required = (value: unknown) => {
  const filled = Array.isArray(value)
    ? value.length > 0
    : typeof value === 'string'
      ? value.trim() !== ''
      : !!value
  return filled || 'Este campo es obligatorio.'
}

export const emailFormat = (value: string) => /.+@.+\..+/.test(value) || 'Escribe un correo válido.'

// vacío pasa: en la edición la contraseña es opcional, y para el alta se combina con required
export const minLength = (min: number) => (value: string) =>
  !value || value.length >= min || `Mínimo ${min} caracteres.`
