// cada color tiene una versión para el tema claro y otra más luminosa para el oscuro
export const palettes = {
  green: { label: 'Verde', light: '#1b8f4c', dark: '#34c471' },
  blue: { label: 'Azul', light: '#1565c0', dark: '#64b5f6' },
  purple: { label: 'Morado', light: '#6a1b9a', dark: '#ba68c8' },
  orange: { label: 'Naranja', light: '#d84315', dark: '#ff8a65' },
  red: { label: 'Rojo', light: '#c62828', dark: '#ef5350' },
}

export type PaletteKey = keyof typeof palettes
