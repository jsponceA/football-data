import { computed } from 'vue'
import { useTheme } from 'vuetify'
import { palettes, type PaletteKey } from '@/plugins/palettes'
import { useUiStore, type ThemeMode } from '@/stores/ui'

type ThemeInstance = ReturnType<typeof useTheme>

function paint(theme: ThemeInstance, key: PaletteKey) {
  const { light, dark } = theme.themes.value
  if (light?.colors) light.colors.primary = palettes[key].light
  if (dark?.colors) dark.colors.primary = palettes[key].dark
}

// main.ts lo llama una vez, cuando ya existen Pinia y Vuetify
export function applyAppearance(theme: ThemeInstance, ui: ReturnType<typeof useUiStore>) {
  theme.change(ui.theme)
  paint(theme, ui.palette)
}

export function useAppearance() {
  const theme = useTheme()
  const ui = useUiStore()

  // con "system" lo que cuenta es el tema que Vuetify resolvió, no el valor guardado
  const isDark = computed(() => theme.current.value.dark)

  function setMode(mode: ThemeMode) {
    ui.theme = mode
    theme.change(mode)
  }

  function setPalette(key: PaletteKey) {
    ui.palette = key
    paint(theme, key)
  }

  function toggle() {
    setMode(isDark.value ? 'light' : 'dark')
  }

  function reset() {
    ui.rail = false
    setMode('system')
    setPalette('green')
  }

  return { isDark, setMode, setPalette, toggle, reset }
}
