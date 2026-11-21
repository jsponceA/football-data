import { computed } from 'vue'
import { useTheme } from 'vuetify'
import { useUiStore } from '@/stores/ui'

export function useThemeToggle() {
  const theme = useTheme()
  const ui = useUiStore()

  // con "system" lo que cuenta es el tema que Vuetify resolvió, no el valor guardado
  const isDark = computed(() => theme.current.value.dark)

  function toggle() {
    ui.theme = isDark.value ? 'light' : 'dark'
    theme.change(ui.theme)
  }

  return { isDark, toggle }
}
