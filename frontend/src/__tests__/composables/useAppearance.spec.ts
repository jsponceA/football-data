import { describe, expect, it } from 'vitest'
import { withSetup } from '@/__tests__/helpers'
import { palettes } from '@/plugins/palettes'
import { useUiStore } from '@/stores/ui'
import { useTheme } from 'vuetify'
import { useAppearance } from '@/composables/useAppearance'

function setup() {
  return withSetup(() => ({ appearance: useAppearance(), theme: useTheme(), ui: useUiStore() })).result
}

describe('useAppearance', () => {
  it('elegir un color lo guarda y lo aplica en los dos temas', () => {
    const { appearance, theme, ui } = setup()

    appearance.setPalette('blue')

    expect(ui.palette).toBe('blue')
    expect(theme.themes.value.light?.colors?.primary).toBe(palettes.blue.light)
    expect(theme.themes.value.dark?.colors?.primary).toBe(palettes.blue.dark)
  })

  it('cambiar el modo lo guarda y cambia el tema de Vuetify', () => {
    const { appearance, ui } = setup()

    appearance.setMode('dark')
    expect(ui.theme).toBe('dark')
    expect(appearance.isDark.value).toBe(true)

    appearance.setMode('light')
    expect(ui.theme).toBe('light')
    expect(appearance.isDark.value).toBe(false)
  })

  it('toggle alterna entre claro y oscuro', () => {
    const { appearance } = setup()
    appearance.setMode('light')

    appearance.toggle()
    expect(appearance.isDark.value).toBe(true)

    appearance.toggle()
    expect(appearance.isDark.value).toBe(false)
  })

  it('restablecer vuelve a los valores de fábrica', () => {
    const { appearance, theme, ui } = setup()
    appearance.setMode('dark')
    appearance.setPalette('red')
    ui.rail = true

    appearance.reset()

    expect(ui.theme).toBe('system')
    expect(ui.palette).toBe('green')
    expect(ui.rail).toBe(false)
    expect(theme.themes.value.light?.colors?.primary).toBe(palettes.green.light)
  })
})
