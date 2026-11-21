import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { PaletteKey } from '@/plugins/palettes'

export type ThemeMode = 'system' | 'light' | 'dark'

export const useUiStore = defineStore(
  'ui',
  () => {
    // mientras nadie elija, se sigue el modo del sistema
    const theme = ref<ThemeMode>('system')
    const palette = ref<PaletteKey>('green')
    // menú lateral reducido a iconos
    const rail = ref(false)

    return { theme, palette, rail }
  },
  { persist: true },
)
